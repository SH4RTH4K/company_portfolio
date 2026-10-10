@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $title = $locale == 'en' ? 'Contact Us' : 'যোগাযোগ করুন';
    $name_label = $locale == 'en' ? 'Full Name *' : 'পূর্ণ নাম *';
    $email_label = $locale == 'en' ? 'Email Address *' : 'ইমেইল *';
    $phone_label = $locale == 'en' ? 'Phone Number' : 'ফোন নম্বর';
    $company_label = $locale == 'en' ? 'Company Name' : 'কোম্পানির নাম';
    $subject_label = $locale == 'en' ? 'Subject' : 'বিষয়';
    $message_label = $locale == 'en' ? 'Message *' : 'আপনার বার্তা *';
    $submit_btn = $locale == 'en' ? 'Send Message' : 'বার্তা পাঠান';
@endphp

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="mb-4 text-center">{{ $title }}</h1>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="{{ route('contact.submit', ['locale' => $locale]) }}" method="POST">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ $name_label }}</label>
                                <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name') }}" required>
                                @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ $email_label }}</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ $phone_label }}</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ $company_label }}</label>
                                <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ $subject_label }}</label>
                            <input type="text" name="subject" class="form-control" value="{{ old('subject') }}">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">{{ $message_label }}</label>
                            <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="5" required>{{ old('message') }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">{{ $submit_btn }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

