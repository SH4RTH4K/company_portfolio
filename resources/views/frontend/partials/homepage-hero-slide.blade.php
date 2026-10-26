@php
    $locale = app()->getLocale();
    $isBn = $locale === 'bn';
    $heroText = function (string $key, string $default) use ($hero, $locale, $globalSettings): string {
        if ($hero) {
            return $hero->text($key, $locale) ?? $default;
        }

        return filled($globalSettings[$key] ?? null) ? $globalSettings[$key] : $default;
    };
@endphp

<div class="container py-5">
    <div class="row align-items-center g-5">
        <div class="col-lg-7">
            <div class="d-inline-flex align-items-center bg-white bg-opacity-10 border border-white border-opacity-25 rounded-pill px-3 py-1 mb-3">
                <span class="badge bg-primary rounded-pill me-2">{{ $heroText('hero_badge_label', $isBn ? 'জিরো গ্র্যাভিটিস' : 'Zero Gravities') }}</span>
                <span class="small text-white-50">{{ $heroText('hero_badge_text', $isBn ? 'প্রযুক্তি • সমাধান • বাণিজ্য' : 'Technology • Solutions • Commerce') }}</span>
            </div>
            <h1 class="display-4 fw-extrabold text-white mb-3">
                {{ $heroText('hero_title_prefix', $isBn ? 'সীমাহীন প্রযুক্তি,' : 'Technology Without Limits,') }} <span class="text-accent">{{ $heroText('hero_title_accent', $isBn ? 'আধুনিক ব্যবসায়িক সমাধান' : 'Built For Growth') }}</span>
            </h1>
            <p class="lead text-white-50 mb-4 pe-lg-4">
                {{ $heroText('hero_intro', $isBn ? 'স্মার্ট সফটওয়্যার, নির্ভরযোগ্য আইটি অবকাঠামো, ক্লাউড আর্কিটেকচার এবং ২৪/৭ এন্টারপ্রাইজ সাপোর্ট সার্ভিসের মাধ্যমে আমরা আপনার ব্যবসাকে সামনের দিকে এগিয়ে নিতে কাজ করি।' : 'We empower progressive enterprises with intelligent custom software, 24/7 proactive Managed IT & AMC, robust cloud cybersecurity, and enterprise-grade hardware infrastructure.') }}
            </p>
            <div class="d-flex flex-wrap gap-3">
                <a href="{{ route('services.index', ['locale' => $locale]) }}" class="btn btn-primary btn-lg rounded-pill px-4 shadow">
                    <i class="bi bi-grid-fill me-2"></i> {{ $heroText('hero_primary_button_label', $isBn ? 'আমাদের সমাধান দেখুন' : 'Explore Solutions') }}
                </a>
                <a href="{{ route('contact', ['locale' => $locale]) }}" class="btn btn-outline-light btn-lg rounded-pill px-4">
                    <i class="bi bi-chat-quote-fill me-2"></i> {{ $heroText('hero_secondary_button_label', $isBn ? 'সমাধানের জন্য যোগাযোগ' : 'Request Consultation') }}
                </a>
            </div>
        </div>

        <div class="col-lg-5 text-center">
            <div class="card bg-deep border border-secondary border-opacity-25 p-4 rounded-4 shadow-lg text-start">
                <div class="d-flex align-items-center mb-3">
                    <span class="p-3 bg-primary bg-opacity-25 rounded-3 text-primary fs-3 me-3"><i class="bi bi-shield-check"></i></span>
                    <div>
                        <h5 class="text-white mb-0">{{ $heroText('hero_card_title', $isBn ? '২৪/৭ এন্টারপ্রাইজ সাপোর্ট' : 'Enterprise IT Reliability') }}</h5>
                        <small class="text-white-50">{{ $heroText('hero_card_subtitle', $isBn ? '১০০% নিশ্চিত আপটাইম এসএলএ' : '99.9% Uptime Guarantee') }}</small>
                    </div>
                </div>
                <hr class="border-secondary border-opacity-25">
                <div class="row g-3 text-center">
                    @foreach([1, 2, 3] as $statNumber)
                        @php
                            $valueDefaults = [1 => $isBn ? '১৫+' : '15+', 2 => $isBn ? '২০০+' : '200+', 3 => $isBn ? '২৪/৭' : '24/7'];
                            $labelDefaults = [1 => $isBn ? 'বছরের অভিজ্ঞতা' : 'Years Exp', 2 => $isBn ? 'কর্পোরেট ক্লায়েন্ট' : 'Clients', 3 => $isBn ? 'সাপোর্ট টিম' : 'NOC Support'];
                        @endphp
                        <div class="col-4">
                            <h3 class="fw-bold text-accent mb-0">{{ $heroText('hero_stat_'.$statNumber.'_value', $valueDefaults[$statNumber]) }}</h3>
                            <small class="text-white-50">{{ $heroText('hero_stat_'.$statNumber.'_label', $labelDefaults[$statNumber]) }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
