<?php

namespace App\Controller;

use App\Services\DefaultValue;
use App\Services\EMailer;
use App\Services\LDAPAuthentication;
use App\Services\APIMethods;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TrialController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $ldapAuthenticator;
    private $emailer;
    private $defaultValue;
    private $loggerg;
    private $loggerv1;
    private $loggerv2;
    private $loggerv5;
    private $api_methods;

    public function __construct(LoggerInterface $generalapiLogger, LoggerInterface $groupapi1Logger, LoggerInterface $groupapi2Logger, LoggerInterface $groupapi5Logger, LDAPAuthentication $ldapAuther, EMailer $emailer, DefaultValue $defVal, APIMethods $api_methods)
    {
        $this->ldapAuthenticator = $ldapAuther;
        $this->emailer = $emailer;
        $this->defaultValue = $defVal;
        $this->loggerg = $generalapiLogger;
        $this->loggerv1 = $groupapi1Logger;
        $this->loggerv2 = $groupapi2Logger;
        $this->loggerv5 = $groupapi5Logger;
        $this->api_methods = $api_methods;
    }

    /**
     * @Route("/trial/testldap", name="trial_test_ldap")
     */
    public function trialTestLdap(): Response
    {
        if ($this->ldapAuthenticator->isPasswordValid('bose.vipin@nic.in', 'asdas')) {
            $message = 'Login OK';
        } else {
            $message = 'Login Failed';
        }

        return $this->render('/trial.html.twig', ['message' => $message]);
    }

    /**
     * @Route("/trial/logs", name="trial_logs")
     */
    public function trialLogs(Request $request): Response
    {
        $this->loggerg->info('Trial hit successfull');
        $this->loggerv1->info('Trial hit successfull');
        $this->loggerv2->info('Trial hit successfull');
        $this->loggerv5->info('Trial hit successfull');
        die;
    }

    /**
     * @Route("/trial/hostip", name="trial_test_hostip")
     */
    public function trialHostIP(Request $request): Response
    {
        $message = $request->getHost();

        return $this->render('/trial.html.twig', ['message' => $message]);
    }

    /**
     * @Route("/trial/email", name="trial_test_email")
     */
    public function trialEmail(Request $request)
    {
        $url = $this->defaultValue->getDefaultValue('LDAP_SERVER_URL');
        $mailStatus = $this->emailer->sendEmail('bose.vipin@nic.in', 'Test Email', 'Test Subject');

        return $this->render('/trial.html.twig', ['mailStatus' => $mailStatus]);
    }

    /**
     * @Route("/trial/emailnotifyapp", name="trial_test_emailnotifyapp")
     */
    public function trialEmailNotifyApp(Request $request)
    {
        return $this->render('/emailer/notify_app_download.html.twig');
    }

    /**
     * @Route("/trial/welcome", name="trial_test_welcom")
     */
    public function trialWelcome(Request $request)
    {
        return $this->render('/emailer/welcome.html.twig');
    }

    /**
     * @Route("/trial/emaildowntime", name="trial_emaildowntime")
     */
    public function trialEmailDownTime(Request $request)
    {
        return $this->render('/emailer/notify_app_downtime.html.twig');
    }

    /**
     * @Route("/trial/devicesettings", name="trial_devicesettings")
     */
    public function trialDeviceSettings(Request $request)
    {
        $client = new Client(['verify' => false]);
        $url = $url = $this->generateUrl('app_devicesettings', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $form_params = ['os' => 'Android', 'make' => 'Generic', 'model' => 'Generic'];
        $response = $client->request('POST', $url, ['body' => json_encode($form_params)]);
        $body = $response->getBody();

        return $this->render('/trial/devicesettings.html.twig', ['responsebody' => $body]);
    }

    /**
     * @Route("/trial/xml", name="trial_xml")
     */
    public function trialXml(Request $request)
    {
        $xmlTemplate = <<<XML
        <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
        <plist version="1.0">
        <dict>
            <key>items</key>
            <array>
                <dict>
                    <key>assets</key>
                    <array>
                        <dict>
                            <key>kind</key>
                            <string>software-package</string>
                            <key>url</key>
                            <string>__sp__</string>
                        </dict>
                        <dict>
                            <key>kind</key>
                            <string>display-image</string>
                            <key>url</key>
                            <string>__di__</string>
                        </dict>
                        <dict>
                            <key>kind</key>
                            <string>full-size-image</string>
                            <key>url</key>
                            <string>__fsi__</string>
                        </dict>
                    </array>
                    <key>metadata</key>
                    <dict>
                        <key>bundle-identifier</key>
                        <string>www.gims.gov.in</string>
                        <key>bundle-version</key>
                        <string>1.0</string>
                        <key>kind</key>
                        <string>software</string>
                        <key>title</key>
                        <string>gim</string>
                    </dict>
                </dict>
            </array>
        </dict>
        </plist>
XML;
        $rootNode = new \SimpleXMLElement($xmlTemplate);

        return new Response($rootNode->asXML());
    }

    /**
     * @Route("/trial/gims/message", name="trial_gims_message")
     */
    public function trialGimsMessage(Request $request)
    {
        $client = new Client(['verify' => false]);
        $url = $url = $this->generateUrl('app_devicesettings', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $form_params = ['os' => 'Android', 'make' => 'Generic', 'model' => 'Generic'];
        $response = $client->request('POST', $url, ['body' => json_encode($form_params)]);
        $body = $response->getBody();

        return $this->render('/trial/devicesettings.html.twig', ['responsebody' => $body]);
    }

    /**
     * @Route("/trial/otl/{emailid}", name="trial_otl_message")
     */
    public function trialOTL(Request $request, $emailid)
    {
        $toAddress = $emailid;
        $emailSubject = 'Sandes One Time Link for app download.';
        $emailContent = $this->renderView('emailer/otl_email_template.html.twig');
        $otlStatus = $this->emailer->sendEmail($toAddress, $emailSubject, $emailContent);
        if (strpos($otlStatus, 'EMail Sent!') !== false) {
            echo "Email attempt successful";
        } else {
            echo "Email attempt unsuccessful";
        }
        return $this->render('emailer/otl_email_template.html.twig');
    }
    /**
     * @Route("/trial/sms/{mobno}", name="trial_sms_message")
     */
    public function trialSMS(Request $request, $mobno)
    {
        $sms_mobileno = "91$mobno";
        $sms_message = "You are officially onboarded to Sandes, the Government instant messaging system. You may install Sandes app from https://www.sandes.gov.in/get and register using your mobile number +$sms_mobileno.Sandes-NICSI";
        $sms_template_id = "1107162383170334979";
        return new JsonResponse($this->api_methods->sendSMS(8, $sms_mobileno, $sms_message, $sms_template_id));
    }
}
