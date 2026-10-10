<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminLeadNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $lead;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Lead: '.$this->lead->full_name.' - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin_lead_notification',
        );
    }
}
