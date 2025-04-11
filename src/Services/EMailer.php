<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EMailer
{
    private $defaultValue;
    private $entityManager;
    private $mailer;
    private $logsemailer;

    public function __construct(LoggerInterface $emailerLogger, EntityManagerInterface $em, DefaultValue $defVal, MailerInterface $mailer)
    {
        $this->entityManager = $em;
        $this->defaultValue = $defVal;
        $this->mailer = $mailer;
        $this->logsemailer = $emailerLogger;
    }

    public function sendReleaseNote()
    {
        $em = $this->entityManager;
        $url = $this->defaultValue->getDefaultValue('LDAP_SERVER_URL');
        $appEnv = $em->getRepository('App:Masters\DefaultValue')->findOneByDefaultValue($hostName);
        $defaultValue = $em->getRepository('App:Masters\DefaultValue')->findOneBy(['code' => $defaultValueCode, 'environment' => $appEnv->getEnvironment()]);
        if ($defaultValue) {
            return $defaultValue->getDefaultValue();
        } else {
            return 0;
        }
    }
    public function sendEmail($recipients, $emailSubject, $emailContent)
    {
        $status = 'false';
        $message = 'Init';
        $data = json_decode('init');
        $this->logsemailer->info('HIT ');
        $this->logsemailer->info('DATA - Recipients -> '. $recipients);
        $this->logsemailer->info('DATA - EMAIL Subject -> '. $emailSubject);
        $mail_body = <<<MAILSTR
From: SANDES - Support <support-sandes@nic.in>
To:  $recipients
Subject:  $emailSubject
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary=**=gimsemailgims98765
--**=gimsemailgims98765
Content-Type: text/html; charset="utf-8"
Content-Transfer-Encoding: 7bit

$emailContent

--**=gimsemailgims98765
MAILSTR;
        try {
            $client = new Client(['verify' => false]);
            $url = $this->defaultValue->getDefaultValue('EMAILER_URLV5');
            $headers = ['Recipients' => $recipients, 'Content-Type' => 'text/plain'];
            $response = $client->request('POST', $url, ['body' => $mail_body, 'headers' => $headers]);
            $body = $response->getBody();

            $status = 'true';
            $message = 'EMail Sent!';
            $data = json_decode($body);
            $this->logsemailer->info('DATA - SERVICE RETURN SUCCESS-> '. $body);
        } catch (Exception $e) {
            $status = 'false';
            $message = $e->getMessage();
            $this->logsemailer->info('DATA - EXCEPTION-> '. $message);
            $data = [];
        }
        // echo $message.PHP_EOL;

        return $message;
    }


    // Code maintained for Legacy
    // public function sendEmail($toAddress, $emailSubject, $emailContent)
    // {
    //     /*
    //       {
    //       "to":["arun.kv@nic.in"],
    //       "cc":["syam.krishna@nic.in"],
    //       "bcc":["sunish@nic.in"],
    //       "subject":"Hi",
    //       "content_type":"text/html",
    //       "message_body":"Hello World!"

    //       }
    //       CURL one liner -
    //       curl -X POST 'http://10.247.143.135:8081/v1/api/message/email' --data '{ "to": ["vipin.bose@gov.in"],"subject":"Hello Curl Oneliner", "content_type":"text/html", "message_body":"Hello World!" }'
    //       curl -X POST 'http://10.162.5.79:8085/v1/api/message/email' --data $'{ "to": ["vipin.bose@gov.in"],"subject":"Hello Curl Oneliner", "content_type":"text/html", "message_body":"Hello World!" }'

    //      */
    //     $status = 'false';
    //     $message = 'Init';
    //     $data = json_decode('init');

    //     try {
    //         $client = new Client(['verify' => false]);
    //         $url = $this->defaultValue->getDefaultValue('EMAILER_URL');
    //         $form_params = ['to' => [$toAddress], 'subject' => $emailSubject, 'content_type' => 'text/html', 'message_body' => $emailContent];
    //         $response = $client->request('POST', $url, ['body' => json_encode($form_params)]);
    //         $body = $response->getBody();

    //         $vbClient = new Client(['verify' => false]);
    //         $form_params = ['to' => ['vipin.bose@gov.in'], 'subject' => $emailSubject, 'content_type' => 'text/html', 'message_body' => $emailContent];
    //         $vbResponse = $vbClient->request('POST', $url, ['body' => json_encode($form_params)]);
    //         $vbBody = $vbResponse->getBody();

    //         $status = 'true';
    //         $message = 'EMail Sent!';
    //         $data = json_decode($body);
    //     } catch (ClientException $ce) {
    //         $status = 'false';
    //         $message = $ce->getMessage();
    //         $data = [];
    //     } catch (RequestException $re) {
    //         $status = 'false';
    //         $message = $re->getMessage();
    //         $data = [];
    //     } catch (Exception $e) {
    //         $status = 'false';
    //         $message = $e->getMessage();
    //         $data = [];
    //     }

    //     return $message.'---'.$emailContent;
    // }

    public function sendEmailV5($recipients, $emailSubject, $emailContent, $attachment_file_name_with_path)
    {
        $data = file_get_contents($attachment_file_name_with_path);
        $b64attachment = chunk_split(base64_encode($data));
        $status = 'false';
        $message = 'Init';
        $data = json_decode('init');
        
        $report_date = new \DateTime(); 
        $report_date->modify("-1 day");
        $attachment_name = "Sandes_Statistics_OU_". $report_date->format("dmY").".pdf";

        $mail_body = <<<MAILSTR
From: SANDES - Support <support-sandes@nic.in>
To:  $recipients
Subject: SANDES - Instant Review Statistics (AUTO)
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary=**=gimsemailgims98765
--**=gimsemailgims98765
Content-Type: text/html; charset="utf-8"
Content-Transfer-Encoding: 7bit

Dear Sir,
<br/>
<br/>
<br/>
This is an automated e-mail generated for instant review of  SANDES. 
<br/>
<br/>
Please find the attached screenshot, that shows SANDES Statistics.
<br/>
<br/>
Organization Admins, OU Admins, Nodal Officers shall receive this e-mail daily for their respective Organizations.
<br />
<br />
This mail has been sent with approval of HOG SANDES
<br />
<br />
<br />

Regards
<br/>
<strong>Team SANDES</strong>
<br/>


--**=gimsemailgims98765
Content-Transfer-Encoding: base64
Content-Type: application/pdf; name="$attachment_name"
Content-Disposition: attachment; filename="$attachment_name"

$b64attachment

MAILSTR;
        try {
            $client = new Client(['verify' => false]);
            $url = $this->defaultValue->getDefaultValue('EMAILER_URLV5');
            $headers = ['Recipients' => $recipients, 'Content-Type' => 'text/plain'];
            $response = $client->request('POST', $url, ['body' => $mail_body, 'headers' => $headers]);
            $body = $response->getBody();

            $status = 'true';
            $message = 'EMail Sent!';
            $data = json_decode($body);
        } catch (Exception $e) {
            $status = 'false';
            $message = $e->getMessage();
            $data = [];
        }
        echo $message.PHP_EOL;

        return $message;
    }
    public function sendEmailGenericV5($recipients, $emailSubject, $emailContent, $attachment_file_name_with_path)
    {
        $data = file_get_contents($attachment_file_name_with_path);
        $b64attachment = chunk_split(base64_encode($data));
        $status = 'false';
        $message = 'Init';
        $data = json_decode('init');
        
        $attachment_name = basename($attachment_file_name_with_path);
        $attachment_mimetype = mime_content_type($attachment_file_name_with_path);

        $mail_body = <<<MAILSTR
From: SANDES - Support <support-sandes@nic.in>
To:  $recipients
Subject: SANDES Portal
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary=**=gimsemailgims98765
--**=gimsemailgims98765
Content-Type: text/html; charset="utf-8"
Content-Transfer-Encoding: 7bit

$emailContent

--**=gimsemailgims98765
Content-Transfer-Encoding: base64
Content-Type: $attachment_mimetype; name="$attachment_name"
Content-Disposition: attachment; filename="$attachment_name"

$b64attachment

MAILSTR;
        try {
            $client = new Client(['verify' => false]);
            $url = $this->defaultValue->getDefaultValue('EMAILER_URLV5');
            $headers = ['Recipients' => $recipients, 'Content-Type' => 'text/plain'];
            $response = $client->request('POST', $url, ['body' => $mail_body, 'headers' => $headers]);
            $body = $response->getBody();
            $status = 'true';
        } catch (Exception $e) {
            $status = 'false';
        }
        return $status;
    }

    public function sendToManagersV5($attachment_file_name_with_path)
    {
        $data = file_get_contents($attachment_file_name_with_path);
        $b64attachment = chunk_split(base64_encode($data));
        // $recipients = 'manoj.pa@nic.in,arun.kv@nic.in,sunish@nic.in,syam.krishna@nic.in,abby.murali@nic.in,vipin.bose@gov.in';
        $recipients = 'muthu@nic.in,sapna.kapoor@nic.in,manoj.pa@nic.in,arun.kv@nic.in,sunish@nic.in,syam.krishna@nic.in,abby.murali@nic.in,pkmalik@nic.in,deepak.mittal@nic.in';
        // $recipients = 'arun.kv@nic.in,sunish@nic.in,syam.krishna@nic.in,abby.murali@nic.in,vipin.bose@gov.in';
        $rtmp_to = 'G. Mayil Muthu Kumaran <muthu@nic.in>';
        $rtmp_cc = 'Suchitra Pyarelal <suchitra@nic.in>, Ms. Sapna Kapoor <sapna.kapoor@nic.in>, Manoj PA <manoj.pa@nic.in>, Pramod Kumar <pkmalik@nic.in>, Deepak Mittal <deepak.mittal@nic.in>';
        $rtmp_bcc = 'Arun K Varghese <arun.kv@nic.in>, Sunish <sunish@nic.in>, Abby Murali <abby.murali@nic.in>, Syamkrishna B G <syam.krishna@nic.in>, Vipin Bose <vipin.bose@gov.in>';
        $status = 'false';
        $message = 'Init';
        $data = json_decode('init');
        $report_date = new \DateTime(); 
        $report_date->modify("-1 day");
        $attachment_name = "Sandes_Statistics_ORG_". $report_date->format("dmY").".pdf";
        $mail_body = <<<MAILSTR
From: SANDES - Support <support-sandes@nic.in>
To:  $rtmp_to
Cc: $rtmp_cc
Bcc: $rtmp_bcc
Subject: SANDES - Organization Wise Statistics (AUTO)
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary=**=gimsemailgims98765
--**=gimsemailgims98765
Content-Type: text/html; charset="utf-8"
Content-Transfer-Encoding: 7bit

Dear Sir,
<br />
<br />
<br />
This is an automated e-mail generated for instant review of  SANDES. 
<br />
<br />
Please find the attached screenshot, that  shows Organization Wise Statistics.
<br />
<br />
This mail has been sent with approval of HOG SANDES
<br />
<br />
<br />
Regards<br />
<strong>Team SANDES</strong>

--**=gimsemailgims98765
Content-Transfer-Encoding: base64
Content-Type: application/pdf; name="$attachment_name"
Content-Disposition: attachment; filename="$attachment_name"

$b64attachment

MAILSTR;
        try {
            $client = new Client(['verify' => false]);
            $url = $this->defaultValue->getDefaultValue('EMAILER_URLV5');
            $headers = ['Recipients' => $recipients, 'Content-Type' => 'text/plain'];
            $response = $client->request('POST', $url, ['body' => $mail_body, 'headers' => $headers]);
            $body = $response->getBody();

            $status = 'true';
            $message = 'EMail Sent!';
            $data = json_decode($body);
        } catch (Exception $e) {
            $status = 'false';
            $message = $e->getMessage();
            $data = [];
        }
        echo $message.PHP_EOL;

        return $message;
    }

    public function sendEmailFromSupport($toAddress, $emailSubject, $emailContent, $attachPath)
    {
        $email = (new Email());
        $email->from($this->defaultValue->getDefaultValue('EMAILER-SUPPORT-FROM-ADDRESS'));
        $email->to($toAddress);
        if (1 === $this->defaultValue->getDefaultValue('EMAILER-DEBUG')) {
            $email->bcc('vipin.bose@gov.in');
        }
        $email->subject($emailSubject);
        $email->html($emailContent);
        if ($attachPath) {
            $email->attachFromPath($attachPath);
        }

        $sentEmail = $this->mailer->send($email);

        return $sentEmail;
    }
}
