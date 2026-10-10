<x-mail::message>
# New Lead Received

A new lead has been submitted through the contact form on **{{ config('app.name') }}**.

**Name:** {{ $lead->full_name }}  
**Email:** [{{ $lead->email }}](mailto:{{ $lead->email }})  
**Phone:** {{ $lead->phone ?? 'N/A' }}  
**Company:** {{ $lead->company_name ?? 'N/A' }}  
**Interested Service:** {{ $lead->interested_service ?? 'N/A' }}  
**Budget Range:** {{ $lead->budget_range ?? 'N/A' }}  

**Subject:** {{ $lead->subject ?? 'N/A' }}  

**Message:**  
{{ $lead->message }}

---
**Lead Source Info:**
- UTM Source: {{ $lead->utm_source ?? 'N/A' }}
- UTM Medium: {{ $lead->utm_medium ?? 'N/A' }}
- UTM Campaign: {{ $lead->utm_campaign ?? 'N/A' }}
- Landing Page: {{ $lead->landing_page ?? 'N/A' }}
- Language: {{ strtoupper($lead->language) }}

<x-mail::button :url="route('admin.leads.index')">
View in Admin Panel
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} System
</x-mail::message>
