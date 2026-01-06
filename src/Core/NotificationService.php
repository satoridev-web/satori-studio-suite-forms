<?php
/**
 * ================================================================
 * SATORI Forms Notification Service
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Core;

final class NotificationService
{
    public function register_hooks(): void
    {
        add_action('satori_forms_submission_stored', [$this, 'handle_submission'], 10, 4);
    }

    public function handle_submission(int $submissionId, int $formId, array $data, string $submittedAt = ''): void
    {
        $settings = $this->load_settings($formId);
        if ($settings === null || !$settings['enabled']) {
            return;
        }

        $to = $this->sanitize_recipient($settings['to'] ?? '');
        if ($to === '') {
            return;
        }

        $placeholders = $this->build_placeholders($formId, $submissionId, $data, $submittedAt);

        $subjectTemplate = (string) ($settings['subject'] ?? '');
        if ($subjectTemplate === '') {
            $subjectTemplate = 'New submission for {form_title}';
        }

        $messageTemplate = (string) ($settings['message'] ?? '');
        if ($messageTemplate === '') {
            $messageTemplate = $this->default_message($data);
        }

        $subject = $this->replace_placeholders($subjectTemplate, $placeholders);
        $subject = sanitize_text_field($subject);
        $message = $this->replace_placeholders($messageTemplate, $placeholders);

        wp_mail($to, $subject, $message, ['Content-Type: text/plain; charset=UTF-8']);
    }

    private function load_settings(int $formId): ?array
    {
        $schemaValue = get_post_meta($formId, FormSchema::META_KEY, true);
        $schema = null;

        if (is_string($schemaValue) && $schemaValue !== '') {
            $schema = json_decode($schemaValue, true);
        } elseif (is_array($schemaValue)) {
            $schema = $schemaValue;
        }

        if (!is_array($schema)) {
            return null;
        }

        $settings = $schema['settings']['notifications'] ?? null;
        if (!is_array($settings)) {
            return null;
        }

        return [
            'enabled' => (bool) ($settings['enabled'] ?? false),
            'to' => is_string($settings['to'] ?? null) ? $settings['to'] : '',
            'subject' => is_string($settings['subject'] ?? null) ? $settings['subject'] : '',
            'message' => is_string($settings['message'] ?? null) ? $settings['message'] : '',
        ];
    }

    private function sanitize_recipient(string $recipient): string
    {
        $recipient = sanitize_email($recipient);
        if ($recipient === '' || !is_email($recipient)) {
            return '';
        }

        return $recipient;
    }

    private function build_placeholders(
        int $formId,
        int $submissionId,
        array $data,
        string $submittedAt
    ): array
    {
        $timestamp = $this->format_submission_time($submittedAt);
        $placeholders = [
            '{form_title}' => get_the_title($formId) ?: '',
            '{submission_id}' => (string) $submissionId,
            '{submission_date}' => $timestamp,
        ];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $placeholders['{' . $key . '}'] = (string) $value;
        }

        return $placeholders;
    }

    private function format_submission_time(string $submittedAt): string
    {
        if ($submittedAt === '') {
            return gmdate('Y-m-d H:i:s');
        }

        $local = get_date_from_gmt($submittedAt, 'Y-m-d H:i:s');
        if ($local !== '') {
            return $local;
        }

        return $submittedAt;
    }

    private function replace_placeholders(string $template, array $placeholders): string
    {
        return strtr($template, $placeholders);
    }

    private function default_message(array $data): string
    {
        $lines = ['New submission received.'];

        foreach ($data as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }

            $lines[] = sprintf('%s: %s', $key, (string) $value);
        }

        return implode("\n", $lines);
    }
}
