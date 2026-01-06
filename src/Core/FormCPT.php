<?php
/**
 * ================================================================
 * SATORI Forms Form CPT
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Core;

final class FormCPT
{
    public const POST_TYPE = 'satori_form';

    public function register_hooks(): void
    {
        add_action('init', [$this, 'register_post_type']);
    }

    public function register_post_type(): void
    {
        $labels = [
            'name' => __('Forms', 'satori-forms'),
            'singular_name' => __('Form', 'satori-forms'),
            'add_new_item' => __('Add New Form', 'satori-forms'),
            'edit_item' => __('Edit Form', 'satori-forms'),
            'new_item' => __('New Form', 'satori-forms'),
            'view_item' => __('View Form', 'satori-forms'),
            'search_items' => __('Search Forms', 'satori-forms'),
            'not_found' => __('No forms found', 'satori-forms'),
            'not_found_in_trash' => __('No forms found in Trash', 'satori-forms'),
        ];

        register_post_type(
            self::POST_TYPE,
            [
                'labels' => $labels,
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => false,
                'supports' => ['title'],
                'has_archive' => false,
                'rewrite' => false,
                'capability_type' => 'post',
                'map_meta_cap' => true,
            ]
        );
    }
}
