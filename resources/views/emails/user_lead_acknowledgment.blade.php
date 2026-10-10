<x-mail::message>
@if($lead->language === 'bn')
# আপনার বার্তার জন্য ধন্যবাদ, {{ $lead->full_name }}!

আমরা আপনার মেসেজটি পেয়েছি। আমাদের একজন প্রতিনিধি খুব শিগগিরই আপনার সাথে যোগাযোগ করবেন।

আপনার রেফারেন্সের জন্য, আপনি আমাদের কাছে যে তথ্য পাঠিয়েছেন তা নিচে দেওয়া হলো:

**বিষয়:** {{ $lead->subject ?? 'প্রযোজ্য নয়' }}  
**মেসেজ:**  
{{ $lead->message }}

আমাদের সাথে যোগাযোগ করার জন্য ধন্যবাদ।

বিনীত,<br>
{{ config('app.name') }} টিম
@else
# Thank you for contacting us, {{ $lead->full_name }}!

We have successfully received your message. One of our representatives will get back to you shortly.

For your reference, here is a copy of what you submitted:

**Subject:** {{ $lead->subject ?? 'N/A' }}  
**Message:**  
{{ $lead->message }}

We appreciate you getting in touch with us.

Best regards,<br>
The {{ config('app.name') }} Team
@endif
</x-mail::message>
