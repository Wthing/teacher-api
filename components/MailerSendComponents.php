<?php

namespace app\components;

use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Helpers\Builder\EmailParams;
use yii\base\Component;

class MailerSendComponents extends Component
{
    public $apiKey;
    public $fromEmail;
    public $fromName;

    protected $mailerSend;

    public function init()
    {
        parent::init();
        $this->mailerSend = new MailerSend(['api_key' => $this->apiKey]);
    }

    public function send($to, $subject, $html)
    {
        $recipients = [new Recipient($to, $to)];

        $emailParams = (new EmailParams())
            ->setFrom($this->fromEmail)
            ->setFromName($this->fromName)
            ->setRecipients($recipients)
            ->setSubject($subject)
            ->setHtml($html);

        try {
            $this->mailerSend->email->send($emailParams);
            return true;
        } catch (\Throwable $e) {
            \Yii::error('MailerSend error: ' . $e->getMessage());
            return false;
        }
    }
}
