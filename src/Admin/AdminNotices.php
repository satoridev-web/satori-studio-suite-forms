<?php
/**
 * ================================================================
 * SATORI Forms Admin Notices
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Admin;

use Plugin_Upgrader;
use Satori\Forms\Core\FormCPT;
use WP_Error;

final class AdminNotices
{
    private const ACTIVATION_OPTION = 'satori_forms_acf_activation_notice';
    private const DISMISSED_META = 'satori_forms_acf_notice_dismissed';
    private const INSTALL_ACTION = 'satori_forms_install_acf';
    private const DISMISS_ACTION = 'satori_forms_dismiss_acf_notice';
    private const PLUGIN_SLUG = 'advanced-custom-fields';
    private const PLUGIN_FILE = 'advanced-custom-fields/acf.php';
    private const STATUS_QUERY_ARG = 'satori_acf_status';

    public function register_hooks(): void
    {
        if (!is_admin()) {
            return;
        }

        add_action('admin_notices', [$this, 'render_activation_notice']);
        add_action('admin_notices', [$this, 'render_contextual_notice']);
        add_action('admin_notices', [$this, 'render_status_notice']);
        add_action('admin_post_' . self::INSTALL_ACTION, [$this, 'handle_install_action']);
        add_action('admin_post_' . self::DISMISS_ACTION, [$this, 'handle_dismiss_action']);
    }

    public static function mark_activation_notice(): void
    {
        update_option(self::ACTIVATION_OPTION, '1');
    }

    public function render_activation_notice(): void
    {
        if (!$this->should_show_activation_notice()) {
            return;
        }

        $installUrl = esc_url($this->get_install_action_url());
        $dismissUrl = esc_url($this->get_dismiss_action_url());

        echo '<div class="notice notice-info">';
        echo '<p>' . esc_html__('Optional admin tooling: install Advanced Custom Fields (Free) to enable the visual form schema editor.', 'satori-forms') . '</p>';
        echo '<p>';
        echo '<a class="button button-primary" href="' . $installUrl . '">' . esc_html__('Install & Activate ACF Free', 'satori-forms') . '</a> ';
        echo '<a class="button" href="' . $dismissUrl . '">' . esc_html__('Dismiss', 'satori-forms') . '</a>';
        echo '</p>';
        echo '</div>';
    }

    public function render_contextual_notice(): void
    {
        if (ACFIntegration::is_available()) {
            return;
        }

        if (!$this->is_form_screen()) {
            return;
        }

        if (!current_user_can('install_plugins')) {
            return;
        }

        $installUrl = esc_url($this->get_install_action_url());

        echo '<div class="notice notice-warning">';
        echo '<p>' . esc_html__('Install Advanced Custom Fields (Free) to enable the visual schema editor for this form.', 'satori-forms') . '</p>';
        echo '<p><a class="button" href="' . $installUrl . '">' . esc_html__('Install & Activate ACF Free', 'satori-forms') . '</a></p>';
        echo '</div>';
    }

    public function render_status_notice(): void
    {
        $status = filter_input(INPUT_GET, self::STATUS_QUERY_ARG, FILTER_UNSAFE_RAW);
        if (!is_string($status) || $status === '') {
            return;
        }

        $status = sanitize_text_field(wp_unslash($status));
        if ($status === 'success') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html__('Advanced Custom Fields is now active. The schema editor is ready to use.', 'satori-forms') . '</p>';
            echo '</div>';
            return;
        }

        if ($status === 'error') {
            echo '<div class="notice notice-error">';
            echo '<p>' . esc_html__('Unable to install Advanced Custom Fields. Please install it from the Plugins screen.', 'satori-forms') . '</p>';
            echo '</div>';
        }
    }

    public function handle_install_action(): void
    {
        if (!current_user_can('install_plugins')) {
            wp_die(esc_html__('You do not have permission to install plugins.', 'satori-forms'));
        }

        check_admin_referer(self::INSTALL_ACTION);

        $result = $this->install_and_activate_acf();
        $status = $result === true ? 'success' : 'error';

        if ($result === true) {
            delete_option(self::ACTIVATION_OPTION);
            $this->mark_dismissed();
        }

        $redirect = wp_get_referer();
        if (!is_string($redirect) || $redirect === '') {
            $redirect = admin_url('plugins.php');
        }

        $redirect = add_query_arg(self::STATUS_QUERY_ARG, $status, $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    public function handle_dismiss_action(): void
    {
        check_admin_referer(self::DISMISS_ACTION);
        $this->mark_dismissed();
        delete_option(self::ACTIVATION_OPTION);

        $redirect = wp_get_referer();
        if (!is_string($redirect) || $redirect === '') {
            $redirect = admin_url();
        }

        wp_safe_redirect($redirect);
        exit;
    }

    private function should_show_activation_notice(): bool
    {
        if (!get_option(self::ACTIVATION_OPTION)) {
            return false;
        }

        if (ACFIntegration::is_available()) {
            return false;
        }

        if (!current_user_can('install_plugins')) {
            return false;
        }

        return !get_user_meta(get_current_user_id(), self::DISMISSED_META, true);
    }

    private function is_form_screen(): bool
    {
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }

        if ($screen->post_type !== FormCPT::POST_TYPE) {
            return false;
        }

        return in_array($screen->base, ['edit', 'post'], true);
    }

    private function get_install_action_url(): string
    {
        return wp_nonce_url(
            admin_url('admin-post.php?action=' . self::INSTALL_ACTION),
            self::INSTALL_ACTION
        );
    }

    private function get_dismiss_action_url(): string
    {
        return wp_nonce_url(
            admin_url('admin-post.php?action=' . self::DISMISS_ACTION),
            self::DISMISS_ACTION
        );
    }

    private function mark_dismissed(): void
    {
        update_user_meta(get_current_user_id(), self::DISMISSED_META, 1);
    }

    private function install_and_activate_acf(): bool|WP_Error
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        if (is_plugin_active(self::PLUGIN_FILE)) {
            return true;
        }

        if (!file_exists(WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE)) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

            $api = plugins_api(
                'plugin_information',
                [
                    'slug' => self::PLUGIN_SLUG,
                    'fields' => [
                        'sections' => false,
                    ],
                ]
            );

            if (is_wp_error($api)) {
                return $api;
            }

            $upgrader = new Plugin_Upgrader(new \Automatic_Upgrader_Skin());
            $result = $upgrader->install($api->download_link);

            if ($result === false || is_wp_error($result)) {
                return $result instanceof WP_Error ? $result : new WP_Error('satori_forms_acf_install_failed');
            }
        }

        $activateResult = activate_plugin(self::PLUGIN_FILE);
        if (is_wp_error($activateResult)) {
            return $activateResult;
        }

        return true;
    }
}
