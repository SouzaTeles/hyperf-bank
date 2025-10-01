<?php

declare(strict_types=1);

namespace App\Service;

use NotFloran\MjmlBundle\Renderer\BinaryRenderer;

class MjmlService
{
    private BinaryRenderer $renderer;

    public function __construct()
    {
        // Uses MJML CLI binary (requires Node.js and mjml installed globally)
        // BinaryRenderer(string $bin, bool $strict = true, string $validationLevel = 'soft')
        $this->renderer = new BinaryRenderer(
            'mjml',  // Path to mjml binary
            true,    // Strict mode
            'soft'   // Validation level: 'strict', 'soft', or 'skip'
        );
    }

    /**
     * Render MJML template to HTML
     * 
     * @param string $templatePath Path to MJML template file
     * @param array $variables Variables to replace in template
     * @return string Compiled HTML
     */
    public function render(string $templatePath, array $variables = []): string
    {
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("MJML template not found: {$templatePath}");
        }

        $mjml = file_get_contents($templatePath);
        
        // Replace variables in MJML
        foreach ($variables as $key => $value) {
            $mjml = str_replace("{{" . $key . "}}", htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $mjml);
        }
        
        // Compile MJML to HTML
        $html = $this->renderer->render($mjml);
        
        return $html;
    }
}
