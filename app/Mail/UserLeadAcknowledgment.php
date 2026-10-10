<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserLeadAcknowledgment extends Mailable
{
    use Queueable, SerializesModels;

    public $lead;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function envelope(): Envelope
    {
        $subject = $this->lead->language === 'bn'
            ? 'আপনার বার্তার জন্য ধন্যবাদ - '.config('app.name')
            : 'Thank you for your message - '.config('app.name');

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user_lead_acknowledgment',
        );
    }
}
