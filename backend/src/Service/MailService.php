<?php

declare(strict_types=1);

namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    public function sendNotification(array $record): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $getData = json_decode($record['get_data'] ?? '{}', true) ?: [];
        $postData = json_decode($record['post_data'] ?? '{}', true) ?: [];
        $cookieData = json_decode($record['cookie_data'] ?? '{}', true) ?: [];

        $subject = sprintf(
            'GET:%d个 POST:%d个 Cookie:%d个',
            count($getData),
            count($postData),
            count($cookieData)
        );

        $body = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $body = str_replace("\n", '<br/>', $body);
        $body = str_replace(' ', '&nbsp;', $body);

        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = $this->config['smtp_host'];
        $mail->Port = $this->config['smtp_port'];
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = $this->config['smtp_secure'];
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Username = $this->config['username'];
        $mail->Password = $this->config['password'];
        $mail->Subject = $subject;
        $mail->setFrom($this->config['from'], '通知');
        $mail->addAddress($this->config['to']);
        $mail->isHTML(true);
        $mail->Body = $body;

        return $mail->send();
    }
}
