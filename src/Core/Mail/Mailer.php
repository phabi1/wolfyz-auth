<?php

namespace App\Core\Mail;

class Mailer
{
    public function sendMail(string $to, string $template, array $variables): bool
    {
        list($plugin, $templateName) = explode(':', $template, 2);

        // Implement the logic to send an email using WordPress's wp_mail function
        $subject = $this->getSubjectFromTemplate($plugin, $templateName, $variables);
        $message = $this->getMessageFromTemplate($plugin, $templateName, $variables);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];

        return wp_mail($to, $subject, $message, $headers);

    }

    private function getSubjectFromTemplate(string $pluginName, string $templateName, array &$variables): string
    {
        $path = $this->getTemplatePath($pluginName, $templateName, 'subject');
        if (!file_exists($path)) {
            throw new \Exception("Subject template file not found: $path");
        }

        return $this->renderTemplate($path, $variables);
    }

    private function getMessageFromTemplate(string $pluginName, string $templateName, array &$variables): string
    {
        $path = $this->getTemplatePath($pluginName, $templateName, 'html');
        if (!file_exists($path)) {
            throw new \Exception("Message template file not found: $path");
        }

        return $this->renderTemplate($path, $variables);
    }

    private function getTemplatePath(string $pluginName, string $templateName, string $type): string
    {
        return WP_PLUGIN_DIR . '/' . $pluginName . '/mails/' . $templateName . '.' . $type . '.php';
    }

    private function renderTemplate(string $templatePath, array &$variables): string
    {
        ob_start();
        extract($variables);
        include $templatePath;
        return ob_get_clean();
    }
}