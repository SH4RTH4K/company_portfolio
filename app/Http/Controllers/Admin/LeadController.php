<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CustomerLeadReply;
use App\Models\Lead;
use App\Services\MailConfiguration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class LeadController extends Controller
{
    public function index(): View
    {
        $leads = Lead::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.leads.index', compact('leads'));
    }

    public function show(Lead $lead): View
    {
        $lead->load('repliedBy');

        return view('admin.leads.show', compact('lead'));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(Lead::STATUSES)],
        ]);

        $lead->update($validated);

        return redirect()->route('admin.leads.show', $lead)->with('success', 'Lead status updated successfully.');
    }

    public function reply(Request $request, Lead $lead, MailConfiguration $mailConfiguration): RedirectResponse
    {
        if (! $lead->email) {
            return redirect()->route('admin.leads.show', $lead)->with('error', 'This lead does not have an email address.');
        }

        $validated = $request->validate([
            'reply_subject' => ['required', 'string', 'max:255'],
            'reply_message' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $mailConfiguration->apply();

            Mail::to($lead->email)->send(
                new CustomerLeadReply(
                    lead: $lead,
                    admin: $request->user(),
                    replySubject: $validated['reply_subject'],
                    replyMessage: $validated['reply_message'],
                )
            );
        } catch (Throwable $exception) {
            Log::error('Failed to send lead reply email: '.$exception->getMessage(), [
                'lead_id' => $lead->id,
                'admin_id' => $request->user()?->id,
            ]);

            return redirect()
                ->route('admin.leads.show', $lead)
                ->withInput()
                ->with('error', 'Reply email could not be sent. Please check the mail integration settings.');
        }

        $lead->update([
            'reply_subject' => $validated['reply_subject'],
            'reply_message' => $validated['reply_message'],
            'replied_by' => $request->user()?->id,
            'replied_at' => now(),
            'status' => $lead->status === 'New' ? 'Contacted' : $lead->status,
        ]);

        return redirect()->route('admin.leads.show', $lead)->with('success', 'Reply sent to the customer successfully.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('admin.leads.index')->with('success', 'Lead deleted successfully.');
    }
}
