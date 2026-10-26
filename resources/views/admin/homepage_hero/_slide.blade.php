@php
    $slideValue = fn (string $key, string $locale, string $default = ''): string => (string) data_get($slide, $key.'.'.$locale, $default);
    $fieldError = fn (string $key, string $locale): string => "slides.$index.$key.$locale";
@endphp

<section class="hero-editor-section" data-hero-slide>
    <input type="hidden" name="slides[{{ $index }}][id]" value="{{ data_get($slide, 'id') }}">

    <div class="hero-editor-section-header">
        <span class="hero-editor-section-icon"><i class="bi bi-stars"></i></span>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">Hero Slide <span data-slide-number>{{ is_numeric($index) ? ((int) $index + 1) : '' }}</span></h5>
            <p>English and Bengali content for this slide.</p>
        </div>
        <div class="btn-group" role="group" aria-label="Slide controls">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-move-slide="up" title="Move slide up"><i class="bi bi-arrow-up"></i></button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-move-slide="down" title="Move slide down"><i class="bi bi-arrow-down"></i></button>
            <button type="button" class="btn btn-outline-danger btn-sm" data-remove-slide title="Remove slide"><i class="bi bi-trash"></i></button>
        </div>
    </div>

    <div class="hero-editor-subhead">Badge & Headline</div>
    <div class="row g-3 mb-3">
        @foreach([
            ['hero_badge_label', 'Badge Label', 60, 'Zero Gravities', 'জিরো গ্র্যাভিটিস'],
            ['hero_badge_text', 'Badge Text', 120, 'Technology • Solutions • Commerce', 'প্রযুক্তি • সমাধান • বাণিজ্য'],
            ['hero_title_prefix', 'Headline Main', 120, 'Technology Without Limits,', 'সীমাহীন প্রযুক্তি,'],
            ['hero_title_accent', 'Headline Accent', 120, 'Built For Growth', 'আধুনিক ব্যবসায়িক সমাধান'],
        ] as [$key, $label, $maximumLength, $englishDefault, $bengaliDefault])
            @foreach(['en' => ['EN', $englishDefault], 'bn' => ['BN', $bengaliDefault]] as $locale => [$localeLabel, $default])
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">{{ $label }} ({{ $localeLabel }})</label>
                    <input
                        type="text"
                        name="slides[{{ $index }}][{{ $key }}][{{ $locale }}]"
                        class="form-control @error($fieldError($key, $locale)) is-invalid @enderror"
                        value="{{ $slideValue($key, $locale, $default) }}"
                        maxlength="{{ $maximumLength }}"
                    >
                    @error($fieldError($key, $locale))<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
        @endforeach

        <div class="col-md-6">
            <label class="form-label">Intro Text (EN)</label>
            <textarea name="slides[{{ $index }}][hero_intro][en]" class="form-control @error($fieldError('hero_intro', 'en')) is-invalid @enderror" rows="4" maxlength="600">{{ $slideValue('hero_intro', 'en', 'We empower progressive enterprises with intelligent custom software, 24/7 proactive Managed IT & AMC, robust cloud cybersecurity, and enterprise-grade hardware infrastructure.') }}</textarea>
            @error($fieldError('hero_intro', 'en'))<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Intro Text (BN)</label>
            <textarea name="slides[{{ $index }}][hero_intro][bn]" class="form-control @error($fieldError('hero_intro', 'bn')) is-invalid @enderror" rows="4" maxlength="600">{{ $slideValue('hero_intro', 'bn', 'স্মার্ট সফটওয়্যার, নির্ভরযোগ্য আইটি অবকাঠামো, ক্লাউড আর্কিটেকচার এবং ২৪/৭ এন্টারপ্রাইজ সাপোর্ট সার্ভিসের মাধ্যমে আমরা আপনার ব্যবসাকে সামনের দিকে এগিয়ে নিতে কাজ করি।') }}</textarea>
            @error($fieldError('hero_intro', 'bn'))<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="hero-editor-subhead">Buttons & Reliability Card</div>
    <div class="row g-3 mb-3">
        @foreach([
            ['hero_primary_button_label', 'Primary Button', 60, 'Explore Solutions', 'আমাদের সমাধান দেখুন'],
            ['hero_secondary_button_label', 'Secondary Button', 60, 'Request Consultation', 'সমাধানের জন্য যোগাযোগ'],
            ['hero_card_title', 'Card Title', 120, 'Enterprise IT Reliability', '২৪/৭ এন্টারপ্রাইজ সাপোর্ট'],
            ['hero_card_subtitle', 'Card Subtitle', 160, '99.9% Uptime Guarantee', '১০০% নিশ্চিত আপটাইম এসএলএ'],
        ] as [$key, $label, $maximumLength, $englishDefault, $bengaliDefault])
            @foreach(['en' => ['EN', $englishDefault], 'bn' => ['BN', $bengaliDefault]] as $locale => [$localeLabel, $default])
                <div class="col-md-6 col-xl-3">
                    <label class="form-label">{{ $label }} ({{ $localeLabel }})</label>
                    <input
                        type="text"
                        name="slides[{{ $index }}][{{ $key }}][{{ $locale }}]"
                        class="form-control @error($fieldError($key, $locale)) is-invalid @enderror"
                        value="{{ $slideValue($key, $locale, $default) }}"
                        maxlength="{{ $maximumLength }}"
                    >
                    @error($fieldError($key, $locale))<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
        @endforeach
    </div>

    <div class="hero-editor-subhead">Metric Counters</div>
    <div class="row g-3">
        @foreach([1, 2, 3] as $statNumber)
            @php
                $valueKey = 'hero_stat_'.$statNumber.'_value';
                $labelKey = 'hero_stat_'.$statNumber.'_label';
                $valueDefaults = [1 => ['15+', '১৫+'], 2 => ['200+', '২০০+'], 3 => ['24/7', '২৪/৭']];
                $labelDefaults = [1 => ['Years Exp', 'বছরের অভিজ্ঞতা'], 2 => ['Clients', 'কর্পোরেট ক্লায়েন্ট'], 3 => ['NOC Support', 'সাপোর্ট টিম']];
            @endphp
            <div class="col-lg-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-semibold mb-3">Metric {{ $statNumber }}</div>
                    @foreach(['en' => 0, 'bn' => 1] as $locale => $defaultIndex)
                        <label class="form-label small">Value ({{ strtoupper($locale) }})</label>
                        <input type="text" name="slides[{{ $index }}][{{ $valueKey }}][{{ $locale }}]" class="form-control mb-2 @error($fieldError($valueKey, $locale)) is-invalid @enderror" value="{{ $slideValue($valueKey, $locale, $valueDefaults[$statNumber][$defaultIndex]) }}" maxlength="30">
                        @error($fieldError($valueKey, $locale))<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endforeach
                    @foreach(['en' => 0, 'bn' => 1] as $locale => $defaultIndex)
                        <label class="form-label small">Label ({{ strtoupper($locale) }})</label>
                        <input type="text" name="slides[{{ $index }}][{{ $labelKey }}][{{ $locale }}]" class="form-control mb-2 @error($fieldError($labelKey, $locale)) is-invalid @enderror" value="{{ $slideValue($labelKey, $locale, $labelDefaults[$statNumber][$defaultIndex]) }}" maxlength="60">
                        @error($fieldError($labelKey, $locale))<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
