<?php

namespace App\Http\Controllers;

use App\Mail\AdminLeadNotification;
use App\Mail\UserLeadAcknowledgment;
use App\Models\Lead;
use App\Services\MailConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show(Request $request, $locale = 'en')
    {
        return view('contact');
    }

    public function submit(Request $request, MailConfiguration $mailConfiguration, $locale = 'en')
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'required|email|max:255',
            'interested_service' => 'nullable|string|max:255',
            'budget_range' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $validated['language'] = $locale;
        $validated['status'] = 'New';

        // Tracking
        $validated['lead_source'] = 'Contact Form';
        $validated['landing_page'] = request()->headers->get('referer') ?? url()->current();
        $validated['referrer'] = session()->get('initial_referrer') ?? request()->headers->get('referer');

        $validated['utm_source'] = session('utm_source', request()->query('utm_source'));
        $validated['utm_medium'] = session('utm_medium', request()->query('utm_medium'));
        $validated['utm_campaign'] = session('utm_campaign', request()->query('utm_campaign'));
        $validated['utm_term'] = session('utm_term', request()->query('utm_term'));
        $validated['utm_content'] = session('utm_content', request()->query('utm_content'));

        $lead = Lead::create($validated);

        try {
            $mailConfiguration->apply();

            Mail::to($mailConfiguration->adminRecipient())
                ->send(new AdminLeadNotification($lead));

            Mail::to($lead->email)
                ->send(new UserLeadAcknowledgment($lead));
        } catch (\Exception $e) {
            Log::error('Failed to send contact emails: '.$e->getMessage());
            // We don't fail the request if email sending fails, as the lead is already saved in DB
        }

        $successMsg = $locale == 'bn'
            ? 'আপনার বার্তা সফলভাবে পাঠানো হয়েছে! আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।'
            : 'Your message has been sent successfully! We will get back to you shortly.';

        return redirect()->back()->with('success', $successMsg);
    }
}
