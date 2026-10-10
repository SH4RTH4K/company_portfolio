<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class MailConfiguration
{
    /**
     * @return array<string, string|null>
     */
    public function settings(): array
    {
        return SiteSetting::query()
            ->whereIn('key', [
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_scheme',
                'mail_from_address',
                'mail_from_name',
                'mail_admin_notification_address',
            ])
            ->pluck('value_en', 'key')
            ->all();
    }

    public function apply(): void
    {
        $settings = $this->settings();

        $mailer = $this->setting($settings, 'mail_mailer', (string) config('mail.default', 'log'));
        $fromAddress = $this->setting($settings, 'mail_from_address', (string) config('mail.from.address'));
        $fromName = $this->setting($settings, 'mail_from_name', (string) config('mail.from.name'));

        config([
            'mail.default' => $mailer,
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $fromName,
        ]);

        if ($mailer === 'smtp') {
            config([
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.scheme' => $this->scheme($settings['mail_scheme'] ?? null),
                'mail.mailers.smtp.host' => $this->setting($settings, 'mail_host', (string) config('mail.mailers.smtp.host', '127.0.0.1')),
                'mail.mailers.smtp.port' => (int) $this->setting($settings, 'mail_port', (string) config('mail.mailers.smtp.port', 587)),
                'mail.mailers.smtp.username' => $this->nullableSetting($settings, 'mail_username'),
                'mail.mailers.smtp.password' => $this->password($settings),
            ]);
        }

        Mail::forgetMailers();
    }

    public function adminRecipient(): string
    {
        $settings = $this->settings();

        return $this->setting(
            $settings,
            'mail_admin_notification_address',
            $this->setting($settings, 'mail_from_address', (string) config('mail.from.address'))
        );
    }

    public function encryptPassword(string $password): string
    {
        return Crypt::encryptString($password);
    }

    public function passwordIsConfigured(): bool
    {
        return $this->password($this->settings()) !== null;
    }

    /**
     * @param  array<string, string|null>  $settings
     */
    private function password(array $settings): ?string
    {
        $encryptedPassword = $this->nullableSetting($settings, 'mail_password');

        if (! $encryptedPassword) {
            return null;
        }

        try {
            return Crypt::decryptString($encryptedPassword);
        } catch (Throwable $exception) {
            throw new RuntimeException('The saved SMTP password could not be decrypted.', previous: $exception);
        }
    }

    /**
     * @param  array<string, string|null>  $settings
     */
    private function setting(array $settings, string $key, string $default): string
    {
        $value = $settings[$key] ?? null;

        return $value !== null && $value !== '' ? $value : $default;
    }

    /**
     * @param  array<string, string|null>  $settings
     */
    private function nullableSetting(array $settings, string $key): ?string
    {
        $value = $settings[$key] ?? null;

        return $value !== null && $value !== '' ? $value : null;
    }

    private function scheme(?string $scheme): ?string
    {
        return $scheme && $scheme !== 'auto' ? $scheme : null;
    }
}
