<?php
/**
 * PHPMailer Class namespace stub for standalone deployment
 */

namespace PHPMailer\PHPMailer;

class PHPMailer {
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';

    public string $Host = '';
    public bool $SMTPAuth = false;
    public string $Username = '';
    public string $Password = '';
    public string $SMTPSecure = '';
    public int $Port = 587;
    public string $CharSet = 'UTF-8';
    public string $Subject = '';
    public string $Body = '';
    public string $AltBody = '';
    public string $ErrorInfo = '';
    
    private array $to = [];
    private array $from = [];
    private bool $isHtml = false;

    public function __construct(bool $exceptions = false) {}

    public function isSMTP(): void {}

    public function setFrom(string $email, string $name = ''): void {
        $this->from = [$email, $name];
    }

    public function addAddress(string $email, string $name = ''): void {
        $this->to[] = [$email, $name];
    }

    public function isHTML(bool $isHtml = true): void {
        $this->isHtml = $isHtml;
    }

    public function send(): bool {
        // Fall back to standard native mail PHP call as simulation
        $toEmails = [];
        foreach ($this->to as $recipient) {
            $toEmails[] = $recipient[0];
        }
        $toStr = implode(', ', $toEmails);
        
        $headers = "MIME-Version: 1.0\r\n";
        if ($this->isHtml) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        $fromEmail = $this->from[0] ?? SMTP_FROM_EMAIL;
        $fromName = $this->from[1] ?? SMTP_FROM_NAME;
        $headers .= "From: $fromName <$fromEmail>\r\n";

        $res = mail($toStr, $this->Subject, $this->Body, $headers);
        if (!$res) {
            $this->ErrorInfo = "Native PHP mail dispatch failed.";
            throw new Exception($this->ErrorInfo);
        }
        return true;
    }
}
