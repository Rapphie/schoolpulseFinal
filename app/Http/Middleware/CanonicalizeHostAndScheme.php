<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalizeHostAndScheme
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') !== 'local') {
            return $next($request);
        }

        $canonicalUrl = config('app.url');

        if (! is_string($canonicalUrl) || $canonicalUrl === '') {
            return $next($request);
        }

        $canonicalParts = parse_url($canonicalUrl);

        if (! is_array($canonicalParts)) {
            return $next($request);
        }

        $canonicalScheme = $canonicalParts['scheme'] ?? null;
        $canonicalHost = $canonicalParts['host'] ?? null;
        $canonicalPort = isset($canonicalParts['port']) ? (int) $canonicalParts['port'] : null;

        if (! is_string($canonicalScheme) || ! is_string($canonicalHost)) {
            return $next($request);
        }

        $requestPort = $request->getPort();
        $portMatches = $canonicalPort === null || $requestPort === $canonicalPort;

        if (
            $request->getScheme() === $canonicalScheme
            && $request->getHost() === $canonicalHost
            && $portMatches
        ) {
            return $next($request);
        }

        $targetUrl = $canonicalScheme.'://'.$canonicalHost;

        if ($canonicalPort !== null) {
            $targetUrl .= ':'.$canonicalPort;
        }

        $targetUrl .= $request->getRequestUri();

        return redirect()->to($targetUrl, $this->redirectStatusCode($request->method()));
    }

    protected function redirectStatusCode(string $method): int
    {
        if (in_array($method, ['GET', 'HEAD'], true)) {
            return 301;
        }

        return 307;
    }
}
