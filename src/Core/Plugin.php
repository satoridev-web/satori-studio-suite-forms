<?php
/**
 * ================================================================
 * SATORI Forms Core Plugin
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Core;

use Satori\Forms\Admin\FormAdmin;
use Satori\Forms\Admin\SubmissionAdmin;

final class Plugin
{
    private FormCPT $formCpt;
    private FormSchema $formSchema;
    private FormAdmin $formAdmin;
    private SubmissionAdmin $submissionAdmin;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->formCpt = new FormCPT();
        $this->formSchema = new FormSchema();
        $this->formAdmin = new FormAdmin();
        $this->submissionAdmin = new SubmissionAdmin();
        $this->notificationService = new NotificationService();
    }

    public function register_hooks(): void
    {
        add_action('plugins_loaded', [$this, 'initialize']);
    }

    public function initialize(): void
    {
        $this->formCpt->register_hooks();
        $this->formSchema->register_hooks();
        $this->formAdmin->register_hooks();
        $this->submissionAdmin->register_hooks();
        $this->notificationService->register_hooks();
    }
}
