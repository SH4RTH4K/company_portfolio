@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12 text-center">
            @if(app()->getLocale() == 'en')
                <h1 class="display-4">Welcome to Zero Gravities</h1>
                <p class="lead">Technology & Solutions</p>
            @else
                <h1 class="display-4">জিরো গ্রাভিটিজ-এ স্বাগতম</h1>
                <p class="lead">প্রযুক্তি ও সমাধান</p>
            @endif
        </div>
    </div>
</div>
@endsection

