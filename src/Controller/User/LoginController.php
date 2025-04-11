<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\User;

use App\Entity\Portal\Employee;
use App\Form\User\LoginFormType;
use App\Interfaces\AuditableControllerInterface;
use App\Security\Validator\LoginCaptchaValidator as CaptchaValidator;
use App\Services\LDAPAuthentication;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Psr\Log\LoggerInterface;
use App\Services\EMailer;
use Predis\Client;

class LoginController extends AbstractController implements AuditableControllerInterface
{
    private $captchaValidator;
    private $emailer;

    public function __construct(SessionInterface $userSession, LDAPAuthentication $ldapauthenticator, CaptchaValidator $captchaValidator, LoggerInterface $authenticationLogger, EMailer $emailer, Client $redis)
    {
        $this->userSession = $userSession;
        $this->LDAPAuthentication = $ldapauthenticator;
        $this->captchaValidator = $captchaValidator;
        $this->emailer = $emailer;
        $this->redis = $redis;
    }

    /**
     * @Route("/usr/llogin", name="app_login_ldap")
     */
    public function ldaplogin(Request $request, AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(loginFormType::class);

        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->generateUrl('_llogout'));
        }

        if ($error) {
            $error = $error->getMessage();
        }
        $em = $this->getDoctrine()->getManager();
        $preLoginUser = $em->getRepository('App:Portal\User')->findOneByUsername($lastUsername);
        if ($preLoginUser) {
            $attemptCount = $preLoginUser->getAttempted();
        } else {
            $attemptCount = 0;
        }

        $csrfToken = $this->container->has('security.csrf.token_manager') ? $this->container->get('security.csrf.token_manager')->getToken('authenticate')->getValue() : null;
        $salt = password_hash(uniqid(php_uname('n'), true), PASSWORD_BCRYPT);

        return $this->render('user\login_ldap.html.twig', ['form' => $form->createView(), 'error' => $error, 'csrf_token' => $csrfToken, 'browserSalt' => $salt, 'attemptCount' => $attemptCount]);
    }

    /**
     * @Route("/usr/llogin/check", name="app_login_ldap_check")
     */
    public function ldapCheck(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $attemptCount = 0;
        $error = '';
        $userCaptcha = $request->get('user', null, true);
        $captchInput = $userCaptcha['captcha'];
        $form = $this->createForm(loginFormType::class);
        if (false === $this->captchaValidator->validate($captchInput)) {
            $error = 'Captcha is invalid';

            return $this->render('user\login_ldap.html.twig', ['form' => $form->createView(), 'error' => $error]);
        }

        $received_username = trim($request->request->get('_peru'));

        
        $received_password = $request->request->get('_thakol');
        if (strpos($received_username, '.in')) {
            $passwordValid = $this->LDAPAuthentication->isPasswordValid($received_username, $received_password);
            if ($passwordValid) {
                $user = $em->getRepository('App:Portal\User')->findOneByEmail($received_username);
                if (!$user) {
                    $employee = $em->getRepository(Employee::class)->findOneByEmailAddress($received_username);
                    $user = $employee->getUser();
                }
                if (!$user) {
                    $employee = $em->getRepository(Employee::class)->findOneByAlternateEmailAddress($received_username);
                    $user = $employee->getUser();
                }

                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());

                $this->get('security.token_storage')->setToken($token);
                $this->get('session')->set('_security_main', serialize($token));

                return $this->redirectToRoute('app_dashboard');
            } else {
                $error = 'Authentication Failed';
                $form = $this->createForm(loginFormType::class);

                return $this->render('user\login_ldap.html.twig', ['form' => $form->createView(), 'error' => $error]);
            }
        } else {
            $error = 'This authentication method is applicable for authorised government officials';
            $form = $this->createForm(loginFormType::class);

            return $this->render('user\login_ldap.html.twig', ['form' => $form->createView(), 'error' => $error]);
        }
    }

    /**
     * @Route("/usr/nlogin", name="app_login_native")
     */
    public function nativeLogin(Request $request, AuthenticationUtils $authenticationUtils)
    {
        $session = $request->getSession();
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(loginFormType::class);

        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->generateUrl('_nlogout'));
        }

        if ($error) {
            $error = $error->getMessage();
        }
        $em = $this->getDoctrine()->getManager();
        $preLoginUser = $em->getRepository('App:Portal\User')->findOneByUsername($lastUsername);

        if ($preLoginUser) {
            $attemptCount = $preLoginUser->getAttempted();
        } else {
            $attemptCount = 0;
        }

        $csrfToken = $this->container->has('security.csrf.token_manager') ? $this->container->get('security.csrf.token_manager')->getToken('authenticate')->getValue() : null;
        $salt = password_hash(uniqid(php_uname('n'), true), PASSWORD_BCRYPT);

        return $this->render('user\login_native.html.twig', ['form' => $form->createView(), 'error' => $error, 'csrf_token' => $csrfToken, 'browserSalt' => $salt, 'attemptCount' => $attemptCount]);
    }

    /**
     * @Route("/usr/glogin", name="app_login_gimsotp")
     */
    public function gimsotpLogin(Request $request, AuthenticationUtils $authenticationUtils)
    {
        $session = $request->getSession();
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(loginFormType::class);

        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->generateUrl('_glogout'));
        }

        if ($error) {
            $error = $error->getMessage();
        }
        $em = $this->getDoctrine()->getManager();
        $preLoginUser = $em->getRepository('App:Portal\User')->findOneByUsername($lastUsername);

        if ($preLoginUser) {
            $attemptCount = $preLoginUser->getAttempted();
        } else {
            $attemptCount = 0;
        }

        $csrfToken = $this->container->has('security.csrf.token_manager') ? $this->container->get('security.csrf.token_manager')->getToken('authenticate')->getValue() : null;
        $salt = password_hash(uniqid(php_uname('n'), true), PASSWORD_BCRYPT);

        return $this->render('user\login_gimsotp.html.twig', ['form' => $form->createView(), 'error' => $error, 'csrf_token' => $csrfToken, 'browserSalt' => $salt, 'attemptCount' => $attemptCount]);
    }

    /**
     * @Route("/usr/glogin/submit", name="app_login_gimsotp_submit")
     */


    public function gimsotpSubmit(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $attemptCount = 0;
        $error = '';
        $userCaptcha = $request->get('user', null, true);
        // $captchInput = $userCaptcha['captcha'];
        $form = $this->createForm(loginFormType::class);
        // if (false === $this->captchaValidator->validate($captchInput)) {
        //     $error = 'Captcha is invalid';

        //     return $this->render('user\login_gimsotp.html.twig', ['form' => $form->createView(), 'error' => $error]);
        // }

        $received_username = $request->request->get('_peru');
        $userEmployee = $em->getRepository(Employee::class)->findOneByEmailAddress($received_username);

        



        if (!$userEmployee) {
            $userEmployee = $em->getRepository(Employee::class)->findOneByMobileNumber($received_username);
        }
        $preLoginUser = $userEmployee->getUser();

     


        if ($preLoginUser) {
            $attemptCount = $preLoginUser->getAttempted();
            $error = '';
            $random_otp = rand(100000, 999999);
            // Dummy OTP for testing - Paras
            $random_otp = 123456;
            // $this->emailer->sendEmail($userEmployee->getEmailAddress(), "Sandes Poral - OTP for login", $random_otp.' is your SANDES OTP for current Sandes Portal login');
            // $this->LDAPAuthentication->triggerGIMSOTP($received_username, $random_otp.' is your SANDES OTP for current Sandes Portal login');
            $this->userSession->set('GIMSOTP', $random_otp);
            $this->userSession->set('GIMSOTP-USER', $received_username);

            return $this->render('user\login_gimsotp_submit.html.twig', ['form' => $form->createView(), 'error' => $error]);
        } else {
            $attemptCount = 0;
            $error = 'Sandes Account not available';

            return $this->render('user\login_gimsotp.html.twig', ['form' => $form->createView(), 'error' => $error]);
        }
    }

    /**
     * @Route("/usr/glogin/check", name="app_login_gimsotp_check")
     */
    public function gimsotpCheck(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $received_gimsotp = $request->request->get('_thakol');
        // $userCaptcha = $request->get('user', null, true);
        // $captchInput = $userCaptcha['captcha'];
        $form = $this->createForm(loginFormType::class);
        // if (false === $this->captchaValidator->validate($captchInput)) {
        //     $error = 'Captcha is invalid';

        //     return $this->render('user\login_gimsotp.html.twig', ['form' => $form->createView(), 'error' => $error]);
        // }

        $gimsuser = $this->userSession->get('GIMSOTP-USER');
        $gimsotp = (string) $this->userSession->get('GIMSOTP');
        if ($gimsotp === $received_gimsotp) {
            // Paras - Implemented Redis to track active session to handle concurrent logins
            $userEmployee = $em->getRepository(Employee::class)->findOneByMobileNumber($gimsuser);
            if (!$userEmployee) {
                $userEmployee = $em->getRepository(Employee::class)->findOneByEmailAddress($gimsuser);
            }
            $user = $userEmployee->getUser();

            $sessionKey = 'user:' . $user->getId() . ':session';
            // Check if the user already has an active session in Redis
            if ($this->redis->exists($sessionKey)) {
                $error = 'Already logged in from another device';
                $form = $this->createForm(loginFormType::class);

                return $this->render('user/login_gimsotp_submit.html.twig', [
                    'form' => $form->createView(),
                    'error' => $error,
                ]);
            }

            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());

            $sessionId = $this->get('session')->getId();
            $this->redis->set($sessionKey, $sessionId);
            $this->redis->expire($sessionKey, 3600);
            $this->get('security.token_storage')->setToken($token);
            $this->get('session')->set('_security_main', serialize($token));
            $this->userSession->set('AUTH-TYPE', 'GIMSOTP');

            return $this->redirectToRoute('app_dashboard');
        } else {
            $error = 'Authentication Failed';
            $form = $this->createForm(loginFormType::class);

            return $this->render('user\login_gimsotp_submit.html.twig', ['form' => $form->createView(), 'error' => $error]);
        }
    }

    /**
     * @Route("/usr/prelogin/{username}", name="usr_prelogin")
     */
    public function preLogin($username)
    {
        $em = $this->getDoctrine()->getManager();
        $loweredUserName = strtolower($username);
        if (filter_var($loweredUserName, FILTER_VALIDATE_EMAIL)) {
            $preLoginUser = $em->getRepository('App:Portal\User')->findOneByEmail($loweredUserName);
            if (!$preLoginUser) {
                $whetheremployee = $em->getRepository(Employee::class)->findOneByEmailAddress($loweredUserName);
                $preLoginUser = $whetheremployee->getUser();
            }
            if (!$preLoginUser) {
                $employee = $em->getRepository(Employee::class)->findOneByAlternateEmailAddress($loweredUserName);
                $preLoginUser = $employee->getUser();
            }
        } else {
            $preLoginUser = $em->getRepository('App:Portal\User')->findOneByUsername($loweredUserName);
        }
        if ($preLoginUser) {
            return new JsonResponse(['uppu' => $preLoginUser->getSalt(), 'status' => 'jabajaba']);
        } else {
            return new JsonResponse(['uppu' => password_hash(uniqid(php_uname('n'), true), PASSWORD_BCRYPT), 'status' => 'kshamikku']);
        }
    }

    /**
     * @Route("/usr/login/expired", name="app_login_expired")
     */
    public function expired(Request $request)
    {
        return $this->render('user\loginExpired.html.twig');
    }

    /**
     * @Route("/usr/glogout", name="_glogout")
     */
    public function glogout(Request $request)
    {
        $this->deleteRedisSession();  //Paras

        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();
        }

        return $this->redirectToRoute('app_login_gimsotp');
    }

    /**
     * @Route("/usr/llogout", name="_llogout")
     */
    public function llogout(Request $request)
    {
        $this->deleteRedisSession();  //Paras

        $session = $request->getSession();
        if ('PARICHAY' == $session->get('AUTH-TYPE')) {
            return $this->redirectToRoute('parichay_logout');
        }

        if ('GIMSOTP' == $session->get('AUTH-TYPE')) {
            if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
                $this->get('security.token_storage')->setToken(null);
                $request->getSession()->invalidate();
            }

            return $this->redirectToRoute('app_login_gimsotp');
        }

        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();
        }

        return $this->redirectToRoute('app_login_ldap');
    }

    /**
     * @Route("/usr/nlogout", name="_nlogout")
     */
    public function nlogout(Request $request)
    {
        $this->deleteRedisSession();  //Paras

        $session = $request->getSession();
        if ('PARICHAY' == $session->get('AUTH-TYPE')) {
            return $this->redirectToRoute('parichay_logout');
        }

        if ('GIMSOTP' == $session->get('AUTH-TYPE')) {
            if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
                $this->get('security.token_storage')->setToken(null);
                $request->getSession()->invalidate();
            }

            return $this->redirectToRoute('app_login_gimsotp');
        }

        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->get('security.token_storage')->setToken(null);
            $request->getSession()->invalidate();
        }

        return $this->redirectToRoute('app_login_native');
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function applogout()
    {
        $this->deleteRedisSession();  //Paras
        return $this->redirectToRoute('app_home');
    }

    public function deleteRedisSession()
    {
        // Paras - Destroy redis session
        $user = $this->getUser();
        $sessionKey = 'user:' . $user->getId() . ':session';
        if ($user) {
            $sessionKey = 'user:' . $user->getId() . ':session';
            if ($this->redis->exists($sessionKey)) {
                $this->redis->del($sessionKey);
            }
        }
    }
}
