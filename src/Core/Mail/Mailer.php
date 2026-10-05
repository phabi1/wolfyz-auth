<?php

namespace App\Core\Mail;

use App\Core\Config\Parameters;
use App\Core\Mvc\View\View;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer
{
    private $view;

    private $from;

    private $smtp;

    public function __construct(View $view, Parameters $parameters)
    {
        $this->view = $view;
        $this->from = $parameters->get('mail.from');
        $this->smtp = $parameters->get('mail.smtp');
    }

    public function sendMail(string $to, string $template, array $variables, array $options = []): bool
    {
        if (!$this->isConfigured()) {
            throw new \PHPMailer\PHPMailer\Exception('Mail sender or SMTP server is not configured.');
        }
        // Implement the logic to send an email using WordPress's wp_mail function
        $subject = $this->getSubjectFromTemplate($template, $variables);
        $html = $this->getMessageFromTemplate($template, $variables);
        $text = $this->getTextFromTemplate($template, $variables);

        return $this->send($to, $subject, $html, $text, $options);
    }

    public function isConfigured(): bool
    {
        return is_array($this->from) && is_array($this->smtp)
            && filter_var($this->from['email'] ?? '', FILTER_VALIDATE_EMAIL) !== false
            && is_string($this->smtp['host'] ?? null) && $this->smtp['host'] !== ''
            && (int) ($this->smtp['port'] ?? 0) > 0;
    }

    private function send(string $to, string $subject, string $html, string $text, array $options = []): bool
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $this->smtp['host'];
        $mail->Port = $this->smtp['port'];                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
       
        if (!empty($this->smtp['username']) && !empty($this->smtp['password'])) {
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp['username'];
            $mail->Password = $this->smtp['password'];
        } else {
            $mail->SMTPAuth = false;
        }
        $secure = $this->smtp['encryption'] ?? '';
        $mail->SMTPSecure = $secure;

        //Recipients
        $mail->setFrom($this->from['email'], $this->from['name'] ?? '');
        $mail->addAddress($to);     //Add a recipient

        $replyTo = $options['reply_to'] ?? null;
        if (isset($replyTo)) {
            $mail->addReplyTo($replyTo['email'], $replyTo['name'] ?? '');
        }

        if (isset($options['cc'])) {
            $mail->addCC($options['cc']);
        }
        if (isset($options['bcc'])) {
            $mail->addBCC($options['bcc']);
        }

        //Attachments
        $attachments = $options['attachments'] ?? [];
        foreach ($attachments as $attachment) {
            if (is_array($attachment)) {
                $mail->addAttachment($attachment[0], $attachment[1] ?? '');
            } else {
                $mail->addAttachment($attachment);
            }
        }

        //Content
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = $subject;
        if (!empty($html)) {
            $mail->Body = $html;
        }
        if (!empty($text)) {
            $mail->AltBody = $text;
        }

        if ($mail->send()) {
            return true;
        }
        return false;
    }

    private function getSubjectFromTemplate(string $templateName, array &$variables): string
    {
        $path = $this->getTemplatePath($templateName, 'subject');
        return $this->renderTemplate($path, $variables);
    }

    private function getMessageFromTemplate(string $templateName, array &$variables): string
    {
        $path = $this->getTemplatePath($templateName, 'html');
        return $this->renderTemplate($path, $variables);
    }

    private function getTextFromTemplate(string $templateName, array &$variables): string
    {
        $path = $this->getTemplatePath($templateName, 'text');
        return $this->renderTemplate($path, $variables);
    }

    private function getTemplatePath(string $templateName, string $type): string
    {
        return 'mails/' . $templateName . '.' . $type;
    }

    private function renderTemplate(string $templatePath, array &$variables): string
    {
        return $this->view->render($templatePath, $variables);
    }
}