<?php
/**
 * ================================================================
 * SATORI Forms Submission Validator
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Core;

final class SubmissionValidator
{
    private const SUPPORTED_SCHEMA_VERSION = 1;
    private const SUPPORTED_FIELD_TYPES = [
        'text',
        'email',
        'textarea',
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

    public function validate(int $formId, array $submission): array
    {
        $schema = $this->load_schema($formId);
        if ($schema === null) {
            return $this->failure([
                $this->error(null, 'schema_missing', 'Form schema could not be loaded.'),
            ]);
        }

        if (!isset($schema['version']) || (int) $schema['version'] !== self::SUPPORTED_SCHEMA_VERSION) {
            return $this->failure([
                $this->error(null, 'schema_version', 'Unsupported form schema version.'),
            ]);
        }

        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            return $this->failure([
                $this->error(null, 'schema_fields', 'Form schema fields are invalid.'),
            ]);
        }

        $fields = $this->map_fields($schema['fields']);
        if ($fields === null) {
            return $this->failure([
                $this->error(null, 'schema_fields', 'Form schema fields are invalid.'),
            ]);
        }

        $submission = $this->normalize_submission($submission);
        $errors = [];
        $data = [];

        $unknownFields = array_diff(array_keys($submission), array_keys($fields));
        foreach ($unknownFields as $fieldId) {
            $errors[] = $this->error($fieldId, 'unknown_field', 'Field is not part of the form schema.');
        }

        foreach ($fields as $fieldId => $field) {
            $hasValue = array_key_exists($fieldId, $submission);
            $value = $hasValue ? $submission[$fieldId] : null;
            $required = (bool) $field['required'];

            if (isset($field['validation']['required'])) {
                $required = $required || (bool) $field['validation']['required'];
            }

            if (!$hasValue || $this->is_empty_value($value)) {
                if ($required) {
                    $errors[] = $this->error($fieldId, 'required', 'Field is required.');
                }
                continue;
            }

            if (!is_scalar($value)) {
                $errors[] = $this->error($fieldId, 'invalid_type', 'Field value must be a string.');
                continue;
            }

            $sanitized = $this->sanitize_value((string) $value, $field['type']);
            if ($field['type'] === 'email' && $sanitized !== '' && !is_email($sanitized)) {
                $errors[] = $this->error($fieldId, 'invalid_email', 'Email address is invalid.');
                continue;
            }

            $length = $this->string_length($sanitized);
            $validation = $field['validation'] ?? [];

            if (array_key_exists('min_length', $validation) && $validation['min_length'] !== null) {
                if ($length < (int) $validation['min_length']) {
                    $errors[] = $this->error($fieldId, 'min_length', 'Field value is too short.');
                    continue;
                }
            }

            if (array_key_exists('max_length', $validation) && $validation['max_length'] !== null) {
                if ($length > (int) $validation['max_length']) {
                    $errors[] = $this->error($fieldId, 'max_length', 'Field value is too long.');
                    continue;
                }
            }

            $data[$fieldId] = $sanitized;
        }

        if ($errors !== []) {
            return $this->failure($errors, $data);
        }

        return [
            'is_valid' => true,
            'data' => $data,
            'errors' => [],
        ];
    }

    private function load_schema(int $formId): ?array
    {
        $rawSchema = get_post_meta($formId, FormSchema::META_KEY, true);
        if ($rawSchema === '' || $rawSchema === null) {
            return null;
        }

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

    private function map_fields(array $fields): ?array
    {
        $mapped = [];

        foreach ($fields as $field) {
            if (!is_array($field) || !isset($field['id'], $field['type'], $field['required'])) {
                return null;
            }

            $missingKeys = array_diff(self::REQUIRED_FIELD_KEYS, array_keys($field));
            if ($missingKeys !== []) {
                return null;
            }

            $unknownKeys = array_diff(array_keys($field), self::ALLOWED_FIELD_KEYS);
            if ($unknownKeys !== []) {
                return null;
            }

            if (!is_string($field['id']) || $field['id'] === '') {
                return null;
            }

            if (!is_string($field['type'])) {
                return null;
            }

            if (!in_array($field['type'], self::SUPPORTED_FIELD_TYPES, true)) {
                return null;
            }

            if (!is_bool($field['required'])) {
                return null;
            }

            if (!is_string($field['label']) || trim($field['label']) === '') {
                return null;
            }

            if (isset($field['validation'])) {
                if (!is_array($field['validation'])) {
                    return null;
                }

                $unknownValidationKeys = array_diff(
                    array_keys($field['validation']),
                    self::ALLOWED_VALIDATION_KEYS
                );
                if ($unknownValidationKeys !== []) {
                    return null;
                }

                foreach (['min_length', 'max_length'] as $key) {
                    if (array_key_exists($key, $field['validation']) && $field['validation'][$key] !== null) {
                        if (!is_int($field['validation'][$key])) {
                            return null;
                        }
                    }
                }

                if (array_key_exists('required', $field['validation']) && !is_bool($field['validation']['required'])) {
                    return null;
                }
            }

            $mapped[$field['id']] = $field;
        }

        return $mapped;
    }

    private function normalize_submission(array $submission): array
    {
        $normalized = [];

        foreach ($submission as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $normalized[$key] = is_string($value) ? wp_unslash($value) : $value;
        }

        return $normalized;
    }

    private function sanitize_value(string $value, string $type): string
    {
        if ($type === 'textarea') {
            return sanitize_textarea_field($value);
        }

        if ($type === 'email') {
            return sanitize_email($value);
        }

        return sanitize_text_field($value);
    }

    private function is_empty_value(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return false;
    }

    private function string_length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }

    private function error(?string $field, string $code, string $message): array
    {
        return [
            'field' => $field,
            'code' => $code,
            'message' => $message,
        ];
    }

    private function failure(array $errors, array $data = []): array
    {
        return [
            'is_valid' => false,
            'data' => $data,
            'errors' => $errors,
        ];
    }
}
