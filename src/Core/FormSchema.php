<?php
/**
 * ================================================================
 * SATORI Forms Form Schema
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Core;

use WP_Post;

final class FormSchema
{
    public const META_KEY = 'satori_form_schema';
    private const SCHEMA_VERSION = 1;
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

    private const ALLOWED_VALIDATION_KEYS = [
        'required',
        'min_length',
        'max_length',
    ];

    private const ALLOWED_SETTINGS_KEYS = [
        'notifications',
    ];

    public function register_hooks(): void
    {
        add_action('init', [$this, 'register_meta']);
        add_action('save_post_' . FormCPT::POST_TYPE, [$this, 'handle_save'], 10, 3);
    }

    public function register_meta(): void
    {
        register_post_meta(
            FormCPT::POST_TYPE,
            self::META_KEY,
            [
                'single' => true,
                'type' => 'string',
                'show_in_rest' => false,
                'auth_callback' => [$this, 'authorize_meta_update'],
            ]
        );
    }

    public function authorize_meta_update(
        bool $allowed,
        string $metaKey,
        int $postId,
        int $userId,
        string $cap,
        array $caps
    ): bool
    {
        if ($postId <= 0) {
            return user_can($userId, 'edit_posts');
        }

        return user_can($userId, 'edit_post', $postId);
    }

    public function handle_save(int $postId, WP_Post $post, bool $update): void
    {
        if ($post->post_type !== FormCPT::POST_TYPE) {
            return;
        }

        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $rawSchema = filter_input(INPUT_POST, self::META_KEY, FILTER_UNSAFE_RAW);
        if ($rawSchema === null) {
            return;
        }

        $rawSchema = is_string($rawSchema) ? wp_unslash($rawSchema) : '';
        if ($rawSchema === '') {
            delete_post_meta($postId, self::META_KEY);
            return;
        }

        $schema = $this->decode_schema($rawSchema);
        if ($schema === null) {
            wp_die(
                esc_html__('Invalid schema payload. Expected JSON data.', 'satori-forms'),
                esc_html__('Invalid Form Schema', 'satori-forms'),
                ['response' => 400]
            );
        }

        $error = '';
        if (!$this->validate_schema($schema, $error)) {
            wp_die(
                esc_html($error),
                esc_html__('Invalid Form Schema', 'satori-forms'),
                ['response' => 400]
            );
        }

        update_post_meta($postId, self::META_KEY, wp_json_encode($schema));
    }

    private function decode_schema(mixed $rawSchema): ?array
    {
        if (is_array($rawSchema)) {
            return $rawSchema;
        }

        if (!is_string($rawSchema)) {
            return null;
        }

        $decoded = json_decode($rawSchema, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function validate_schema(array $schema, string &$error): bool
    {
        $unknownSchemaKeys = array_diff(array_keys($schema), self::ALLOWED_SCHEMA_KEYS);
        if ($unknownSchemaKeys !== []) {
            $error = sprintf(
                'Schema contains unsupported keys: %s.',
                implode(', ', $unknownSchemaKeys)
            );
            return false;
        }

        if (!array_key_exists('version', $schema) || !array_key_exists('fields', $schema)) {
            $error = 'Schema must include version and fields.';
            return false;
        }

        if ((int) $schema['version'] !== self::SCHEMA_VERSION) {
            $error = sprintf(
                'Unsupported schema version: %s.',
                is_scalar($schema['version']) ? (string) $schema['version'] : 'unknown'
            );
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
                $error = sprintf(
                    'Field at index %d is missing required keys: %s.',
                    $index,
                    implode(', ', $missingKeys)
                );
                return false;
            }

            $unknownFieldKeys = array_diff(array_keys($field), self::ALLOWED_FIELD_KEYS);
            if ($unknownFieldKeys !== []) {
                $error = sprintf(
                    'Field at index %d contains unsupported keys: %s.',
                    $index,
                    implode(', ', $unknownFieldKeys)
                );
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

            if (array_key_exists('validation', $field)) {
                if (!is_array($field['validation'])) {
                    $error = sprintf('Field at index %d has invalid validation rules.', $index);
                    return false;
                }

                $unknownValidationKeys = array_diff(
                    array_keys($field['validation']),
                    self::ALLOWED_VALIDATION_KEYS
                );
                if ($unknownValidationKeys !== []) {
                    $error = sprintf(
                        'Field at index %d has unsupported validation keys: %s.',
                        $index,
                        implode(', ', $unknownValidationKeys)
                    );
                    return false;
                }

                if (
                    array_key_exists('required', $field['validation'])
                    && !is_bool($field['validation']['required'])
                ) {
                    $error = sprintf('Field at index %d validation.required must be boolean.', $index);
                    return false;
                }

                if (
                    array_key_exists('min_length', $field['validation'])
                    && !$this->is_int_or_null($field['validation']['min_length'])
                ) {
                    $error = sprintf('Field at index %d validation.min_length must be int or null.', $index);
                    return false;
                }

                if (
                    array_key_exists('max_length', $field['validation'])
                    && !$this->is_int_or_null($field['validation']['max_length'])
                ) {
                    $error = sprintf('Field at index %d validation.max_length must be int or null.', $index);
                    return false;
                }
            }

            if (array_key_exists('meta', $field) && !is_array($field['meta'])) {
                $error = sprintf('Field at index %d meta must be an object.', $index);
                return false;
            }
        }

        if (array_key_exists('settings', $schema) && !is_array($schema['settings'])) {
            $error = 'Schema settings must be an object.';
            return false;
        }

        if (array_key_exists('settings', $schema)) {
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
            $error = sprintf(
                'Schema settings has unsupported keys: %s.',
                implode(', ', $unknownSettingsKeys)
            );
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
            $error = sprintf(
                'Schema settings.notifications has unsupported keys: %s.',
                implode(', ', $unknownNotificationKeys)
            );
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

    private function is_int_or_null(mixed $value): bool
    {
        return $value === null || is_int($value);
    }
}
