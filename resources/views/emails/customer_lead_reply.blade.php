<x-mail::message>
# {{ $replySubject }}

Hello {{ $lead->full_name }},

{{ $replyMessage }}

---

**Your original inquiry:**  
{{ $lead->subject ?? 'Contact request' }}

**Original message:**  
{{ $lead->message }}

Replied by {{ $admin->name }} from {{ config('app.name') }}.

Thanks,<br>
{{ config('mail.from.name', config('app.name')) }}
</x-mail::message>
