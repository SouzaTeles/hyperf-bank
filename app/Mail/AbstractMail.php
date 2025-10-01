<?php

declare(strict_types=1);

namespace App\Mail;

use App\Service\MjmlService;
use Hyperf\Di\Annotation\Inject;
use RuntimeException;
use Symfony\Component\Mime\Email;

abstract class AbstractMail
{
    protected const TEMPLATES_PATH = BASE_PATH . '/resources/mail/';

    #[Inject]
    protected MjmlService $mjmlService;

    /**
     * Build the email message
     */
    abstract public function build(string $toEmail): Email;

    /**
     * Get template variables
     */
    abstract protected function getTemplateVariables(): array;

    /**
     * Get email subject
     */
    abstract protected function getSubject(): string;

    /**
     * Get sender email
     */
    protected function getFromEmail(): string
    {
        return 'noreply@hyperfbank.com';
    }

    /**
     * Get template path based on class name
     * Example: WithdrawConfirmationMail -> withdraw-confirmation.mjml
     */
    protected function getTemplatePath(): string
    {
        // Get class name without namespace
        $fullClassName = static::class;
        $className = substr($fullClassName, strrpos($fullClassName, '\\') + 1);
        
        // Remove "Mail" suffix if exists
        $className = preg_replace('/Mail$/', '', $className);
        
        // Convert PascalCase to kebab-case
        $templateName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
        
        return self::TEMPLATES_PATH . $templateName . '.mjml';
    }

    /**
     * Render template with variables
     */
    protected function renderTemplate(): string
    {
        $templatePath = $this->getTemplatePath();
        
        if (!file_exists($templatePath)) {
            throw new RuntimeException("Email template not found: {$templatePath}");
        }
        
        return $this->mjmlService->render($templatePath, $this->getTemplateVariables());
    }
}
