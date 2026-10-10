@extends('admin.layouts.admin')

@section('title', 'Homepage Hero')

@section('content')
<style>
    .hero-editor-shell {
        --hero-editor-border: #d8e0ea;
        --hero-editor-muted: #667085;
    }

    .hero-editor-hero {
        background: linear-gradient(135deg, #0d1b2a 0%, #123047 100%);
        border-radius: 8px;
        color: #fff;
        padding: 1.25rem;
    }

    .hero-editor-section {
        background: #fff;
        border: 1px solid var(--hero-editor-border);
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
        margin-bottom: 1rem;
        padding: 1.25rem;
    }

    .hero-editor-section-header {
        align-items: flex-start;
        border-bottom: 1px solid var(--hero-editor-border);
        display: flex;
        gap: 0.85rem;
        margin-bottom: 1.25rem;
        padding-bottom: 0.9rem;
    }

    .hero-editor-section-icon {
        align-items: center;
        background: #eef4ff;
        border-radius: 8px;
        color: #0d6efd;
        display: inline-flex;
        flex: 0 0 2.4rem;
        height: 2.4rem;
        justify-content: center;
        width: 2.4rem;
    }

    .hero-editor-section-header p {
        color: var(--hero-editor-muted);
        margin-bottom: 0;
    }

    .hero-editor-subhead {
        color: #111827;
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 0.85rem;
        margin-top: 1rem;
    }

    .hero-editor-savebar {
        align-items: center;
        background: #fff;
        border: 1px solid var(--hero-editor-border);
        border-radius: 8px;
        bottom: 1rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        display: flex;
        justify-content: space-between;
        margin-top: 1rem;
        padding: 0.85rem 1rem;
        position: sticky;
        z-index: 10;
    }
</style>

<div class="hero-editor-shell">
    <form action="{{ route('admin.homepage-hero.update') }}" method="POST">
        @csrf

        <div class="hero-editor-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Homepage Hero</h4>
                    <p class="mb-0 text-white-50">Control the first-screen headline, intro text, calls to action, reliability card, and metric counters.</p>
                </div>
                <button type="submit" class="btn btn-light fw-semibold align-self-start">
                    <i class="bi bi-check2-circle me-1"></i> Save Hero
                </button>
            </div>
        </div>

        <section class="hero-editor-section">
            <div class="hero-editor-section-header">
                <span class="hero-editor-section-icon"><i class="bi bi-stars"></i></span>
                <div>
                    <h5 class="fw-bold mb-1">Hero Content</h5>
                    <p>Manage the text shown in the public homepage hero area.</p>
                </div>
            </div>

            <div class="hero-editor-subhead">Badge & Headline</div>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Badge Label (EN)</label>
                    <input type="text" name="hero_badge_label[en]" class="form-control @error('hero_badge_label.en') is-invalid @enderror" value="{{ old('hero_badge_label.en', $settings['hero_badge_label'] ?? 'Zero Gravities') }}" maxlength="60">
                    @error('hero_badge_label.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Badge Label (BN)</label>
                    <input type="text" name="hero_badge_label[bn]" class="form-control @error('hero_badge_label.bn') is-invalid @enderror" value="{{ old('hero_badge_label.bn', $settingsBn['hero_badge_label'] ?? '') }}" maxlength="60">
                    @error('hero_badge_label.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Badge Text (EN)</label>
                    <input type="text" name="hero_badge_text[en]" class="form-control @error('hero_badge_text.en') is-invalid @enderror" value="{{ old('hero_badge_text.en', $settings['hero_badge_text'] ?? 'Technology • Solutions • Commerce') }}" maxlength="120">
                    @error('hero_badge_text.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Badge Text (BN)</label>
                    <input type="text" name="hero_badge_text[bn]" class="form-control @error('hero_badge_text.bn') is-invalid @enderror" value="{{ old('hero_badge_text.bn', $settingsBn['hero_badge_text'] ?? '') }}" maxlength="120">
                    @error('hero_badge_text.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Headline Main (EN)</label>
                    <input type="text" name="hero_title_prefix[en]" class="form-control @error('hero_title_prefix.en') is-invalid @enderror" value="{{ old('hero_title_prefix.en', $settings['hero_title_prefix'] ?? 'Technology Without Limits,') }}" maxlength="120">
                    @error('hero_title_prefix.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Headline Main (BN)</label>
                    <input type="text" name="hero_title_prefix[bn]" class="form-control @error('hero_title_prefix.bn') is-invalid @enderror" value="{{ old('hero_title_prefix.bn', $settingsBn['hero_title_prefix'] ?? '') }}" maxlength="120">
                    @error('hero_title_prefix.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Headline Accent (EN)</label>
                    <input type="text" name="hero_title_accent[en]" class="form-control @error('hero_title_accent.en') is-invalid @enderror" value="{{ old('hero_title_accent.en', $settings['hero_title_accent'] ?? 'Built For Growth') }}" maxlength="120">
                    @error('hero_title_accent.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Headline Accent (BN)</label>
                    <input type="text" name="hero_title_accent[bn]" class="form-control @error('hero_title_accent.bn') is-invalid @enderror" value="{{ old('hero_title_accent.bn', $settingsBn['hero_title_accent'] ?? '') }}" maxlength="120">
                    @error('hero_title_accent.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Intro Text (EN)</label>
                    <textarea name="hero_intro[en]" class="form-control @error('hero_intro.en') is-invalid @enderror" rows="4" maxlength="600">{{ old('hero_intro.en', $settings['hero_intro'] ?? 'We empower progressive enterprises with intelligent custom software, 24/7 proactive Managed IT & AMC, robust cloud cybersecurity, and enterprise-grade hardware infrastructure.') }}</textarea>
                    @error('hero_intro.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Intro Text (BN)</label>
                    <textarea name="hero_intro[bn]" class="form-control @error('hero_intro.bn') is-invalid @enderror" rows="4" maxlength="600">{{ old('hero_intro.bn', $settingsBn['hero_intro'] ?? '') }}</textarea>
                    @error('hero_intro.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="hero-editor-subhead">Buttons & Reliability Card</div>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Primary Button (EN)</label>
                    <input type="text" name="hero_primary_button_label[en]" class="form-control @error('hero_primary_button_label.en') is-invalid @enderror" value="{{ old('hero_primary_button_label.en', $settings['hero_primary_button_label'] ?? 'Explore Solutions') }}" maxlength="60">
                    @error('hero_primary_button_label.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Primary Button (BN)</label>
                    <input type="text" name="hero_primary_button_label[bn]" class="form-control @error('hero_primary_button_label.bn') is-invalid @enderror" value="{{ old('hero_primary_button_label.bn', $settingsBn['hero_primary_button_label'] ?? '') }}" maxlength="60">
                    @error('hero_primary_button_label.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Secondary Button (EN)</label>
                    <input type="text" name="hero_secondary_button_label[en]" class="form-control @error('hero_secondary_button_label.en') is-invalid @enderror" value="{{ old('hero_secondary_button_label.en', $settings['hero_secondary_button_label'] ?? 'Request Consultation') }}" maxlength="60">
                    @error('hero_secondary_button_label.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Secondary Button (BN)</label>
                    <input type="text" name="hero_secondary_button_label[bn]" class="form-control @error('hero_secondary_button_label.bn') is-invalid @enderror" value="{{ old('hero_secondary_button_label.bn', $settingsBn['hero_secondary_button_label'] ?? '') }}" maxlength="60">
                    @error('hero_secondary_button_label.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Card Title (EN)</label>
                    <input type="text" name="hero_card_title[en]" class="form-control @error('hero_card_title.en') is-invalid @enderror" value="{{ old('hero_card_title.en', $settings['hero_card_title'] ?? 'Enterprise IT Reliability') }}" maxlength="120">
                    @error('hero_card_title.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Card Title (BN)</label>
                    <input type="text" name="hero_card_title[bn]" class="form-control @error('hero_card_title.bn') is-invalid @enderror" value="{{ old('hero_card_title.bn', $settingsBn['hero_card_title'] ?? '') }}" maxlength="120">
                    @error('hero_card_title.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Card Subtitle (EN)</label>
                    <input type="text" name="hero_card_subtitle[en]" class="form-control @error('hero_card_subtitle.en') is-invalid @enderror" value="{{ old('hero_card_subtitle.en', $settings['hero_card_subtitle'] ?? '99.9% Uptime Guarantee') }}" maxlength="160">
                    @error('hero_card_subtitle.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Card Subtitle (BN)</label>
                    <input type="text" name="hero_card_subtitle[bn]" class="form-control @error('hero_card_subtitle.bn') is-invalid @enderror" value="{{ old('hero_card_subtitle.bn', $settingsBn['hero_card_subtitle'] ?? '') }}" maxlength="160">
                    @error('hero_card_subtitle.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="hero-editor-subhead">Metric Counters</div>
            <div class="row g-3">
                @foreach([1, 2, 3] as $statNumber)
                    @php
                        $defaultStatValues = [1 => '15+', 2 => '200+', 3 => '24/7'];
                        $defaultStatLabels = [1 => 'Years Exp', 2 => 'Clients', 3 => 'NOC Support'];
                        $valueKey = 'hero_stat_'.$statNumber.'_value';
                        $labelKey = 'hero_stat_'.$statNumber.'_label';
                    @endphp
                    <div class="col-lg-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="fw-semibold mb-3">Metric {{ $statNumber }}</div>
                            <label class="form-label small">Value (EN)</label>
                            <input type="text" name="{{ $valueKey }}[en]" class="form-control mb-2 @error($valueKey.'.en') is-invalid @enderror" value="{{ old($valueKey.'.en', $settings[$valueKey] ?? $defaultStatValues[$statNumber]) }}" maxlength="30">
                            @error($valueKey.'.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <label class="form-label small">Value (BN)</label>
                            <input type="text" name="{{ $valueKey }}[bn]" class="form-control mb-2 @error($valueKey.'.bn') is-invalid @enderror" value="{{ old($valueKey.'.bn', $settingsBn[$valueKey] ?? '') }}" maxlength="30">
                            @error($valueKey.'.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <label class="form-label small">Label (EN)</label>
                            <input type="text" name="{{ $labelKey }}[en]" class="form-control mb-2 @error($labelKey.'.en') is-invalid @enderror" value="{{ old($labelKey.'.en', $settings[$labelKey] ?? $defaultStatLabels[$statNumber]) }}" maxlength="60">
                            @error($labelKey.'.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <label class="form-label small">Label (BN)</label>
                            <input type="text" name="{{ $labelKey }}[bn]" class="form-control @error($labelKey.'.bn') is-invalid @enderror" value="{{ old($labelKey.'.bn', $settingsBn[$labelKey] ?? '') }}" maxlength="60">
                            @error($labelKey.'.bn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="hero-editor-savebar">
            <div>
                <div class="fw-bold">Ready to update the homepage hero?</div>
                <div class="small text-muted">This saves only the Homepage Hero content.</div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle me-1"></i> Save Hero
            </button>
        </div>
    </form>
</div>
@endsection
