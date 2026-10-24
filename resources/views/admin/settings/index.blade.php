@extends('admin.layouts.admin')

@section('title', 'Global Settings')

@section('content')
<style>
    .settings-shell {
        --settings-border: #d8e0ea;
        --settings-muted: #667085;
    }

    .settings-hero {
        background: linear-gradient(135deg, #0d1b2a 0%, #123047 100%);
        border-radius: 8px;
        color: #fff;
        padding: 1.25rem;
    }

    .settings-section {
        background: #fff;
        border: 1px solid var(--settings-border);
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
        margin-bottom: 1rem;
        padding: 1.25rem;
    }

    .settings-section-header {
        align-items: flex-start;
        border-bottom: 1px solid var(--settings-border);
        display: flex;
        gap: 0.85rem;
        margin-bottom: 1.25rem;
        padding-bottom: 0.9rem;
    }

    .settings-section-icon {
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

    .settings-section-header p {
        color: var(--settings-muted);
        margin-bottom: 0;
    }

    .settings-subhead {
        color: #111827;
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 0.85rem;
        margin-top: 1rem;
    }

    .settings-savebar {
        align-items: center;
        background: #fff;
        border: 1px solid var(--settings-border);
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

<div class="settings-shell">
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="settings-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Site Settings</h4>
                    <p class="mb-0 text-white-50">Manage company details, navigation, mail delivery, development mode, brand assets, and social links from one control panel.</p>
                </div>
                <button type="submit" class="btn btn-light fw-semibold align-self-start">
                    <i class="bi bi-check2-circle me-1"></i> Save Settings
                </button>
            </div>
        </div>

        <div>
                <section id="company-settings" class="settings-section">
                    <div class="settings-section-header">
                        <span class="settings-section-icon"><i class="bi bi-building"></i></span>
                        <div>
                            <h5 class="fw-bold mb-1">Company & Contact</h5>
                            <p>Public company name, contact details, address, and default language.</p>
                        </div>
                    </div>

                    <div class="settings-subhead">Company Information</div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Company Name (EN)</label>
                    <input type="text" name="company_name[en]" class="form-control" value="{{ $settings['company_name'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Name (BN)</label>
                    <input type="text" name="company_name[bn]" class="form-control" value="{{ $settingsBn['company_name'] ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tagline (EN)</label>
                    <input type="text" name="tagline[en]" class="form-control" value="{{ $settings['tagline'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tagline (BN)</label>
                    <input type="text" name="tagline[bn]" class="form-control" value="{{ $settingsBn['tagline'] ?? '' }}">
                </div>
            </div>

                    <div class="settings-subhead">Contact Details</div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Primary Phone</label>
                    <input type="text" name="phone[en]" class="form-control" value="{{ $settings['phone'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="whatsapp[en]" class="form-control" value="{{ $settings['whatsapp'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">General Email</label>
                    <input type="email" name="email[en]" class="form-control" value="{{ $settings['email'] ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Address (EN)</label>
                    <textarea name="address[en]" class="form-control" rows="2">{{ $settings['address'] ?? '' }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Address (BN)</label>
                    <textarea name="address[bn]" class="form-control" rows="2">{{ $settingsBn['address'] ?? '' }}</textarea>
                </div>
            </div>

                </section>

                <section id="navigation-settings" class="settings-section">
                    <div class="settings-section-header">
                        <span class="settings-section-icon"><i class="bi bi-list"></i></span>
                        <div>
                            <h5 class="fw-bold mb-1">Navigation</h5>
                            <p>Choose which menu items appear in the header, mobile menu, and footer in English and Bangla.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        @foreach($navigationItems as $item => $label)
                            <div class="col-md-6 col-xl-4">
                                <input type="hidden" name="menu_visibility[{{ $item }}]" value="0">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input @error('menu_visibility.'.$item) is-invalid @enderror" role="switch" id="menu-item-{{ $item }}" name="menu_visibility[{{ $item }}]" value="1" @checked(old('menu_visibility.'.$item, $settings['menu_'.$item.'_visible'] ?? '1') == '1')>
                                    <label class="form-check-label" for="menu-item-{{ $item }}">{{ $label }}</label>
                                    @error('menu_visibility.'.$item)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('menu_visibility')<div class="text-danger small mt-3">{{ $message }}</div>@enderror
                    <p class="form-text mb-0 mt-3">Turn off an item to hide its navigation links, then click Save Settings. Pages remain accessible by direct link. Turn it on again to restore it.</p>
                </section>

                <section id="email-settings" class="settings-section">
                    <div class="settings-section-header">
                        <span class="settings-section-icon"><i class="bi bi-envelope-at"></i></span>
                        <div>
                            <h5 class="fw-bold mb-1">Email Integration</h5>
                            <p>Configure how contact notifications and customer replies are sent.</p>
                        </div>
                    </div>
            <div class="border rounded-3 p-3 mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Mailer</label>
                        <select name="mail_mailer" class="form-select @error('mail_mailer') is-invalid @enderror">
                            @foreach(['log' => 'Log Only', 'smtp' => 'SMTP Email'] as $mailerValue => $mailerLabel)
                                <option value="{{ $mailerValue }}" {{ old('mail_mailer', $settings['mail_mailer'] ?? config('mail.default', 'log')) === $mailerValue ? 'selected' : '' }}>{{ $mailerLabel }}</option>
                            @endforeach
                        </select>
                        @error('mail_mailer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Use Log Only for development/testing.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="mail_host" class="form-control @error('mail_host') is-invalid @enderror" value="{{ old('mail_host', $settings['mail_host'] ?? config('mail.mailers.smtp.host')) }}">
                        @error('mail_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Port</label>
                        <input type="number" name="mail_port" class="form-control @error('mail_port') is-invalid @enderror" value="{{ old('mail_port', $settings['mail_port'] ?? config('mail.mailers.smtp.port', 587)) }}" min="1" max="65535">
                        @error('mail_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Connection Security</label>
                        <select name="mail_scheme" class="form-select @error('mail_scheme') is-invalid @enderror">
                            @foreach(['auto' => 'Auto / STARTTLS when available', 'smtp' => 'SMTP / STARTTLS', 'smtps' => 'SMTPS / SSL'] as $schemeValue => $schemeLabel)
                                <option value="{{ $schemeValue }}" {{ old('mail_scheme', $settings['mail_scheme'] ?? 'auto') === $schemeValue ? 'selected' : '' }}>{{ $schemeLabel }}</option>
                            @endforeach
                        </select>
                        @error('mail_scheme')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="mail_username" class="form-control @error('mail_username') is-invalid @enderror" value="{{ old('mail_username', $settings['mail_username'] ?? '') }}" autocomplete="off">
                        @error('mail_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="mail_password" class="form-control @error('mail_password') is-invalid @enderror" value="" autocomplete="new-password" placeholder="{{ $mailPasswordConfigured ? 'Saved password will be kept' : 'Enter SMTP password or app password' }}">
                        @error('mail_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($mailPasswordConfigured)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_mail_password" value="1" id="remove_mail_password">
                                <label class="form-check-label" for="remove_mail_password">Clear saved SMTP password</label>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">From Email</label>
                        <input type="email" name="mail_from_address" class="form-control @error('mail_from_address') is-invalid @enderror" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? config('mail.from.address')) }}">
                        @error('mail_from_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">From Name</label>
                        <input type="text" name="mail_from_name" class="form-control @error('mail_from_name') is-invalid @enderror" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? config('mail.from.name')) }}">
                        @error('mail_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Admin Notification Email</label>
                        <input type="email" name="mail_admin_notification_address" class="form-control @error('mail_admin_notification_address') is-invalid @enderror" value="{{ old('mail_admin_notification_address', $settings['mail_admin_notification_address'] ?? ($settings['mail_from_address'] ?? config('mail.from.address'))) }}">
                        @error('mail_admin_notification_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">New contact messages are sent here.</div>
                    </div>
                </div>
            </div>

                </section>

                <section id="language-settings" class="settings-section">
                    <div class="settings-section-header">
                        <span class="settings-section-icon"><i class="bi bi-translate"></i></span>
                        <div>
                            <h5 class="fw-bold mb-1">Language & Theme</h5>
                            <p>Choose the default public language and basic display behavior.</p>
                        </div>
                    </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Default Language</label>
                    <select name="default_language" class="form-select">
                        <option value="en" {{ ($settings['default_language'] ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                        <option value="bn" {{ ($settings['default_language'] ?? 'en') === 'bn' ? 'selected' : '' }}>Bangla</option>
                    </select>
                </div>
            </div>

                </section>

                <section id="development-settings" class="settings-section">
                    <div class="settings-section-header">
                        <span class="settings-section-icon"><i class="bi bi-code-square"></i></span>
                        <div>
                            <h5 class="fw-bold mb-1">Development Mode Page</h5>
                            <p>Control the temporary public message when the website is under development.</p>
                        </div>
                    </div>
            <div class="border rounded-3 p-3 mb-3">
                @php
                    $developmentModeDesign = $settings['development_mode_design'] ?? 'orbit';
                    $developmentModeBackground = $settings['development_mode_background_color'] ?? '#0d1b2a';
                    $developmentModeAccent = $settings['development_mode_accent_color'] ?? '#00b4d8';
                    $developmentModeText = $settings['development_mode_text_color'] ?? '#ffffff';
                @endphp
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="development_mode_enabled" value="1" id="development_mode_enabled" {{ old('development_mode_enabled', ($settings['development_mode_enabled'] ?? '0') === '1') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="development_mode_enabled">Enable development mode for public website</label>
                        </div>
                        <div class="form-text">Admin pages, storage assets, build assets, and health check remain accessible.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Badge Text</label>
                        <input type="text" name="development_mode_badge" class="form-control @error('development_mode_badge') is-invalid @enderror" value="{{ old('development_mode_badge', $settings['development_mode_badge'] ?? 'Development Mode') }}" maxlength="50">
                        @error('development_mode_badge')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small">Title</label>
                        <input type="text" name="development_mode_title" class="form-control @error('development_mode_title') is-invalid @enderror" value="{{ old('development_mode_title', $settings['development_mode_title'] ?? 'We are crafting something better') }}" maxlength="100">
                        @error('development_mode_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Message</label>
                        <textarea name="development_mode_message" class="form-control @error('development_mode_message') is-invalid @enderror" rows="3" maxlength="500">{{ old('development_mode_message', $settings['development_mode_message'] ?? 'Zero Gravities is upgrading the experience. Please check back soon, or contact our team for urgent support.') }}</textarea>
                        @error('development_mode_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Status / Time Note</label>
                        <input type="text" name="development_mode_eta" class="form-control @error('development_mode_eta') is-invalid @enderror" value="{{ old('development_mode_eta', $settings['development_mode_eta'] ?? 'Expected back shortly') }}" maxlength="120">
                        @error('development_mode_eta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Button Text</label>
                        <input type="text" name="development_mode_cta_label" class="form-control @error('development_mode_cta_label') is-invalid @enderror" value="{{ old('development_mode_cta_label', $settings['development_mode_cta_label'] ?? 'Contact Support') }}" maxlength="60">
                        @error('development_mode_cta_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Button URL</label>
                        <input type="text" name="development_mode_cta_url" class="form-control @error('development_mode_cta_url') is-invalid @enderror" value="{{ old('development_mode_cta_url', $settings['development_mode_cta_url'] ?? 'mailto:info@zerogravities.com') }}" maxlength="255">
                        @error('development_mode_cta_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Design Style</label>
                        <select name="development_mode_design" class="form-select @error('development_mode_design') is-invalid @enderror">
                            @foreach(['orbit' => 'Orbit Glow', 'focus' => 'Corporate Focus', 'light' => 'Clean Light'] as $designKey => $designLabel)
                                <option value="{{ $designKey }}" {{ old('development_mode_design', $developmentModeDesign) === $designKey ? 'selected' : '' }}>{{ $designLabel }}</option>
                            @endforeach
                        </select>
                        @error('development_mode_design')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Background Color</label>
                        <input type="color" name="development_mode_background_color" class="form-control form-control-color w-100 @error('development_mode_background_color') is-invalid @enderror" value="{{ old('development_mode_background_color', $developmentModeBackground) }}">
                        @error('development_mode_background_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Accent Color</label>
                        <input type="color" name="development_mode_accent_color" class="form-control form-control-color w-100 @error('development_mode_accent_color') is-invalid @enderror" value="{{ old('development_mode_accent_color', $developmentModeAccent) }}">
                        @error('development_mode_accent_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Text Color</label>
                        <input type="color" name="development_mode_text_color" class="form-control form-control-color w-100 @error('development_mode_text_color') is-invalid @enderror" value="{{ old('development_mode_text_color', $developmentModeText) }}">
                        @error('development_mode_text_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

                </section>

                <section id="brand-settings" class="settings-section">
                    <div class="settings-section-header">
                        <span class="settings-section-icon"><i class="bi bi-image"></i></span>
                        <div>
                            <h5 class="fw-bold mb-1">Brand & Logo</h5>
                            <p>Upload logo assets, tune logo sizing, and manage the text logo fallback.</p>
                        </div>
                    </div>
            <div class="row g-4 mb-3">
                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 h-100">
                        <label class="form-label fw-semibold">Header Logo</label>
                        @if(!empty($settings['logo']))
                            @php
                                $logoPreviewBackground = $settings['logo_background_color'] ?? 'transparent';
                                $logoPreviewStyle = 'background-color: ' . $logoPreviewBackground . '; padding: ' . ($settings['logo_padding'] ?? 0) . 'px; border-radius: ' . ($settings['logo_border_radius'] ?? 0) . 'px;';
                            @endphp
                            <div class="mb-3 border d-inline-flex align-items-center justify-content-center" style="{{ $logoPreviewStyle }}">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['logo']) }}" alt="Current logo" style="width: {{ $settings['logo_width'] ?? 180 }}px; height: {{ $settings['logo_height'] ?? 54 }}px; object-fit: contain;">
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                                <label class="form-check-label" for="remove_logo">Remove current logo</label>
                            </div>
                        @endif
                        <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <div class="form-text">Recommended transparent PNG or WebP, maximum 2 MB.</div>
                        @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 h-100">
                        <label class="form-label fw-semibold">Logo Display Customization</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Width (px)</label>
                                <input type="number" name="logo_width" class="form-control @error('logo_width') is-invalid @enderror" value="{{ old('logo_width', $settings['logo_width'] ?? 180) }}" min="80" max="320" step="1">
                                @error('logo_width')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Height (px)</label>
                                <input type="number" name="logo_height" class="form-control @error('logo_height') is-invalid @enderror" value="{{ old('logo_height', $settings['logo_height'] ?? 54) }}" min="24" max="140" step="1">
                                @error('logo_height')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Padding (px)</label>
                                <input type="number" name="logo_padding" class="form-control @error('logo_padding') is-invalid @enderror" value="{{ old('logo_padding', $settings['logo_padding'] ?? 0) }}" min="0" max="40" step="1">
                                @error('logo_padding')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Corner Radius (px)</label>
                                <input type="number" name="logo_border_radius" class="form-control @error('logo_border_radius') is-invalid @enderror" value="{{ old('logo_border_radius', $settings['logo_border_radius'] ?? 0) }}" min="0" max="40" step="1">
                                @error('logo_border_radius')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Background Color</label>
                                <input type="color" name="logo_background_color" class="form-control form-control-color w-100 @error('logo_background_color') is-invalid @enderror" value="{{ old('logo_background_color', ($settings['logo_background_color'] ?? 'transparent') === 'transparent' ? '#ffffff' : $settings['logo_background_color']) }}">
                                @error('logo_background_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="logo_background_transparent" value="1" id="logo_background_transparent" {{ old('logo_background_transparent', ($settings['logo_background_color'] ?? 'transparent') === 'transparent') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="logo_background_transparent">Transparent background</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-text mt-2">For PNG logos with built-in white pixels, upload a transparent PNG to fully control the background color.</div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="border rounded-3 p-3">
                        <label class="form-label fw-semibold">Text Logo Fallback</label>
                        @php
                            $previewIcon = $settings['text_logo_icon_class'] ?? 'bi-rocket-takeoff-fill';
                            $previewIconSize = max(16, min(96, (int) ($settings['text_logo_icon_size'] ?? 32)));
                            $previewIconColor = $settings['text_logo_icon_color'] ?? '#0d6efd';
                            $previewIconWidth = max(16, min(160, (int) ($settings['text_logo_icon_width'] ?? $previewIconSize)));
                            $previewIconHeight = max(16, min(160, (int) ($settings['text_logo_icon_height'] ?? $previewIconSize)));
                            $previewIconPadding = max(0, min(40, (int) ($settings['text_logo_icon_padding'] ?? 0)));
                            $previewIconBorderRadius = max(0, min(60, (int) ($settings['text_logo_icon_border_radius'] ?? 0)));
                            $rawPreviewIconBackground = $settings['text_logo_icon_background_color'] ?? 'transparent';
                            $previewIconBackground = $rawPreviewIconBackground === 'transparent' || preg_match('/^#[0-9A-Fa-f]{6}$/', $rawPreviewIconBackground) ? $rawPreviewIconBackground : 'transparent';
                            $previewPrimaryText = $settings['text_logo_primary_text'] ?? 'ZERO';
                            $previewPrimaryColor = $settings['text_logo_primary_color'] ?? '#ffffff';
                            $previewAccentText = $settings['text_logo_accent_text'] ?? 'GRAVITIES';
                            $previewAccentColor = $settings['text_logo_accent_color'] ?? '#00b4d8';
                            $previewTaglineText = $settings['text_logo_tagline_text'] ?? 'Beyond Limits';
                            $previewTaglineColor = $settings['text_logo_tagline_color'] ?? '#8fa6b8';
                            $previewTaglineSize = max(8, min(24, (int) ($settings['text_logo_tagline_size'] ?? 11)));
                            $previewHasIcon = $previewIcon !== 'none';
                            $previewUsesFavicon = $previewHasIcon && $previewIcon === 'uploaded_favicon' && !empty($settings['favicon']);
                        @endphp
                        <div class="mb-3 p-3 rounded d-inline-flex align-items-center" style="background-color: #0d1b2a;">
                            @if($previewHasIcon)
                                <span class="me-2 d-inline-flex align-items-center justify-content-center" style="width: {{ $previewIconWidth }}px; height: {{ $previewIconHeight }}px; padding: {{ $previewIconPadding }}px; border-radius: {{ $previewIconBorderRadius }}px; background-color: {{ $previewIconBackground }}; box-sizing: content-box; line-height: 1;">
                                    @if($previewUsesFavicon)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['favicon']) }}" alt="Current favicon" style="width: 100%; height: 100%; object-fit: contain;">
                                    @else
                                        <i class="bi {{ preg_match('/^bi-[a-z0-9-]+$/', $previewIcon) ? $previewIcon : 'bi-rocket-takeoff-fill' }}" style="color: {{ $previewIconColor }}; font-size: {{ $previewIconSize }}px; line-height: 1;"></i>
                                    @endif
                                </span>
                            @endif
                            <span class="d-inline-flex flex-column lh-1">
                                <span>
                                    <span class="fw-bold fs-3" style="color: {{ $previewPrimaryColor }};">{{ $previewPrimaryText }}</span><span class="fw-bold fs-3" style="color: {{ $previewAccentColor }};">{{ $previewAccentText }}</span>
                                </span>
                                @if(trim($previewTaglineText) !== '')
                                    <span class="fw-semibold text-uppercase mt-1" style="color: {{ $previewTaglineColor }}; font-size: {{ $previewTaglineSize }}px; letter-spacing: 0;">{{ $previewTaglineText }}</span>
                                @endif
                            </span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small">Icon</label>
                                <select name="text_logo_icon_class" class="form-select @error('text_logo_icon_class') is-invalid @enderror">
                                    @foreach([
                                        'none' => 'None',
                                        'bi-rocket-takeoff-fill' => 'Rocket',
                                        'bi-stars' => 'Stars',
                                        'bi-cpu-fill' => 'CPU',
                                        'bi-globe2' => 'Globe',
                                        'bi-lightning-charge-fill' => 'Lightning',
                                        'bi-boxes' => 'Boxes',
                                        'bi-diagram-3-fill' => 'Network',
                                        'uploaded_favicon' => 'Uploaded Favicon',
                                    ] as $iconClass => $iconLabel)
                                        <option value="{{ $iconClass }}" {{ old('text_logo_icon_class', $previewIcon) === $iconClass ? 'selected' : '' }}>{{ $iconLabel }}</option>
                                    @endforeach
                                </select>
                                @error('text_logo_icon_class')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">None hides the icon. Uploaded Favicon uses the favicon from Brand Assets.</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Icon Size (px)</label>
                                <input type="number" name="text_logo_icon_size" class="form-control @error('text_logo_icon_size') is-invalid @enderror" value="{{ old('text_logo_icon_size', $previewIconSize) }}" min="16" max="96" step="1">
                                @error('text_logo_icon_size')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Icon Color</label>
                                <input type="color" name="text_logo_icon_color" class="form-control form-control-color w-100 @error('text_logo_icon_color') is-invalid @enderror" value="{{ old('text_logo_icon_color', $previewIconColor) }}">
                                @error('text_logo_icon_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Icon Width (px)</label>
                                <input type="number" name="text_logo_icon_width" class="form-control @error('text_logo_icon_width') is-invalid @enderror" value="{{ old('text_logo_icon_width', $previewIconWidth) }}" min="16" max="160" step="1">
                                @error('text_logo_icon_width')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Icon Height (px)</label>
                                <input type="number" name="text_logo_icon_height" class="form-control @error('text_logo_icon_height') is-invalid @enderror" value="{{ old('text_logo_icon_height', $previewIconHeight) }}" min="16" max="160" step="1">
                                @error('text_logo_icon_height')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Icon Padding (px)</label>
                                <input type="number" name="text_logo_icon_padding" class="form-control @error('text_logo_icon_padding') is-invalid @enderror" value="{{ old('text_logo_icon_padding', $previewIconPadding) }}" min="0" max="40" step="1">
                                @error('text_logo_icon_padding')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Icon Corner Radius (px)</label>
                                <input type="number" name="text_logo_icon_border_radius" class="form-control @error('text_logo_icon_border_radius') is-invalid @enderror" value="{{ old('text_logo_icon_border_radius', $previewIconBorderRadius) }}" min="0" max="60" step="1">
                                @error('text_logo_icon_border_radius')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Icon Background Color</label>
                                <input type="color" name="text_logo_icon_background_color" class="form-control form-control-color w-100 @error('text_logo_icon_background_color') is-invalid @enderror" value="{{ old('text_logo_icon_background_color', $previewIconBackground === 'transparent' ? '#ffffff' : $previewIconBackground) }}">
                                @error('text_logo_icon_background_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="text_logo_icon_background_transparent" value="1" id="text_logo_icon_background_transparent" {{ old('text_logo_icon_background_transparent', $previewIconBackground === 'transparent') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="text_logo_icon_background_transparent">Transparent background</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Primary Text</label>
                                <input type="text" name="text_logo_primary_text" class="form-control @error('text_logo_primary_text') is-invalid @enderror" value="{{ old('text_logo_primary_text', $previewPrimaryText) }}" maxlength="40">
                                @error('text_logo_primary_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Primary Color</label>
                                <input type="color" name="text_logo_primary_color" class="form-control form-control-color w-100 @error('text_logo_primary_color') is-invalid @enderror" value="{{ old('text_logo_primary_color', $previewPrimaryColor) }}">
                                @error('text_logo_primary_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Accent Text</label>
                                <input type="text" name="text_logo_accent_text" class="form-control @error('text_logo_accent_text') is-invalid @enderror" value="{{ old('text_logo_accent_text', $previewAccentText) }}" maxlength="40">
                                @error('text_logo_accent_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Accent Color</label>
                                <input type="color" name="text_logo_accent_color" class="form-control form-control-color w-100 @error('text_logo_accent_color') is-invalid @enderror" value="{{ old('text_logo_accent_color', $previewAccentColor) }}">
                                @error('text_logo_accent_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Tagline Text</label>
                                <input type="text" name="text_logo_tagline_text" class="form-control @error('text_logo_tagline_text') is-invalid @enderror" value="{{ old('text_logo_tagline_text', $previewTaglineText) }}" maxlength="80">
                                @error('text_logo_tagline_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Tagline Size (px)</label>
                                <input type="number" name="text_logo_tagline_size" class="form-control @error('text_logo_tagline_size') is-invalid @enderror" value="{{ old('text_logo_tagline_size', $previewTaglineSize) }}" min="8" max="24" step="1">
                                @error('text_logo_tagline_size')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Tagline Color</label>
                                <input type="color" name="text_logo_tagline_color" class="form-control form-control-color w-100 @error('text_logo_tagline_color') is-invalid @enderror" value="{{ old('text_logo_tagline_color', $previewTaglineColor) }}">
                                @error('text_logo_tagline_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="form-text mt-2">Used automatically when no uploaded header logo is active.</div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 h-100">
                        <label class="form-label fw-semibold">Favicon</label>
                        @if(!empty($settings['favicon']))
                            <div class="mb-3 p-2 rounded bg-light border d-inline-block">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['favicon']) }}" alt="Current favicon" style="width: 40px; height: 40px; object-fit: contain;">
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="remove_favicon" value="1" id="remove_favicon">
                                <label class="form-check-label" for="remove_favicon">Remove current favicon</label>
                            </div>
                        @endif
                        <input type="file" name="favicon" class="form-control @error('favicon') is-invalid @enderror" accept=".ico,.jpg,.jpeg,.png,.webp,image/x-icon,image/vnd.microsoft.icon,image/jpeg,image/png,image/webp">
                        <div class="form-text">Use ICO, PNG, JPG, or WebP. Square images work best. Maximum 512 KB.</div>
                        @error('favicon')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 h-100">
                        <label class="form-label fw-semibold">Apple Touch Icon</label>
                        @if(!empty($settings['apple_touch_icon']))
                            <div class="mb-3 p-2 rounded bg-light border d-inline-block">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['apple_touch_icon']) }}" alt="Current Apple touch icon" style="width: 72px; height: 72px; object-fit: contain;">
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="remove_apple_touch_icon" value="1" id="remove_apple_touch_icon">
                                <label class="form-check-label" for="remove_apple_touch_icon">Remove current Apple icon</label>
                            </div>
                        @endif
                        <input type="file" name="apple_touch_icon" class="form-control @error('apple_touch_icon') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <div class="form-text">Recommended 180x180 PNG or WebP, maximum 1 MB.</div>
                        @error('apple_touch_icon')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 h-100">
                        <label class="form-label fw-semibold">Default Social / OG Image</label>
                        @if(!empty($settings['default_og_image']))
                            <div class="mb-3 p-2 rounded bg-light border">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['default_og_image']) }}" alt="Current social image" style="max-height: 120px; max-width: 100%; object-fit: contain;">
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="remove_default_og_image" value="1" id="remove_default_og_image">
                                <label class="form-check-label" for="remove_default_og_image">Remove current social image</label>
                            </div>
                        @endif
                        <input type="file" name="default_og_image" class="form-control @error('default_og_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <div class="form-text">Recommended 1200x630 image, maximum 4 MB.</div>
                        @error('default_og_image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

                </section>

                <section id="social-settings" class="settings-section">
                    <div class="settings-section-header">
                        <span class="settings-section-icon"><i class="bi bi-share"></i></span>
                        <div>
                            <h5 class="fw-bold mb-1">Social Links</h5>
                            <p>Public social profile links shown across the website.</p>
                        </div>
                    </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Facebook URL</label>
                    <input type="url" name="facebook[en]" class="form-control" value="{{ $settings['facebook'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">LinkedIn URL</label>
                    <input type="url" name="linkedin[en]" class="form-control" value="{{ $settings['linkedin'] ?? '' }}">
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">YouTube URL</label>
                    <input type="url" name="youtube[en]" class="form-control" value="{{ $settings['youtube'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">X (Twitter) URL</label>
                    <input type="url" name="twitter[en]" class="form-control" value="{{ $settings['twitter'] ?? '' }}">
                </div>
            </div>

                </section>

                <div class="settings-savebar">
                    <div>
                        <div class="fw-bold">Ready to apply changes?</div>
                        <div class="small text-muted">This saves every section on the settings page.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i> Save Settings
                    </button>
                </div>
        </div>
    </form>
</div>
@endsection
