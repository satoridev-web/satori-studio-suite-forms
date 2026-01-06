<?php
/**
 * ================================================================
 * SATORI Forms Core Plugin
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Core;

final class Plugin
{
    private FormCPT $formCpt;
    private FormSchema $formSchema;

    public function __construct()
    {
        $this->formCpt = new FormCPT();
        $this->formSchema = new FormSchema();
    }

    public function register_hooks(): void
    {
        add_action('plugins_loaded', [$this, 'initialize']);
    }

    public function initialize(): void
    {
        $this->formCpt->register_hooks();
        $this->formSchema->register_hooks();
    }
}
