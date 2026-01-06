<?php
/**
 * Plugin Name: SATORI Forms
 * Description: Core bootstrap for the SATORI Forms plugin.
 * Version: 0.1.0
 * Author: SATORI
 * License: GPL-2.0-or-later
 * Text Domain: satori-forms
 */

/**
 * ================================================
 * SATORI Forms Bootstrap
 * ================================================
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('SATORI_FORMS_VERSION', '0.1.0');
define('SATORI_FORMS_PATH', __DIR__ . '/');

require_once SATORI_FORMS_PATH . 'src/autoload.php';

$plugin = new Satori\Forms\Core\Plugin();
$plugin->register_hooks();
