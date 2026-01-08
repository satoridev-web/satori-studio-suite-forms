<?php
/**
 * ================================================================
 * SATORI Forms ACF Integration
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Admin;

use Satori\Forms\Core\FormCPT;

final class ACFIntegration
{
    public function register_hooks(): void
    {
        if (!is_admin()) {
            return;
        }

        add_action('acf/init', [$this, 'register_field_groups']);
    }

    public static function is_available(): bool
    {
        return function_exists('acf_add_local_field_group');
    }

    public function register_field_groups(): void
    {
        if (!self::is_available()) {
            return;
        }

        acf_add_local_field_group(
            [
                'key' => 'satori_forms_schema_group',
                'title' => __('SATORI Form Schema', 'satori-forms'),
                'fields' => [
                    [
                        'key' => 'satori_forms_schema_tab_fields',
                        'label' => __('Fields', 'satori-forms'),
                        'type' => 'tab',
                    ],
                    [
                        'key' => 'satori_forms_schema_fields',
                        'label' => __('Field Definitions', 'satori-forms'),
                        'name' => 'satori_form_fields',
                        'type' => 'repeater',
                        'layout' => 'row',
                        'button_label' => __('Add Field', 'satori-forms'),
                        'sub_fields' => [
                            [
                                'key' => 'satori_forms_schema_field_id',
                                'label' => __('Field ID', 'satori-forms'),
                                'name' => 'field_id',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'satori_forms_schema_field_type',
                                'label' => __('Type', 'satori-forms'),
                                'name' => 'field_type',
                                'type' => 'select',
                                'choices' => [
                                    'text' => __('Text', 'satori-forms'),
                                    'email' => __('Email', 'satori-forms'),
                                    'textarea' => __('Textarea', 'satori-forms'),
                                ],
                                'ui' => 1,
                                'required' => 1,
                            ],
                            [
                                'key' => 'satori_forms_schema_field_label',
                                'label' => __('Label', 'satori-forms'),
                                'name' => 'field_label',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'satori_forms_schema_field_required',
                                'label' => __('Required', 'satori-forms'),
                                'name' => 'field_required',
                                'type' => 'true_false',
                                'ui' => 1,
                                'default_value' => 0,
                            ],
                        ],
                    ],
                    [
                        'key' => 'satori_forms_schema_tab_notifications',
                        'label' => __('Notifications', 'satori-forms'),
                        'type' => 'tab',
                    ],
                    [
                        'key' => 'satori_forms_schema_notifications',
                        'label' => __('Notification Settings', 'satori-forms'),
                        'name' => 'satori_form_notifications',
                        'type' => 'group',
                        'layout' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'satori_forms_schema_notifications_enabled',
                                'label' => __('Enable Notifications', 'satori-forms'),
                                'name' => 'enabled',
                                'type' => 'true_false',
                                'ui' => 1,
                                'default_value' => 0,
                            ],
                            [
                                'key' => 'satori_forms_schema_notifications_to',
                                'label' => __('To Email', 'satori-forms'),
                                'name' => 'to',
                                'type' => 'email',
                            ],
                            [
                                'key' => 'satori_forms_schema_notifications_subject',
                                'label' => __('Subject', 'satori-forms'),
                                'name' => 'subject',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'satori_forms_schema_notifications_message',
                                'label' => __('Message', 'satori-forms'),
                                'name' => 'message',
                                'type' => 'textarea',
                                'rows' => 6,
                            ],
                        ],
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => FormCPT::POST_TYPE,
                        ],
                    ],
                ],
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'active' => true,
            ]
        );
    }
}
