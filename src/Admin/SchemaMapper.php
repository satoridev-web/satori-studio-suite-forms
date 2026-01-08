<?php
/**
 * ================================================================
 * SATORI Forms Schema Mapper
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Admin;

use Satori\Forms\Core\FormCPT;
use Satori\Forms\Core\FormSchema;

final class SchemaMapper
{
    private const SUPPORTED_FIELD_TYPES = [
        'text',
        'email',
        'textarea',
    ];

    private const ALLOWED_SCHEMA_KEYS = [
        'version',
        'fields',
        'settings',
    ];

    private const REQUIRED_FIELD_KEYS = [
        'id',
        'type',
        'label',
        'required',
    ];

    private const ALLOWED_FIELD_KEYS = [
        'id',
        'type',
        'label',
        'required',
        'validation',
        'meta',
    ];

    private const ALLOWED_SETTINGS_KEYS = [
        'notifications',
    ];

    public function register_hooks(): void
    {
        if (!is_admin()) {
            return;
        }

        add_action('acf/save_post', [$this, 'handle_acf_save'], 20);
    }

    public function handle_acf_save(mixed $postId): void
    {
        if (!ACFIntegration::is_available()) {
            return;
        }

        if (!is_numeric($postId)) {
            return;
        }

        $postId = (int) $postId;
        if ($postId <= 0) {
            return;
        }

        $post = get_post($postId);
        if (!$post || $post->post_type !== FormCPT::POST_TYPE) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $fields = $this->build_fields($postId);
        $notifications = $this->build_notifications($postId);

        if ($fields === [] && $notifications === null) {
            delete_post_meta($postId, FormSchema::META_KEY);
            return;
        }

        $schema = [
            'version' => 1,
            'fields' => $fields,
        ];

        if ($notifications !== null) {
            $schema['settings'] = [
                'notifications' => $notifications,
            ];
        }

        $error = '';
        if (!$this->validate_schema($schema, $error)) {
            return;
        }

        update_post_meta($postId, FormSchema::META_KEY, wp_json_encode($schema));
    }

    private function build_fields(int $postId): array
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $rawFields = get_field('satori_form_fields', $postId);
        if (!is_array($rawFields)) {
            return [];
        }

        $fields = [];
        foreach ($rawFields as $rawField) {
            if (!is_array($rawField)) {
                continue;
            }

            $id = sanitize_text_field((string) ($rawField['field_id'] ?? ''));
            $type = sanitize_text_field((string) ($rawField['field_type'] ?? ''));
            $label = sanitize_text_field((string) ($rawField['field_label'] ?? ''));
            $required = (bool) ($rawField['field_required'] ?? false);

            if ($id === '' || $label === '' || $type === '') {
                continue;
            }

            if (!in_array($type, self::SUPPORTED_FIELD_TYPES, true)) {
                continue;
            }

            $fields[] = [
                'id' => $id,
                'type' => $type,
                'label' => $label,
                'required' => $required,
            ];
        }

        return $fields;
    }

    private function build_notifications(int $postId): ?array
    {
        if (!function_exists('get_field')) {
            return null;
        }

        $raw = get_field('satori_form_notifications', $postId);
        if (!is_array($raw)) {
            return null;
        }

        $enabled = (bool) ($raw['enabled'] ?? false);
        $to = sanitize_email((string) ($raw['to'] ?? ''));
        $subject = sanitize_text_field((string) ($raw['subject'] ?? ''));
        $message = sanitize_textarea_field((string) ($raw['message'] ?? ''));

        if (!$enabled && $to === '' && $subject === '' && $message === '') {
            return null;
        }

        return [
            'enabled' => $enabled,
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
        ];
    }

    private function validate_schema(array $schema, string &$error): bool
    {
        $unknownSchemaKeys = array_diff(array_keys($schema), self::ALLOWED_SCHEMA_KEYS);
        if ($unknownSchemaKeys !== []) {
            $error = 'Schema contains unsupported keys.';
            return false;
        }

        if (!array_key_exists('version', $schema) || !array_key_exists('fields', $schema)) {
            $error = 'Schema must include version and fields.';
            return false;
        }

        if ((int) $schema['version'] !== 1) {
            $error = 'Unsupported schema version.';
            return false;
        }

        if (!is_array($schema['fields'])) {
            $error = 'Schema fields must be an array.';
            return false;
        }

        foreach ($schema['fields'] as $index => $field) {
            if (!is_array($field)) {
                $error = sprintf('Field at index %d must be an object.', $index);
                return false;
            }

            $missingKeys = array_diff(self::REQUIRED_FIELD_KEYS, array_keys($field));
            if ($missingKeys !== []) {
                $error = sprintf('Field at index %d is missing required keys.', $index);
                return false;
            }

            $unknownFieldKeys = array_diff(array_keys($field), self::ALLOWED_FIELD_KEYS);
            if ($unknownFieldKeys !== []) {
                $error = sprintf('Field at index %d contains unsupported keys.', $index);
                return false;
            }

            if (!is_string($field['id']) || trim($field['id']) === '') {
                $error = sprintf('Field at index %d must include a non-empty id.', $index);
                return false;
            }

            if (!is_string($field['type']) || !in_array($field['type'], self::SUPPORTED_FIELD_TYPES, true)) {
                $error = sprintf('Field at index %d has an unsupported type.', $index);
                return false;
            }

            if (!is_string($field['label']) || trim($field['label']) === '') {
                $error = sprintf('Field at index %d must include a non-empty label.', $index);
                return false;
            }

            if (!is_bool($field['required'])) {
                $error = sprintf('Field at index %d must include a boolean required flag.', $index);
                return false;
            }

            if (array_key_exists('validation', $field) && !is_array($field['validation'])) {
                $error = sprintf('Field at index %d has invalid validation rules.', $index);
                return false;
            }

            if (array_key_exists('meta', $field) && !is_array($field['meta'])) {
                $error = sprintf('Field at index %d meta must be an object.', $index);
                return false;
            }
        }

        if (array_key_exists('settings', $schema)) {
            if (!is_array($schema['settings'])) {
                $error = 'Schema settings must be an object.';
                return false;
            }

            if (!$this->validate_settings($schema['settings'], $error)) {
                return false;
            }
        }

        return true;
    }

    private function validate_settings(array $settings, string &$error): bool
    {
        $unknownSettingsKeys = array_diff(array_keys($settings), self::ALLOWED_SETTINGS_KEYS);
        if ($unknownSettingsKeys !== []) {
            $error = 'Schema settings has unsupported keys.';
            return false;
        }

        if (!array_key_exists('notifications', $settings)) {
            return true;
        }

        if (!is_array($settings['notifications'])) {
            $error = 'Schema settings.notifications must be an object.';
            return false;
        }

        $allowedNotificationKeys = ['enabled', 'to', 'subject', 'message'];
        $unknownNotificationKeys = array_diff(array_keys($settings['notifications']), $allowedNotificationKeys);
        if ($unknownNotificationKeys !== []) {
            $error = 'Schema settings.notifications has unsupported keys.';
            return false;
        }

        if (
            array_key_exists('enabled', $settings['notifications'])
            && !is_bool($settings['notifications']['enabled'])
        ) {
            $error = 'Schema settings.notifications.enabled must be boolean.';
            return false;
        }

        foreach (['to', 'subject', 'message'] as $key) {
            if (
                array_key_exists($key, $settings['notifications'])
                && !is_string($settings['notifications'][$key])
            ) {
                $error = sprintf('Schema settings.notifications.%s must be string.', $key);
                return false;
            }
        }

        return true;
    }
}
