<?php
/**
 * ================================================
 * SATORI Forms Core Plugin
 * ================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Core;

final class Plugin
{
    public function __construct()
    {
    }

    public function register_hooks(): void
    {
        add_action('plugins_loaded', [$this, 'initialize']);
    }

    public function initialize(): void
    {
    }
}
