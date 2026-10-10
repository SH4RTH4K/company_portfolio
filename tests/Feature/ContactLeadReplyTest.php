<?php

namespace Tests\Feature;

use App\Mail\AdminLeadNotification;
use App\Mail\CustomerLeadReply;
use App\Mail\UserLeadAcknowledgment;
use App\Models\Lead;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactLeadReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_stores_lead_and_sends_notifications_using_admin_mail_settings(): void
    {
        Mail::fake();
        foreach ([
            'mail_mailer' => 'log',
            'mail_from_address' => 'no-reply@zerogravities.com',
            'mail_from_name' => 'Zero Gravities Support',
            'mail_admin_notification_address' => 'leads@zerogravities.com',
        ] as $key => $value) {
            SiteSetting::create([
                'key' => $key,
                'value_en' => $value,
            ]);
        }

        $this->post('/bn/contact', [
            'full_name' => 'Rahim Uddin',
            'company_name' => 'Acme Bangladesh',
            'phone' => '+8801400000000',
            'email' => 'rahim@example.com',
            'subject' => 'Need ERP support',
            'message' => 'Please contact me about ERP support.',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('leads', [
            'full_name' => 'Rahim Uddin',
            'email' => 'rahim@example.com',
            'language' => 'bn',
            'status' => 'New',
            'lead_source' => 'Contact Form',
        ]);

        $lead = Lead::firstOrFail();

        Mail::assertSent(AdminLeadNotification::class, function (AdminLeadNotification $mail) use ($lead) {
            return $mail->lead->is($lead) && $mail->hasTo('leads@zerogravities.com');
        });

        Mail::assertSent(UserLeadAcknowledgment::class, function (UserLeadAcknowledgment $mail) {
            return $mail->hasTo('rahim@example.com');
        });

        $this->assertSame('no-reply@zerogravities.com', config('mail.from.address'));
        $this->assertSame('Zero Gravities Support', config('mail.from.name'));
    }

    public function test_admin_sidebar_has_contact_messages_menu(): void
    {
        $admin = User::factory()->create([
            'role' => 'lead_manager',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Contact Messages')
            ->assertSee(route('admin.leads.index'), false);
    }

    public function test_admin_can_reply_to_stored_lead_by_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'name' => 'Support Admin',
            'role' => 'lead_manager',
        ]);
        $lead = Lead::create([
            'full_name' => 'Client Person',
            'email' => 'client@example.com',
            'subject' => 'Website project',
            'message' => 'I need a company website.',
            'status' => 'New',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.leads.reply', $lead), [
                'reply_subject' => 'Re: Website project',
                'reply_message' => 'Thanks for contacting us. We will schedule a call today.',
            ])
            ->assertRedirect(route('admin.leads.show', $lead))
            ->assertSessionHas('success');

        $lead->refresh();

        $this->assertSame('Contacted', $lead->status);
        $this->assertSame('Re: Website project', $lead->reply_subject);
        $this->assertSame('Thanks for contacting us. We will schedule a call today.', $lead->reply_message);
        $this->assertSame($admin->id, $lead->replied_by);
        $this->assertNotNull($lead->replied_at);

        Mail::assertSent(CustomerLeadReply::class, function (CustomerLeadReply $mail) {
            return $mail->hasTo('client@example.com')
                && $mail->replySubject === 'Re: Website project'
                && $mail->replyMessage === 'Thanks for contacting us. We will schedule a call today.';
        });
    }

    public function test_admin_cannot_reply_to_lead_without_customer_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => 'lead_manager',
        ]);
        $lead = Lead::create([
            'full_name' => 'Phone Only Client',
            'message' => 'Please call me.',
            'status' => 'New',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.leads.reply', $lead), [
                'reply_subject' => 'Re: Contact request',
                'reply_message' => 'We received your message.',
            ])
            ->assertRedirect(route('admin.leads.show', $lead))
            ->assertSessionHas('error', 'This lead does not have an email address.');

        $lead->refresh();

        $this->assertNull($lead->replied_at);
        Mail::assertNothingSent();
    }
}
