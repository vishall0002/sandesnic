<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Exception;

class PortalMailer
{
    private $defaultValue;

    public function __construct(EntityManagerInterface $em, DefaultValue $defVal)
    {
        $this->emr = $em;
        $this->defaultValue = $defVal;
    }

    public function sendReleaseNote()
    {
        $em = $this->emr;
        $url = $this->defaultValue->getDefaultValue('LDAP_SERVER_URL');
        $appEnv = $em->getRepository('App:Masters\DefaultValue')->findOneByDefaultValue($hostName);
        $defaultValue = $em->getRepository('App:Masters\DefaultValue')->findOneBy(array('code' => $defaultValueCode, 'environment' => $appEnv->getEnvironment()));
        if ($defaultValue) {
            return $defaultValue->getDefaultValue();
        } else {
            return 0;
        }
    }

    public function sendEmail($toAddress, $emailSubject, $emailContent)
    {
        $email = (new Email())
        ->from('hello@example.com')
        ->to('you@example.com')
        //->cc('cc@example.com')
        //->bcc('bcc@example.com')
        //->replyTo('fabien@example.com')
        //->priority(Email::PRIORITY_HIGH)
        ->subject('Time for Symfony Mailer!')
        ->text('Sending emails is fun again!')
        ->html('<p>See Twig integration for better HTML integration!</p>');

    
        
        $transport = new EsmtpTransport('smtp://user:pass@example.com');
        $mailer = new Mailer($transport);
        $sentEmail = $mailer->send($email);
    }
}
