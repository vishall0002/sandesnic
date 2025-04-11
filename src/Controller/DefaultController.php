<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Portal\DeviceSettings;
use App\Services\LDAPAuthentication;
use App\Services\AppServices;
use Doctrine\DBAL\FetchMode;
use App\Services\XMPPGeneral;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class DefaultController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $LDAPAuthentication;

    public function __construct(LDAPAuthentication $ldapauthenticator,XMPPGeneral $xmpp)
    {
        $this->LDAPAuthentication = $ldapauthenticator;
        $this->xmppGeneral = $xmpp;
    }
    /**
     * @Route("/", name="app_home")
     */
    public function home(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $androidCurrent = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'ANDROID', 'isCurrent' => true]);
        $androidBeta = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'ANDROID', 'isBeta' => true]);
        $iosCurrent = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'isCurrent' => true]);
        $iosBeta = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS']);
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_dashboard');
        }
        if ('PRODUCTION' === $_ENV['RUNNING_MODE']) {
            return $this->render('home_production.html.twig',['iosCurrent' => $iosCurrent, 'iosBeta' => $iosBeta, 'androidCurrent' => $androidCurrent, 'androidBeta' => $androidBeta]);
        } elseif ('NPRODUCTION' === $_ENV['RUNNING_MODE']) {
            return $this->render('home_production.html.twig',['iosCurrent' => $iosCurrent, 'iosBeta' => $iosBeta, 'androidCurrent' => $androidCurrent, 'androidBeta' => $androidBeta]);
        } else {
            return $this->render('home_production.html.twig',['iosCurrent' => $iosCurrent, 'iosBeta' => $iosBeta, 'androidCurrent' => $androidCurrent, 'androidBeta' => $androidBeta]);
        } 
    }

    /**
     * @Route("/homep", name="app_home_production")
     */
    public function homeProduction(Request $request): Response
    {
        return $this->render('home_production.html.twig');
        
    }
    /**
     * @Route("/homes", name="app_home_staging")
     */
    public function homeStaging(Request $request): Response
    {

        return $this->render('home_staging.html.twig');
        
    }
    private function websiteColor($hue) {
        $darkColor = static function (float $alpha = 1) use ($hue) {
            return "hsla($hue, 100%, 45%, $alpha)";
        };
        $lightColor = static function (float $alpha = 1) use ($hue) {
            return "hsla($hue, 100%, 95%, $alpha)";
        };
        $result = [];
        $result['lightClr'] = $lightColor();
        $result['darkClr'] = $darkColor();
        $result['darkClrMod'] = $darkColor(0.75);
        return $result;
    }
    /**
     * @Route("/releasenotes", name="app_releasenotes")
     */
    public function releaseNotes(): Response
    {
        return $this->render('/default/release_notes.html.twig');
    }

    /**
     * @Route("/startweb", name="app_gims_web_start" )
     */
    public function startweb(SessionInterface $session,Request $request): Response
    {
        $user = $this->getUser();
        if ($user->getEmail() == ""){
            $gimsWebSession = json_encode($this->LDAPAuthentication->getGIMSWebSession($user->getEmail(), "nic*123"));
        } else {
            $gimsWebSession = json_encode($this->LDAPAuthentication->getGIMSWebSession($user->getUsername(), "nic*123"));
        }
        $session->set("GIMS-WEB-SESSION", $gimsWebSession);
        // return new JsonResponse (["status"=>"success"]);
        $gims_web_url  = $request->getSchemeAndHttpHost();
        return $this->redirect($gims_web_url.'/web/index.html');
    }

    /**
     * @Route("/web/{wildcard}", requirements={"wildcard": ".*"}, name="app_gims_web" )
     */
    public function web(Request $request): Response
    {
        $gims_web_url  = $request->getSchemeAndHttpHost();
        return $this->redirect($gims_web_url.'/web/index.html');
    }

    /**
     * @Route("/mobile/android/privacy", name="mobile_android_privacy" )
     */
    public function androidPrivacy(): Response
    {
        return $this->render('/default/privacy/android_privacy.html.twig');
    }
    /**
     * @Route("/mobile/android/support", name="mobile_android_support" )
     */
    public function androidSupport(): Response
    {
        dump('Support');
        die;
    }
    /**
     * @Route("/mobile/android/about", name="mobile_android_about" )
     */
    public function androidAbout(): Response
    {
        dump('Support');
        die;
    }
    
    /**
     * @Route("/mobile/ios/privacy", name="ios_privacy_device" )
     */
    public function iosPrivacyDevice(): Response
    {
        return $this->render('/default/privacy/ios_privacy.html.twig');
    }

    /**
     * @Route("/privacy", name="ios_privacy_web" )
     */
    public function iosPrivacyWeb(): Response
    {
        return $this->render('/default/privacy/ios_privacy.html.twig');
    }

    /**
     * @Route("/mobile/ios/support", name="ios_support_device" )
     */
    public function iosSupportDevice(): Response
    {
        dump('Support');
        die;
    }
    
    /**
     * @Route("/support", name="ios_support_web" )
     */
    public function iosSupportWeb(): Response
    {
        dump('Support');
        die;
    }

    /**
     * @Route("/mobile/ios/about", name="ios_about" )
     */
    public function iosAbout(): Response
    {
        dump('Support');
        die;
    }
    /**
     * @Route("/terms/ios", name="ios_terms" )
     */
    public function iosTerms(): Response
    {
        dump('Terms and Conditions');
        die;
    }
    /**
     * @Route("/terms", name="terms" )
     */
    public function terms(): Response
    {
        dump('terms');
        die;
    }


    /**
     * @Route("/public/devicesettings", name="app_devicesettings")
     */
    public function deviceSettings(Request $request): Response
    {
        $osmakemodeljson = $request->getContent();
        $osmakemodel = \json_decode($osmakemodeljson);
        $deviceSettings = $this->getDoctrine()->getRepository(DeviceSettings::class)->findOneBy(array('deviceOS' => $osmakemodel->os, 'deviceMake' => $osmakemodel->make, 'deviceModel' => $osmakemodel->model));
        if (!$deviceSettings){
            $deviceSettings = $this->getDoctrine()->getRepository(DeviceSettings::class)->findOneBy(array('deviceOS' => 'GENERIC', 'deviceMake' => 'GENERIC', 'deviceModel' => 'GENERIC'));
        }
        return $this->render('/device/phone.html.twig', array('deviceSettings' => $deviceSettings));
    }
    /**
     * @Route("/ping", name="app_ping")
     */
    public function pingAction() {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED') || $this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new Response('ok');
        }
        return new Response('ko');
    }

    /**
     * @Route("/get", name="app_get_package")
     */
    public function getPackageAction(AppServices $app_service) {
        $clientos = $app_service->getOS();
        if ("Android" === $clientos){
            return $this->redirect('https://play.google.com/store/apps/details?id=in.nic.gimkerala');
        } elseif ("Apple" === $clientos){
            return $this->redirect('https://apps.apple.com/in/app/gims-instant-messaging-system/id1517976582');
        } else {
            return $this->redirectToRoute('app_dashboard_dlink');
        }
    }

    /**
     * @Route("/downloads", name="app_downloads")
     */
    public function downloadsAction() {
        return $this->render('/default/downloads.html.twig');
    }
   
    /**
     * @Route("/download", name="app_download")
     */
    public function downloadAction() {
        return $this->render('/default/downloads.html.twig');
    }


    public function getIndexPageData()
    {
        $jsonFilePath = $this->getParameter('kernel.project_dir') . '/data.json';
        print_r($jsonFilePath);die;
        // Check if the file exists
        if (!file_exists($jsonFilePath)) {
            throw $this->createNotFoundException('JSON file not found');
        }
        // Read the contents of the JSON file
        $jsonData = file_get_contents($jsonFilePath);
        // Decode the JSON data
        $data = json_decode($jsonData, true); 
        return new JsonResponse($data);


    }
}
