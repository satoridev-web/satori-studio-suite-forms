<?php
/**
 * ================================================================
 * SATORI Forms Submissions Admin
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Admin;

use Satori\Forms\Core\FormCPT;
use Satori\Forms\Core\SubmissionTable;

final class SubmissionAdmin
{
    public function register_hooks(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . FormCPT::POST_TYPE,
            __('Submissions', 'satori-forms'),
            __('Submissions', 'satori-forms'),
            'edit_posts',
            'satori-form-submissions',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('You do not have permission to view submissions.', 'satori-forms'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Form Submissions', 'satori-forms') . '</h1>';
        echo '<p>' . esc_html__('Submissions are read-only in Phase F1.', 'satori-forms') . '</p>';

        $submissions = $this->fetch_submissions();

        if ($submissions === []) {
            echo '<p>' . esc_html__('No submissions found.', 'satori-forms') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('ID', 'satori-forms') . '</th>';
        echo '<th>' . esc_html__('Form', 'satori-forms') . '</th>';
        echo '<th>' . esc_html__('Submitted At', 'satori-forms') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($submissions as $submission) {
            $formTitle = $submission['form_title'] ?: __('(Deleted Form)', 'satori-forms');
            $formLink = $submission['form_id'] ? get_edit_post_link($submission['form_id']) : '';
            $submittedAt = $this->format_submission_date($submission['submitted_at']);

            echo '<tr>';
            echo '<td>' . esc_html((string) $submission['id']) . '</td>';
            echo '<td>';
            if ($formLink !== '') {
                echo '<a href="' . esc_url($formLink) . '">' . esc_html($formTitle) . '</a>';
            } else {
                echo esc_html($formTitle);
            }
            echo '</td>';
            echo '<td>' . esc_html($submittedAt) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    private function fetch_submissions(): array
    {
        global $wpdb;

        $table = SubmissionTable::table_name();
        $posts = $wpdb->posts;

        $query = "SELECT submissions.id, submissions.form_id, submissions.submitted_at, posts.post_title AS form_title
            FROM {$table} AS submissions
            LEFT JOIN {$posts} AS posts ON posts.ID = submissions.form_id
            ORDER BY submissions.submitted_at DESC
            LIMIT 100";

        $results = $wpdb->get_results($query, ARRAY_A);
        if (!is_array($results)) {
            return [];
        }

        return $results;
    }

    private function format_submission_date(string $submittedAt): string
    {
        $local = get_date_from_gmt($submittedAt, 'Y-m-d H:i:s');
        $timestamp = $local ? strtotime($local) : strtotime($submittedAt);

        if ($timestamp === false) {
            return $submittedAt;
        }

        return date_i18n(
            get_option('date_format') . ' ' . get_option('time_format'),
            $timestamp
        );
    }
}
