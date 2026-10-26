@extends('admin.layouts.admin')

@section('title', 'Homepage Hero Slides')

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

@php
    $slidesForForm = old('slides', $slides);
    $blankSlide = [
        'id' => null,
        'hero_badge_label' => ['en' => 'Zero Gravities', 'bn' => 'জিরো গ্র্যাভিটিস'],
        'hero_badge_text' => ['en' => 'Technology • Solutions • Commerce', 'bn' => 'প্রযুক্তি • সমাধান • বাণিজ্য'],
        'hero_title_prefix' => ['en' => 'Technology Without Limits,', 'bn' => 'সীমাহীন প্রযুক্তি,'],
        'hero_title_accent' => ['en' => 'Built For Growth', 'bn' => 'আধুনিক ব্যবসায়িক সমাধান'],
        'hero_intro' => ['en' => 'We empower progressive enterprises with intelligent custom software, 24/7 proactive Managed IT & AMC, robust cloud cybersecurity, and enterprise-grade hardware infrastructure.', 'bn' => 'স্মার্ট সফটওয়্যার, নির্ভরযোগ্য আইটি অবকাঠামো, ক্লাউড আর্কিটেকচার এবং ২৪/৭ এন্টারপ্রাইজ সাপোর্ট সার্ভিসের মাধ্যমে আমরা আপনার ব্যবসাকে সামনের দিকে এগিয়ে নিতে কাজ করি।'],
        'hero_primary_button_label' => ['en' => 'Explore Solutions', 'bn' => 'আমাদের সমাধান দেখুন'],
        'hero_secondary_button_label' => ['en' => 'Request Consultation', 'bn' => 'সমাধানের জন্য যোগাযোগ'],
        'hero_card_title' => ['en' => 'Enterprise IT Reliability', 'bn' => '২৪/৭ এন্টারপ্রাইজ সাপোর্ট'],
        'hero_card_subtitle' => ['en' => '99.9% Uptime Guarantee', 'bn' => '১০০% নিশ্চিত আপটাইম এসএলএ'],
        'hero_stat_1_value' => ['en' => '15+', 'bn' => '১৫+'],
        'hero_stat_1_label' => ['en' => 'Years Exp', 'bn' => 'বছরের অভিজ্ঞতা'],
        'hero_stat_2_value' => ['en' => '200+', 'bn' => '২০০+'],
        'hero_stat_2_label' => ['en' => 'Clients', 'bn' => 'কর্পোরেট ক্লায়েন্ট'],
        'hero_stat_3_value' => ['en' => '24/7', 'bn' => '২৪/৭'],
        'hero_stat_3_label' => ['en' => 'NOC Support', 'bn' => 'সাপোর্ট টিম'],
    ];
@endphp

<div class="hero-editor-shell">
    <form action="{{ route('admin.homepage-hero.update') }}" method="POST" id="homepage-hero-form">
        @csrf

        <div class="hero-editor-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Homepage Hero Slides</h4>
                    <p class="mb-0 text-white-50">Add, remove, and reorder homepage heroes. With one slide, the homepage stays static; with two or more, the slider starts automatically.</p>
                </div>
                <div class="d-flex flex-wrap align-items-end gap-2 align-self-start">
                    <div>
                        <label for="slider_interval_seconds" class="form-label small text-white mb-1">Transition time (seconds)</label>
                        <input
                            type="number"
                            id="slider_interval_seconds"
                            name="slider_interval_seconds"
                            class="form-control @error('slider_interval_seconds') is-invalid @enderror"
                            value="{{ old('slider_interval_seconds', $sliderIntervalSeconds) }}"
                            min="3"
                            max="30"
                            step="1"
                            required
                        >
                        @error('slider_interval_seconds')<div class="invalid-feedback bg-white rounded px-2">{{ $message }}</div>@enderror
                    </div>
                    <button type="button" class="btn btn-outline-light fw-semibold" id="add-hero-slide">
                        <i class="bi bi-plus-circle me-1"></i> Add Slide
                    </button>
                    <button type="submit" class="btn btn-light fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Save Slides
                    </button>
                </div>
            </div>
        </div>

        @if($errors->has('slides'))
            <div class="alert alert-danger">{{ $errors->first('slides') }}</div>
        @endif

        <div id="hero-slides">
            @foreach($slidesForForm as $index => $slide)
                @include('admin.homepage_hero._slide', ['index' => $index, 'slide' => $slide])
            @endforeach
        </div>

        <div class="hero-editor-savebar">
            <div>
                <div class="fw-bold">Ready to update the homepage heroes?</div>
                <div class="small text-muted">Slide order here is the order visitors will see.</div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle me-1"></i> Save Slides
            </button>
        </div>
    </form>
</div>

<template id="hero-slide-template">
    @include('admin.homepage_hero._slide', ['index' => '__INDEX__', 'slide' => $blankSlide])
</template>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const slidesContainer = document.getElementById('hero-slides');
        const slideTemplate = document.getElementById('hero-slide-template');
        const addSlideButton = document.getElementById('add-hero-slide');

        const refreshSlides = () => {
            const slides = Array.from(slidesContainer.querySelectorAll('[data-hero-slide]'));

            slides.forEach((slide, index) => {
                slide.querySelector('[data-slide-number]').textContent = index + 1;
                slide.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/slides\[[^\]]+\]/, `slides[${index}]`);
                });

                slide.querySelector('[data-move-slide="up"]').disabled = index === 0;
                slide.querySelector('[data-move-slide="down"]').disabled = index === slides.length - 1;
                slide.querySelector('[data-remove-slide]').disabled = slides.length === 1;
            });

            addSlideButton.disabled = slides.length >= 10;
        };

        addSlideButton.addEventListener('click', () => {
            const index = slidesContainer.querySelectorAll('[data-hero-slide]').length;
            slidesContainer.insertAdjacentHTML('beforeend', slideTemplate.innerHTML.replaceAll('__INDEX__', index));
            refreshSlides();
        });

        slidesContainer.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-remove-slide]');
            const moveButton = event.target.closest('[data-move-slide]');
            const slide = event.target.closest('[data-hero-slide]');

            if (! slide) {
                return;
            }

            if (removeButton && slidesContainer.querySelectorAll('[data-hero-slide]').length > 1) {
                slide.remove();
            }

            if (moveButton?.dataset.moveSlide === 'up' && slide.previousElementSibling) {
                slidesContainer.insertBefore(slide, slide.previousElementSibling);
            }

            if (moveButton?.dataset.moveSlide === 'down' && slide.nextElementSibling) {
                slidesContainer.insertBefore(slide.nextElementSibling, slide);
            }

            refreshSlides();
        });

        refreshSlides();
    });
</script>
@endsection
