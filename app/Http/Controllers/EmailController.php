<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EmailController extends Controller
{
    public function send(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $response = Http::withToken(env('RESEND_API_KEY'))
            ->post('https://api.resend.com/emails', [
                'from' => env('MAIL_FROM_ADDRESS'),
                'to' => [$request->to],
                'subject' => $request->subject,
                'html' => '<p>'.nl2br(e($request->message)).'</p>',
            ]);

        if ($response->successful()) {
            return back()->with('success', 'Email sent successfully!');
        }

        return back()->with('error', 'Failed to send email: '.($response->json('message') ?? 'Unknown error'));
    }
}
