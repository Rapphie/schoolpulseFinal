<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\CanonicalizeHostAndScheme;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CanonicalizeHostAndSchemeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config()->set('app.url', 'http://schoolpulsefinal.test');
    }

    public function test_local_environment_redirects_non_canonical_host_to_configured_host(): void
    {
        config()->set('app.env', 'local');

        $middleware = app(CanonicalizeHostAndScheme::class);
        $request = Request::create('http://localhost/login', 'GET');
        $response = $middleware->handle($request, static fn (Request $nextRequest): Response => response('ok'));

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('http://schoolpulsefinal.test/login', $response->headers->get('Location'));
    }

    public function test_local_environment_redirect_preserves_query_parameters(): void
    {
        config()->set('app.env', 'local');

        $middleware = app(CanonicalizeHostAndScheme::class);
        $request = Request::create('http://localhost/login?next=%2Fadmin%2Fdashboard', 'GET');
        $response = $middleware->handle($request, static fn (Request $nextRequest): Response => response('ok'));

        $this->assertSame(301, $response->getStatusCode());

        $redirectUrl = $response->headers->get('Location');

        $this->assertNotNull($redirectUrl);

        $redirectParts = parse_url($redirectUrl);

        $this->assertIsArray($redirectParts);
        $this->assertSame('http', $redirectParts['scheme'] ?? null);
        $this->assertSame('schoolpulsefinal.test', $redirectParts['host'] ?? null);
        $this->assertSame('/login', $redirectParts['path'] ?? null);

        $query = [];
        parse_str($redirectParts['query'] ?? '', $query);
        $this->assertSame('/admin/dashboard', $query['next'] ?? null);
    }

    public function test_canonical_login_authenticates_and_session_persists_for_dashboard_redirect(): void
    {
        config()->set('app.env', 'local');

        Role::firstOrCreate(
            ['id' => 1],
            ['name' => 'admin', 'description' => 'Administrator with full access']
        );

        $adminUser = User::factory()->create([
            'role_id' => 1,
            'password' => Hash::make('admin-password'),
            'temporary_password' => null,
        ]);

        $loginResponse = $this->post('http://schoolpulsefinal.test/login', [
            'email' => $adminUser->email,
            'password' => 'admin-password',
            'role' => '1',
        ]);

        $loginResponse->assertRedirect('/');
        $this->assertAuthenticatedAs($adminUser);

        $dashboardResponse = $this->get('http://schoolpulsefinal.test/');

        $dashboardResponse->assertRedirect(route('admin.dashboard'));
    }

    public function test_non_local_environment_does_not_force_canonical_redirect(): void
    {
        config()->set('app.env', 'production');

        $middleware = app(CanonicalizeHostAndScheme::class);
        $request = Request::create('http://localhost/login', 'GET');
        $response = $middleware->handle($request, static fn (Request $nextRequest): Response => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
