<?php

namespace App\Controller;

use App\Entity\Portal\Employee;
use App\Services\DefaultValue;
use App\Services\ImageProcess;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DownloadController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $defaultValue;
    private $imageProcess;
    

    public function __construct(DefaultValue $defVal, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, ImageProcess $imageProcess)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->defaultValue = $defVal;
        $this->imageProcess = $imageProcess;
        
    }

    /**
     * @Route("/dash/dlink", name="app_dashboard_dlink")
     */
    public function downloadLink(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $userOU = 0;
        if ($loggedUser) {
            $logged_employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['user' => $loggedUser->getId()]);
            if ($logged_employee){
                $userOU = $logged_employee->getOrganizationUnit();
            }
        }

        $nodalOfficers = $this->getDoctrine()->getRepository(Employee::class)->findBy(['isNodalOfficer' => true, 'organizationUnit' => $userOU]);
        $ou_admins = $this->getDoctrine()->getRepository(Employee::class)->findBy(['isOUAdmin' => true, 'organizationUnit' => $userOU]);
        $iosCurrent = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'isCurrent' => true]);
        $iosBeta = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'isBeta' => true]);
        $androidCurrent = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'ANDROID', 'isCurrent' => true]);
        $androidBeta = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'ANDROID', 'isBeta' => true]);

        return $this->render('dashboard/dashboard_download_app.html.twig', ['ouAdmins' => $ou_admins, 'nodalOfficers' => $nodalOfficers, 'iosCurrent' => $iosCurrent, 'iosBeta' => $iosBeta, 'androidCurrent' => $androidCurrent, 'androidBeta' => $androidBeta, 'logged_member' => $logged_employee]);
    }

    

    /**
     * @Route("/download/android", name="app_dashboard_download_android")
     */
    public function downloadAndroidAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $upload = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'ANDROID', 'isCurrent' => true]);
        if (!$upload) {
            return new Response(json_encode(['status' => 'error', 'message' => 'No current upload found.']), 404);
        }
        $filename = $upload->getAppFileName();
        $uploaddir = $this->getParameter('kernel.project_dir') . '/public/uploads/';
        $fullPath = $uploaddir . $filename;
        // Paras
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return new Response(json_encode(['status' => 'error', 'message' => 'File not found.']), 404);
        }
        $content = file_get_contents($fullPath);
        $response = new Response();
        // Set headers
        $response->headers->set('Content-Type', 'application/vnd.android.package-archive');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '"');
        $response->headers->set('Content-Length', filesize($fullPath));
        $response->setContent($content);
    
        return $response;
    }

    /**
     * @Route("/download/androidbeta", name="app_dashboard_download_android_beta")
     */
    public function downloadAndroidBetaAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $upload = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'ANDROID', 'isBeta' => true]);
        if (!$upload) {
            return new Response(json_encode(['status' => 'error', 'message' => 'No beta upload found.']), 404);
        }
        $filename = $upload->getAppFileName();
        $uploaddir = $this->getParameter('kernel.project_dir') . '/public/uploads/';
        $fullPath = $uploaddir . $filename;
        // Paras
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return new Response(json_encode(['status' => 'error', 'message' => 'File not found.']), 404);
        }
        $content = file_get_contents($fullPath);
        $response = new Response();
        // Set headers
        $response->headers->set('Content-Type', 'application/vnd.android.package-archive');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '"');
        $response->headers->set('Content-Length', filesize($fullPath));
        $response->setContent($content);
    
        return $response;
    }


    /**
     * @Route("/download/ios", name="app_dashboard_download_ios")
     */
    public function downloadIosAction(Request $request)
{
    $em = $this->getDoctrine()->getManager();
    $upload = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'isCurrent' => true]);
    if (!$upload) {
        return new Response(json_encode(['status' => 'error', 'message' => 'No current upload found.']), 404);
    }

    // $basePathUrl = $request->getSchemeAndHttpHost();
    $basePathUrl = "https://www.sandes.gov.in";
    $ipaPath = $basePathUrl . $this->generateUrl('app_dashboard_download_ios_ipa');
    $str = '<?xml version="1.0" encoding="UTF-8"?>
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
					<string>'.$ipaPath.'</string>
				</dict>
				<dict>
					<key>kind</key>
					<string>display-image</string>
					<key>url</key>
					<string>'.$basePathUrl.'/img/logo_app.png</string>
				</dict>
				<dict>
					<key>kind</key>
					<string>full-size-image</string>
					<key>url</key>
					<string>'.$basePathUrl.'/img/logo_app.png</string>
				</dict>
			</array>
			<key>metadata</key>
			<dict>
				<key>bundle-identifier</key>
				<string>gims.gov.in</string>
				<key>bundle-version</key>
				<string>'.$upload->getAppVersion().'</string>
				<key>kind</key>
                <string>software</string>
                <key>platform-identifier</key>
				<string>com.apple.platform.iphoneos</string>
				<key>title</key>
				<string>sandes</string>
			</dict>
		</dict>
	</array>
</dict>
</plist>';
    $xml = new \SimpleXMLElement($str);
    $content = $xml->asXML();
    $response = new Response();
    // Set headers
    $response->headers->set('Content-Type', 'text/xml');
    // Paras
    $filename = 'manifest.plist';
    $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '"');
    $response->setContent($content);

    return $response;
}

    /**
     * @Route("/download/ios/ipa", name="app_dashboard_download_ios_ipa")
     */
    public function downloadIosIpaAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $upload = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'isCurrent' => true]);
    
        if (!$upload) {
            return new Response(json_encode(['status' => 'error', 'message' => 'No current upload found.']), 404);
        }
    
        $filename = $upload->getAppFileName();
        $uploaddir = $this->getParameter('kernel.project_dir') . '/public/uploads/';
        $fullPath = $uploaddir . $filename;
    
        // Paras
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return new Response(json_encode(['status' => 'error', 'message' => 'File not found.']), 404);
        }
    
        $content = file_get_contents($fullPath);
        $response = new Response();
    
        // Set headers
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '"');
        $response->headers->set('Content-Length', filesize($fullPath));
        $response->setContent($content);
    
        return $response;
    }
    

    /**
     * @Route("/download/ios/beta", name="app_dashboard_download_ios_beta")
     */
    public function downloadIosBetaAction(Request $request)
{
    $em = $this->getDoctrine()->getManager();
    $upload = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'isCurrent' => true]);
    if (!$upload) {
        return new Response(json_encode(['status' => 'error', 'message' => 'No current upload found.']), 404);
    }
    // $basePathUrl = $request->getSchemeAndHttpHost();
    $basePathUrl = "https://www.sandes.gov.in";
    $ipaPath = $basePathUrl . $this->generateUrl('app_dashboard_download_ios_ipa_beta');
    $str = '<?xml version="1.0" encoding="UTF-8"?>
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
					<string>'.$ipaPath.'</string>
				</dict>
				<dict>
					<key>kind</key>
					<string>display-image</string>
					<key>url</key>
					<string>'.$basePathUrl.'/img/logo_app.png</string>
				</dict>
				<dict>
					<key>kind</key>
					<string>full-size-image</string>
					<key>url</key>
					<string>'.$basePathUrl.'/img/logo_app.png</string>
				</dict>
			</array>
			<key>metadata</key>
			<dict>
				<key>bundle-identifier</key>
				<string>gims.gov.in</string>
				<key>bundle-version</key>
                <string>'.$upload->getAppVersion().'</string>
                <key>kind</key>
				<string>software</string>
				<key>platform-identifier</key>
				<string>com.apple.platform.iphoneos</string>
				<key>title</key>
				<string>sandes</string>
			</dict>
		</dict>
	</array>
</dict>
</plist>';
    $xml = new \SimpleXMLElement($str);
    $content = $xml->asXML();
    $response = new Response();
    // Set headers
    $response->headers->set('Content-Type', 'text/xml');
    // Paras
    $filename = 'manifest.plist';
    $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '"');
    $response->setContent($content);

    return $response;
}
    /**
     * @Route("/download/ios/ipabeta", name="app_dashboard_download_ios_ipa_beta")
     */
    public function downloadIosIpaBetaAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $upload = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'isBeta' => true]);
    
        if (!$upload) {
            return new Response(json_encode(['status' => 'error', 'message' => 'No beta upload found.']), 404);
        }
    
        $filename = $upload->getAppFileName();
        $uploaddir = $this->getParameter('kernel.project_dir') . '/public/uploads/';
    
        // Paras
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
            return new Response(json_encode(['status' => 'error', 'message' => 'Invalid filename.']), 400);
        }
    
        $fullPath = $uploaddir . $filename;
    
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return new Response(json_encode(['status' => 'error', 'message' => 'File not found.']), 404);
        }
    
        $content = file_get_contents($fullPath);
        $response = new Response();
    
        // Set headers
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '"');
        $response->headers->set('Content-Length', filesize($fullPath));
        $response->setContent($content);
    
        return $response;
    }

    /**
     * @Route("/dash/clink/{objid}", name="app_dashboard_clink")
     */
    public function downloadCLink(Request $request, $objid): Response
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $userOU = 0;
        if ($loggedUser) {
            $userOU = $this->profileWorkspace->getOu()->getId();
        }

        $nodalOfficers = $this->getDoctrine()->getRepository(Employee::class)->findBy(['isNodalOfficer' => true, 'organizationUnit' => $userOU]);
        $ou_admins = $this->getDoctrine()->getRepository(Employee::class)->findBy(['isOUAdmin' => true, 'organizationUnit' => $userOU]);
        $iosCurrent = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'isCurrent' => true]);
        $iosBeta = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'guId' => $objid]);
        $androidCurrent = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'ANDROID', 'isCurrent' => true]);
        $androidBeta = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'ANDROID', 'isBeta' => true]);

        return $this->render('dashboard/dashboard_download_app_custom.html.twig', ['ouAdmins' => $ou_admins, 'nodalOfficers' => $nodalOfficers, 'iosCurrent' => $iosCurrent, 'iosBeta' => $iosBeta, 'androidCurrent' => $androidCurrent, 'androidBeta' => $androidBeta, 'objid' => $objid]);
    }

    /**
     * @Route("/download/ios/betacustom/{objid}", name="app_dashboard_download_ios_beta_custom")
     */
    public function downloadIosBetaCustomAction(Request $request, $objid)
{
    $em = $this->getDoctrine()->getManager();
    $upload = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'guId' => $objid]);

    if (!$upload) {
        return new Response(json_encode(['status' => 'error', 'message' => 'No upload found for the specified ID.']), 404);
    }

    $basePathUrl = $request->getSchemeAndHttpHost();
    $ipaPath = $basePathUrl . $this->generateUrl('app_dashboard_download_ios_ipa_beta_custom', ['objid' => $objid]);
    $str = '<?xml version="1.0" encoding="UTF-8"?>
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
					<string>'.$ipaPath.'</string>
				</dict>
				<dict>
					<key>kind</key>
					<string>display-image</string>
					<key>url</key>
					<string>'.$basePathUrl.'/img/logo_app.png</string>
				</dict>
				<dict>
					<key>kind</key>
					<string>full-size-image</string>
					<key>url</key>
					<string>'.$basePathUrl.'/img/logo_app.png</string>
				</dict>
			</array>
			<key>metadata</key>
			<dict>
				<key>bundle-identifier</key>
				<string>gims.gov.in</string>
				<key>bundle-version</key>
                <string>'.$upload->getAppVersion().'</string>
                <key>kind</key>
				<string>software</string>
				<key>platform-identifier</key>
				<string>com.apple.platform.iphoneos</string>
				<key>title</key>
				<string>sandes</string>
			</dict>
		</dict>
	</array>
</dict>
</plist>';
    $xml = new \SimpleXMLElement($str);
    $content = $xml->asXML();
    $response = new Response();
    // Set headers
    $response->headers->set('Content-Type', 'text/xml');
    // Paras
    $filename = 'manifest.plist';
    $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '"');
    $response->setContent($content);

    return $response;
}



    /**
     * @Route("/download/ios/ipabetacustom/{objid}", name="app_dashboard_download_ios_ipa_beta_custom")
     */
    public function downloadIosIpaBetaCustomAction(Request $request, $objid)
    {
        $em = $this->getDoctrine()->getManager();
        $upload = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'guId' => $objid]);
    
        if (!$upload) {
            return new Response(json_encode(['status' => 'error', 'message' => 'No upload found for the specified ID.']), 404);
        }
    
        $filename = $upload->getAppFileName();
        $uploaddir = $this->getParameter('kernel.project_dir') . '/public/uploads/';
    
        // Paras
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
            return new Response(json_encode(['status' => 'error', 'message' => 'Invalid filename.']), 400);
        }
    
        $fullPath = $uploaddir . $filename;
    
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return new Response(json_encode(['status' => 'error', 'message' => 'File not found.']), 404);
        }
    
        $content = file_get_contents($fullPath);
        $response = new Response();
    
        // Set headers
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '"');
        $response->headers->set('Content-Length', filesize($fullPath));
        $response->setContent($content);
    
        return $response;
    }

    /**
     * @Route("/android/faq", name="app_android_faq")
     */
    public function androidFAQAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $filename = $this->getParameter('kernel.project_dir').'/public/resources/um/faq.pdf';
        $content = file_get_contents($filename);
        $response = new Response();
        //set headers
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline;filename=faq.pdf');
        $response->headers->set('Content-Length', filesize($filename));
        $response->setContent($content);

        return $response;
    }

    /**
     * @Route("/android/qrg", name="app_android_qrg")
     */
    public function androidQRGAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $filename = $this->getParameter('kernel.project_dir').'/public/resources/um/qrg.pdf';
        $content = file_get_contents($filename);
        $response = new Response();
        //set headers
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline;filename=qrg.pdf');
        $response->headers->set('Content-Length', filesize($filename));
        $response->setContent($content);

        return $response;
    }

    /**
     * @Route("/ios/faq", name="app_ios_faq")
     */
    public function iosFAQAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $filename = $this->getParameter('kernel.project_dir').'/public/resources/um/faq.pdf';

        $content = file_get_contents($filename);
        $response = new Response();
        //set headers
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline;filename=faq.pdf');
        $response->headers->set('Content-Length', filesize($filename));
        $response->setContent($content);

        return $response;
    }

    /**
     * @Route("/ios/qrg", name="app_ios_qrg")
     */
    public function iosQRGAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $filename = $this->getParameter('kernel.project_dir').'/public/resources/um/qrg-ios.pdf';
        $content = file_get_contents($filename);
        $response = new Response();
        //set headers
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline;filename=qrg.pdf');
        $response->headers->set('Content-Length', filesize($filename));
        $response->setContent($content);

        return $response;
    }
    
    /**
     * @Route("/onboard", name="app_onboard_info")
     */
    public function onboardInfoAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $filename = $this->getParameter('kernel.project_dir').'/public/resources/um/onboard.pdf';
        $content = file_get_contents($filename);
        $response = new Response();
        //set headers
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline;filename=sandes_onboarding_details.pdf');
        $response->headers->set('Content-Length', filesize($filename));
        $response->setContent($content);

        return $response;
    }
}
