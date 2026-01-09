<?php
/**
 * ================================================================
 * SATORI Forms Admin UI
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Admin;

use Satori\Forms\Core\FormCPT;
use Satori\Forms\Core\FormSchema;
use WP_Post;

final class FormAdmin
{
    public function register_hooks(): void
    {
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
    }

    public function register_meta_boxes(): void
    {
        add_meta_box(
            'satori-form-schema',
            __('Form Schema', 'satori-forms'),
            [$this, 'render_schema_box'],
            FormCPT::POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            'satori-form-notifications',
            __('Email Notification', 'satori-forms'),
            [$this, 'render_notification_box'],
            FormCPT::POST_TYPE,
            'side',
            'default'
        );
    }

    public function render_schema_box(WP_Post $post): void
    {
        wp_nonce_field(FormSchema::NONCE_ACTION, FormSchema::NONCE_FIELD);

        $schemaValue = get_post_meta($post->ID, FormSchema::META_KEY, true);
        $schemaDisplay = $this->format_schema_for_display($schemaValue);

        echo '<p>' . esc_html__('Paste the form schema JSON for this form.', 'satori-forms') . '</p>';
        echo '<textarea name="' . esc_attr(FormSchema::META_KEY) . '" rows="16" class="widefat"';
        echo ' placeholder="' . esc_attr($this->default_schema_placeholder()) . '">';
        echo esc_textarea($schemaDisplay);
        echo '</textarea>';
    }

    public function render_notification_box(WP_Post $post): void
    {
        $notifications = $this->load_notification_settings($post->ID);
        $enabled = (bool) ($notifications['enabled'] ?? false);
        $to = (string) ($notifications['to'] ?? '');
        $subject = (string) ($notifications['subject'] ?? '');
        $message = (string) ($notifications['message'] ?? '');

        echo '<p>' . esc_html__('Send a basic email when a submission is stored.', 'satori-forms') . '</p>';
        echo '<label style="display:block;margin-bottom:8px;">';
        echo '<input type="checkbox" name="satori_form_notification_enabled" value="1" ';
        checked($enabled);
        echo ' /> ' . esc_html__('Enable notifications', 'satori-forms');
        echo '</label>';

        echo '<p><label for="satori_form_notification_to">' . esc_html__('To Email', 'satori-forms') . '</label>';
        echo '<input type="email" class="widefat" id="satori_form_notification_to"';
        echo ' name="satori_form_notification_to" value="' . esc_attr($to) . '" /></p>';

        echo '<p><label for="satori_form_notification_subject">' . esc_html__('Subject', 'satori-forms') . '</label>';
        echo '<input type="text" class="widefat" id="satori_form_notification_subject"';
        echo ' name="satori_form_notification_subject" value="' . esc_attr($subject) . '" /></p>';

        echo '<p><label for="satori_form_notification_message">' . esc_html__('Message', 'satori-forms') . '</label>';
        echo '<textarea class="widefat" rows="6" id="satori_form_notification_message"';
        echo ' name="satori_form_notification_message">' . esc_textarea($message) . '</textarea></p>';

        echo '<p><strong>' . esc_html__('Placeholders', 'satori-forms') . '</strong><br />';
        echo esc_html__('{form_title}, {submission_id}, {submission_date}, {field_id}', 'satori-forms') . '</p>';
    }

    private function format_schema_for_display(mixed $schemaValue): string
    {
        if (is_array($schemaValue)) {
            return (string) wp_json_encode($schemaValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if (!is_string($schemaValue) || trim($schemaValue) === '') {
            return '';
        }

        $decoded = json_decode($schemaValue, true);
        if (!is_array($decoded)) {
            return $schemaValue;
        }

        return (string) wp_json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function load_notification_settings(int $postId): array
    {
        $schemaValue = get_post_meta($postId, FormSchema::META_KEY, true);
        $schema = null;

        if (is_string($schemaValue) && $schemaValue !== '') {
            $schema = json_decode($schemaValue, true);
        } elseif (is_array($schemaValue)) {
            $schema = $schemaValue;
        }

        if (!is_array($schema) || !isset($schema['settings']) || !is_array($schema['settings'])) {
            return [];
        }

        $notifications = $schema['settings']['notifications'] ?? [];
        return is_array($notifications) ? $notifications : [];
    }

    private function default_schema_placeholder(): string
    {
        $placeholder = [
            'version' => 1,
            'fields' => [
                [
                    'id' => 'name',
                    'type' => 'text',
                    'label' => 'Name',
                    'required' => true,
                ],
            ],
            'settings' => [
                'notifications' => [
                    'enabled' => true,
                    'to' => 'admin@example.com',
                    'subject' => 'New submission for {form_title}',
                    'message' => "New submission:\n{name}",
                ],
            ],
        ];

        return (string) wp_json_encode($placeholder, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
