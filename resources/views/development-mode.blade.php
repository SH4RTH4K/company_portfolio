@php
    $value = fn (string $key, string $fallback): string => trim((string) ($settings[$key] ?? '')) !== '' ? (string) $settings[$key] : $fallback;
    $validColor = fn (string $key, string $fallback): string => preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($settings[$key] ?? '')) ? (string) $settings[$key] : $fallback;
    $storedAssetUrl = fn (?string $path, string $fallback): string => $path ? url(\Illuminate\Support\Facades\Storage::url($path)) : asset($fallback);

    $siteName = $value('site_name', 'Zero Gravities');
    $badge = $value('development_mode_badge', 'Development Mode');
    $title = $value('development_mode_title', 'We are crafting something better');
    $message = $value('development_mode_message', 'Zero Gravities is upgrading the experience. Please check back soon, or contact our team for urgent support.');
    $eta = $value('development_mode_eta', 'Expected back shortly');
    $ctaLabel = $value('development_mode_cta_label', 'Contact Support');
    $ctaUrl = $value('development_mode_cta_url', 'mailto:info@zerogravities.com');
    $design = in_array(($settings['development_mode_design'] ?? 'orbit'), ['orbit', 'focus', 'light'], true) ? $settings['development_mode_design'] : 'orbit';
    $backgroundColor = $validColor('development_mode_background_color', '#0d1b2a');
    $accentColor = $validColor('development_mode_accent_color', '#00b4d8');
    $textColor = $validColor('development_mode_text_color', '#ffffff');
    $faviconUrl = $storedAssetUrl($settings['favicon'] ?? null, 'favicon.ico');
    $logoUrl = $storedAssetUrl($settings['logo'] ?? null, 'images/logo.png');
    $safeCtaUrl = preg_match('/^(https?:\/\/|mailto:|tel:|\/(?!\/))/', $ctaUrl) ? $ctaUrl : 'mailto:info@zerogravities.com';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | {{ $siteName }}</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --page-bg: {{ $backgroundColor }};
            --page-accent: {{ $accentColor }};
            --page-text: {{ $textColor }};
            --page-muted: color-mix(in srgb, var(--page-text) 68%, transparent);
            --panel-bg: color-mix(in srgb, var(--page-bg) 78%, #ffffff 10%);
            --panel-line: color-mix(in srgb, var(--page-accent) 32%, transparent);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            color: var(--page-text);
            background:
                radial-gradient(circle at 76% 20%, color-mix(in srgb, var(--page-accent) 22%, transparent), transparent 30%),
                radial-gradient(circle at 20% 84%, color-mix(in srgb, var(--page-accent) 12%, transparent), transparent 28%),
                linear-gradient(135deg, color-mix(in srgb, var(--page-bg) 92%, #000 8%), var(--page-bg));
            overflow-x: hidden;
        }

        .development-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: clamp(18px, 3vw, 34px);
            position: relative;
            isolation: isolate;
        }

        .development-page::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -2;
            background-image:
                linear-gradient(color-mix(in srgb, var(--page-text) 8%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in srgb, var(--page-text) 8%, transparent) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: linear-gradient(to bottom, transparent, #000 18%, #000 78%, transparent);
        }

        .development-page::after {
            content: "";
            position: absolute;
            inset: 7% 8%;
            z-index: -1;
            border: 1px solid color-mix(in srgb, var(--page-accent) 18%, transparent);
            transform: skewY(-6deg);
            opacity: .75;
        }

        .development-shell {
            width: min(1180px, 100%);
            display: grid;
            grid-template-columns: minmax(0, 1.04fr) minmax(340px, .72fr);
            gap: clamp(18px, 2.6vw, 34px);
            align-items: stretch;
        }

        .message-panel,
        .signal-panel {
            border: 1px solid var(--panel-line);
            background: linear-gradient(145deg, color-mix(in srgb, var(--panel-bg) 92%, transparent), color-mix(in srgb, var(--page-bg) 82%, transparent));
            box-shadow: 0 24px 70px rgba(0, 0, 0, .24);
            backdrop-filter: blur(16px);
        }

        .message-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: clamp(28px, 4vw, 46px);
            border-radius: 8px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            margin-bottom: clamp(28px, 4vw, 42px);
            font-weight: 800;
            font-size: clamp(1.08rem, 2vw, 1.34rem);
        }

        .brand img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 12px;
            background: color-mix(in srgb, var(--page-text) 92%, transparent);
            box-shadow: 0 12px 30px rgba(0, 0, 0, .16);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            margin-bottom: 22px;
            border: 1px solid color-mix(in srgb, var(--page-accent) 56%, transparent);
            color: var(--page-accent);
            background: color-mix(in srgb, var(--page-accent) 12%, transparent);
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 700;
        }

        h1 {
            margin: 0;
            max-width: 680px;
            font-size: clamp(2.25rem, 5.2vw, 4.15rem);
            line-height: 1.05;
            letter-spacing: 0;
        }

        .message {
            max-width: 620px;
            margin: 22px 0 0;
            color: var(--page-muted);
            font-size: clamp(1rem, 1.35vw, 1.14rem);
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: center;
            margin-top: 30px;
        }

        .cta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 8px;
            background: var(--page-accent);
            color: #06111f;
            text-decoration: none;
            font-weight: 800;
            box-shadow: 0 16px 36px color-mix(in srgb, var(--page-accent) 32%, transparent);
        }

        .eta {
            color: var(--page-muted);
            font-weight: 700;
        }

        .signal-panel {
            position: relative;
            overflow: hidden;
            min-height: min(620px, calc(100vh - 68px));
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: clamp(28px, 4vw, 44px);
        }

        .signal-panel::before,
        .signal-panel::after {
            content: "";
            position: absolute;
            inset: 32px;
            border: 1px solid color-mix(in srgb, var(--page-accent) 42%, transparent);
            transform: rotate(-10deg);
        }

        .signal-panel::after {
            inset: 74px;
            transform: rotate(10deg);
            border-color: color-mix(in srgb, var(--page-text) 20%, transparent);
        }

        .signal-core {
            position: relative;
            z-index: 1;
            width: min(220px, 58vw);
            aspect-ratio: 1;
            display: grid;
            place-items: center;
            border: 1px solid color-mix(in srgb, var(--page-accent) 48%, transparent);
            border-radius: 50%;
            background: color-mix(in srgb, var(--page-bg) 70%, #ffffff 6%);
            align-self: center;
            box-shadow: inset 0 0 40px color-mix(in srgb, var(--page-accent) 10%, transparent);
        }

        .signal-core i {
            font-size: clamp(3.6rem, 9vw, 5.7rem);
            color: var(--page-accent);
        }

        .signal-copy {
            position: relative;
            z-index: 1;
            max-width: 320px;
            margin: 28px auto 0;
            text-align: center;
        }

        .signal-copy strong {
            display: block;
            margin-bottom: 8px;
            font-size: 1.05rem;
        }

        .signal-copy span {
            color: var(--page-muted);
            font-size: .94rem;
            line-height: 1.55;
        }

        .stage-line {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 30px;
        }

        .stage-line span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            border-top: 2px solid color-mix(in srgb, var(--page-accent) 58%, transparent);
            color: var(--page-muted);
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .development-page.design-focus .signal-panel::before,
        .development-page.design-focus .signal-panel::after {
            transform: none;
            border-radius: 8px;
        }

        .development-page.design-light {
            --page-bg: #f6f9fc;
            --page-text: #0d1b2a;
            --panel-bg: #ffffff;
        }

        .development-page.design-light .message-panel,
        .development-page.design-light .signal-panel {
            box-shadow: 0 22px 60px rgba(13, 27, 42, .12);
        }

        @media (max-width: 860px) {
            .development-shell {
                grid-template-columns: 1fr;
            }

            .signal-panel {
                min-height: 300px;
            }

            .brand {
                margin-bottom: 34px;
            }
        }

        @media (max-width: 560px) {
            .message-panel,
            .signal-panel {
                padding: 24px;
            }

            .actions {
                align-items: stretch;
                flex-direction: column;
            }

            .cta {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="development-page design-{{ $design }}">
        <section class="development-shell" aria-label="Development mode notice">
            <div class="message-panel">
                <div class="brand">
                    @if(!empty($settings['logo']))
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}">
                    @else
                        <img src="{{ $faviconUrl }}" alt="{{ $siteName }}">
                    @endif
                    <span>{{ $siteName }}</span>
                </div>

                <div class="badge">
                    <i class="bi bi-stars"></i>
                    <span>{{ $badge }}</span>
                </div>

                <h1>{{ $title }}</h1>
                <p class="message">{{ $message }}</p>

                <div class="actions">
                    <a class="cta" href="{{ $safeCtaUrl }}">
                        <i class="bi bi-send-fill"></i>
                        <span>{{ $ctaLabel }}</span>
                    </a>
                    <span class="eta">{{ $eta }}</span>
                </div>
            </div>

            <aside class="signal-panel" aria-hidden="true">
                <div class="signal-core">
                    <i class="bi bi-code-slash"></i>
                </div>
                <div class="signal-copy">
                    <strong>Upgrade in progress</strong>
                    <span>Our team is refining performance, content, and service details before reopening the public site.</span>
                </div>
                <div class="stage-line">
                    <span>Build</span>
                    <span>QA</span>
                    <span>Launch</span>
                </div>
            </aside>
        </section>
    </main>
</body>
</html>
