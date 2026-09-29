<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Public presentation page (about, course topics, services, contact).
 */
class LandingController extends Controller
{
    public const SUBJECTS = ['demo' => 'Ukážka platformy pre školu', 'company' => 'Školenie zamestnancov pre firmu', 'courses' => 'Kurzy a obsah', 'technical' => 'Technická podpora', 'other' => 'Iné'];

    public function show(): View
    {
        return view('landing', ['subjects' => self::SUBJECTS]);
    }

    public function contact(Request $request): RedirectResponse
    {
        // Honeypot: the hidden "website" field is filled only by bots - pretend success.
        if (filled($request->input('website'))) {
            return redirect()->to(route('home').'#kontakt')->with('contact_sent', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'school' => ['nullable', 'string', 'max:150'],
            'subject' => ['required', Rule::in(array_keys(self::SUBJECTS))],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => __('Na odoslanie správy potrebujeme váš súhlas so spracovaním údajov.'),
        ], [
            'name' => __('meno'), 'school' => __('škola / firma'), 'subject' => __('téma'), 'message' => __('správa'),
        ]);

        $recipient = config('cysa.contact_email');

        if (blank($recipient)) {
            Log::warning('Contact form: CONTACT_EMAIL is not configured, message not sent.');

            return back()->withInput()->with('error', __('Správu sa momentálne nepodarilo odoslať. Skúste to prosím neskôr.'));
        }

        Mail::to($recipient)->send(new ContactMessage([
            'name' => $data['name'],
            'email' => $data['email'],
            'school' => $data['school'] ?? null,
            'subject' => self::SUBJECTS[$data['subject']],
            'message' => $data['message'],
        ]));

        return redirect()->to(route('home').'#kontakt')->with('contact_sent', true);
    }
}
