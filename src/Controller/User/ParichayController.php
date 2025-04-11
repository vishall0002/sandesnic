<?php

namespace App\Controller\User;

use App\Entity\Portal\User;
use App\Interfaces\AuditableControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use App\Security\Firewall\OWASPLoginFormAuthenticator;


class ParichayController extends AbstractController implements AuditableControllerInterface
{
    private $authlogger;
    public function __construct(LoggerInterface $authenticationLogger, SessionInterface $userSession)
    {
        $this->userSession = $userSession;
        $this->authlogger = $authenticationLogger;
    }

    /**
     * @Route("/parichay/login", name="parichay_login")
     */
    public function parichayLogin()
    {
        $this->authlogger->info('PARICHAY-LOGIN-HIT');
        return $this->redirect('https://parichay.nic.in/Accounts/Services?service=Sandes');
        // return $this->redirect('https://parichay.pp.nic.in/Accounts/Services?service=Sandes');
    }

    /**
     * @Route("/parichay/swpd", name="sandes_web_parichay_direct")
     */
    public function sandesWebparichayDirect()
    {
        $this->authlogger->info('Sandes web parichay direct');
        $this->userSession->set('swpd','SANDES_WEB_PARICHAY_DIRECT');
        return $this->redirect('https://parichay.nic.in/Accounts/Services?service=Sandes');
        // return $this->redirect('https://parichay.pp.nic.in/Accounts/Services?service=Sandes');
    }

    /**
     * @Route("/auth", name="parichay_auth", methods={"GET"})
     */
    public function parichayAuth(Request $request,GuardAuthenticatorHandler $guardHandler,OWASPLoginFormAuthenticator $authenticator)
    {
        $em = $this->getDoctrine()->getManager();

        $received_string = $request->query->get('string');
        $received_status = $request->query->get('status');
        $this->authlogger->info('PARICHAY-LOGIN-AUTH-STRING '.$received_string);
        $this->authlogger->info('PARICHAY-LOGIN-AUTH-STATUS '.$received_status);
        /*
        {
            "firstName": "Vipin",
            "lastName": "Bose",
            "fullName": "Vipin Bose",
            "email": "bose.vipin@nic.in",
            "mobileNo": "9562735438",
            "designation": "Scientist-C",
            "employeeCode": "5461",
            "status": "success",
            "user_id": "bose.vipin",
            "zimOtp": "1",
            "state": "",
            "city": "Thiruvananthapuram",
            "location": "Vindhyachal Bhawan",
            "mailAlternateAddress": [],
            "mailEquivalentAddress": ["bose.vipin@mp.nic.in", "vipin.bose@gov.in"],
            "subservice": "",
            "sessionId": "19F11073-D3DE-4CE4-687D-406482424969",
            "localTokenId": "B48046AD73208850AAAE4EB9AA776A7D7212A7705101CD7BB6F3219FB2C46C517BDC4A81E91AAF9C108710A0E6DC2BD7AAEF7CC4BC371DA85B98D2F0DEE693F9",
            "browserId": "2FDF06EE-4E5E-DC7A-0FD5-504EBD423B2E",
            "ip": "10.162.5.16",
            "ua": "Mozilla/5.0 (X11; Linux x86_64; rv:78.0) Gecko/20100101 Firefox/78.0",
            "userName": "bose.vipin@nic.in",
            "expiresAt": "23-06-2020 00:52:20"
        }
        */

        // $parich = <<<JSTR
        // {
        //     "firstName": "Vipin",
        //     "lastName": "Bose",
        //     "fullName": "Vipin Bose",
        //     "email": "bose.vipin@nic.in",
        //     "mobileNo": "9562735438",
        //     "designation": "Scientist-C",
        //     "employeeCode": "5461",
        //     "status": "success",
        //     "user_id": "bose.vipin",
        //     "zimOtp": "1",
        //     "state": "",
        //     "city": "Thiruvananthapuram",
        //     "location": "Vindhyachal Bhawan",
        //     "mailAlternateAddress": [],
        //     "mailEquivalentAddress": ["bose.vipin@mp.nic.in", "vipin.bose@gov.in"],
        //     "subservice": "",
        //     "sessionId": "19F11073-D3DE-4CE4-687D-406482424969",
        //     "localTokenId": "B48046AD73208850AAAE4EB9AA776A7D7212A7705101CD7BB6F3219FB2C46C517BDC4A81E91AAF9C108710A0E6DC2BD7AAEF7CC4BC371DA85B98D2F0DEE693F9",
        //     "browserId": "2FDF06EE-4E5E-DC7A-0FD5-504EBD423B2E",
        //     "ip": "10.162.5.16",
        //     "ua": "Mozilla/5.0 (X11; Linux x86_64; rv:78.0) Gecko/20100101 Firefox/78.0",
        //     "userName": "bose.vipin@nic.in",
        //     "expiresAt": "23-06-2020 00:52:20"
        // }
        // JSTR;

        // $parichay = json_decode($parich);
        // $received_string = "BC789C6CF855FF038C1F79F8EF7C0569C82DEF2F4BD3BE7D7F7A0356D10E45696FFA54E570127F5B0C34086E507EC3F3E75493E6D09923AF8501F1D92C37B17BE8E1110EFDED431F9A10397F525EB1037DE7F93F95906573787C0AB3A79863BE18A64639620EF98EE8DE42F25F46F69160AFA4893881EA075F4709FA7519511DC35BC42B75A60D5BD9270E1B1063F6203D306028432C1C291E3C08C021B14A096601105EB0442B5AF98CE5931561E502AC31506480E3EFC0FDF9D3DBDE299246EA18B39CA121AAED5B0D5E519D026F1B30CAAECF3848A50810AF19422CF9BAF4992BB720DBBB16C44C8E3FCF7718D7DB2011B34165D76E457584234FB2055CC8BF626CAB3E1125E111CE10064231B0B965AB37AE244A437E70F8649F93FAD818B4A3306C1DA1303FAC6A7232AF5C5A2DC87EAC36245E5209E00946E5FBD43A2333CD1D60B8CC5487153437014248DF84E6F967B3F266788C9F01CAFF14B04CF30198FFBC7FF7FA538896ED285F7E1470AAB15342B98C8C0C58264F6B7DD127703DD851B454B70055E9BE6C22A67EF36BBB44E79A52B3B7EA13D382D50148883BA5460404898CBB8B907355A8306079D6F808FD5E86432BB22E72A84AB0E2CBB07E395377444B495DE4828D43A5CEDCB3CA6CB8D8A9D407BFCBE0809452D8984067580C9B2709D2874EE55C63CFF9B1DA8BB36CE030E835898F5DA0D08B156162CB1583F3266A5E1DAE403CA027118A1A90D5B65516D7F70676788AF98177236737AE9840E459DCFF712CC94C16B0C800033CB64702D8B1B5DB5D687B6955C769D692BA7992E1C5212DA9B8E72EAB743F6A8B86ECE364267923CCAE0EBF74EF90F0830E47CB09315513F6D8C39D8F3705B8AA9BA2D1BE7E1AF5FBA66CFDEC939BB2AFA627E0EF6AC68199EFB717EBC150F8D00BACCAFD00674495D9B43138FA2E4B6B9F99FCB2119EDC855BC5168B7448F05314A6016A75DD8E237ADA32B82D4438E4CA2AC1B7CA971D5107D07C9EC9DBFBE7F0F84D6ECAF876F81B6DEEBA01DB0A226AFC6D24066EE2166F911FCFE3526E6E58C784C292529380863BAC26D9D4C885D86EE09246EBF7C580AF623F42B6FD46A66AB6712B4D9D86E2BEA88D64DB2C4C1F1851B40B2288DC73FEB5E1C436";

        $client = HttpClient::create();
        $response = $client->request('GET', 'https://parichay.nic.in/Accounts/openam/login/validateClientToken/'.$received_string.'/Sandes');
        // $response = $client->request('GET', 'https://parichay.pp.nic.in/Accounts/openam/login/validateClientToken/'.$received_string.'/Sandes');
        // $statusCode = $response->getStatusCode();
        // $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->getContent();

        $staging_restApiKey = 'pQ8FxSv1kjEKnwKJ';
        $staging_key = 'w/u2r8HMTxSyL+q61Kvc3e0eCZNvTBCl';
        $restApiKey = 'a344bAxCuU';
        $key = '56771DTwrhzMzOKDCfNk3tuTQfnUVyXx';
        $decrypted_string = openssl_decrypt(hex2bin($received_string), 'AES-256-ECB', $key, OPENSSL_RAW_DATA);
        $parichay = json_decode($decrypted_string);
        $this->authlogger->info('PARICHAY-LOGIN-AUTH-SRVRETURN'.$decrypted_string);
        if ('success' == $parichay->status) {
            $this->authlogger->info('PARICHAY-LOGIN-AUTH-SRVRETURN'.$decrypted_string);
            $this->userSession->set('PARICHAY-SESSION', $decrypted_string);
            $this->userSession->set('AUTH-TYPE', 'PARICHAY');
            $user = $em->getRepository(User::class)->findOneByEmail($parichay->email);
            $selectedProfileWorkspace = $em->getRepository('App:Portal\Profile')->findOneBy(['user' => $user, 'isEnabled' => true, 'isCurrent' => true]);
            if (!$selectedProfileWorkspace) {
                $selectedProfileWorkspace = $em->getRepository('App:Portal\Profile')->findOneBy(['user' => $user, 'isEnabled' => true]);
            }

            $this->authlogger->info('PARICHAY-LOGIN-AUTH-SRVRETURN - Authentication Complete');
            // return $guardHandler->authenticateUserAndHandleSuccess(
            //     $user,
            //     $request,
            //     $authenticator,
            //     'main' // firewall name in security.yaml
            // );
            // $guardHandler->authenticateWithToken(
            //     $token,
            //     $request,
            //     'main' 
            // );
            // return RedirectResponse($this->generateUrl('parichay_success_landing'));
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->get('security.token_storage')->setToken($token);
            $this->get('session')->set('_security_main', serialize($token));
            
            return $this->render('dashboard/parichay_landing_loader.html.twig');
        } else {
            $this->authlogger->info('PARICHAY-LOGIN-AUTH-SRVRETURN - Internal Server Error');
            throw new HttpException(500, 'Internal Server Error');
        }
        $this->authlogger->info('PARICHAY-LOGIN-AUTH-SRVRETURN - Somehow reached end of the action');
        return $this->redirectToRoute('parichay_success_landing');
    }

    /**
     * @Route("/dash/psl", name="parichay_success_landing")
     */
    public function parichaySuccessLanding()
    {
        return $this->render('dashboard/parichay_landing.html.twig');
    }

    /**
     * @Route("/parichay/logout", name="parichay_logout")
     */
    public function parichayLogout()
    {
        $parichay = json_decode($this->userSession->get('PARICHAY-SESSION'));
        $parichay_user = $parichay->userName;
        $parichay_session = $parichay->sessionId;
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->get('security.token_storage')->setToken(null);
            $this->userSession->invalidate();
        }

        return $this->redirect('https://parichay.nic.in/Accounts/openam/login/logoutAll?userName='.$parichay_user.'&service=Sandes&sessionId='.$parichay_session);
        // return $this->redirect('https://parichay.pp.nic.in/Accounts/openam/login/logoutAll?userName='.$parichay_user.'&service=Sandes&sessionId='.$parichay_session);
    }

    /**
     * @Route("/parichay/timeout", name="parichay_timeout")
     */
    public function parichayTimeout()
    {
        return $this->redirect('https://parichay.nic.in/Accounts/ClientManagement?sessionTimeOut=true&service=Sandes');
        // return $this->redirect('https://parichay.pp.nic.in/Accounts/ClientManagement?sessionTimeOut=true&service=Sandes');
    }
}