<?php

namespace App\Security\Firewall;

use App\Services\LDAPAuthentication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use App\Security\Validator\LoginCaptchaValidator as CaptchaValidator;

class OWASPLoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    private $em;
    private $encoderFactory;
    private $browserSalt = 'Test';
    private $userCaptcha = null;
    private $userSession = null;

    private $captchaValidator;
    private $router;
    private $session;
    private $LDAPAuthentication;
    private $LOGIN_INVALID_ATTEMPTS;
    private $LOGIN_AUTO_UNLOCK_TIME;
    private $domainName = 'www.gims.gov.in';

    public function __construct($LOGIN_INVALID_ATTEMPTS, $LOGIN_AUTO_UNLOCK_TIME, EntityManagerInterface $em, EncoderFactoryInterface $encoderFactory, UrlGeneratorInterface $router, SessionInterface $session, LDAPAuthentication $ldapauthenticator, CaptchaValidator $captchaValidator)
    {
        $this->em = $em;
        $this->encoderFactory = $encoderFactory;
        $this->router = $router;
        $this->session = $session;

        $this->captchaValidator =  $captchaValidator;
        $this->LDAPAuthentication = $ldapauthenticator;
        $this->LOGIN_AUTO_UNLOCK_TIME = $LOGIN_AUTO_UNLOCK_TIME;
        $this->LOGIN_INVALID_ATTEMPTS = $LOGIN_INVALID_ATTEMPTS;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return 'app_login' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        $this->browserSalt = $request->get('_uppu');
        $this->userCaptcha = $request->get('user', null, true);
        $this->userSession = $request->getSession();
        $this->domainName = $request->server->get('HTTP_HOST');

        $this->userSession->set(
            Security::LAST_USERNAME,
            $request->request->get('_peru')
        );

        return [
                      'username' => trim($request->request->get('_peru')),
                      'password' => $request->request->get('_thakol'),
                  ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $username = trim($credentials['username']);

        if (null === $username) {
            return;
        }

        try {
            return $userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            throw new CustomUserMessageAuthenticationException('Invalid username or password');
        }
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $captchInput = $this->userCaptcha['captcha'];
        
        // if (false === $this->captchaValidator->validate($captchInput)) {
            //     throw new CustomUserMessageAuthenticationException('Captcha is invalid');
            
            //     return false;
            // }
            
            // if (!$user->isAccountNonLocked()) {
                //     if ($this->isInLockPeriod($user)) {
                    //         $user->setLocked(true);
                    //         $user->setAttempted(0);
                    //         $this->FOSUserManager->updateUser($user);
                    //         throw new CustomUserMessageAuthenticationException('L:Your Account has been temporarily locked. The lock will be released in less than '.$this->LOGIN_AUTO_UNLOCK_TIME.' minutes');
                    //     } else {
                        //         $user->setLocked(false);
                        //         $this->FOSUserManager->updateUser($user);
                        //     }
                        // }
                        // if ($user->isEnabled()) {
            
        if ($user->getIsLDAP()) {
            $passwordValid = $this->encoderFactory->getEncoder($user)->isPasswordValid($user->getPassword(), $credentials['password'], $user->getSalt());
        // $passwordValid = $this->LDAPAuthentication->isPasswordValid(trim($credentials['username']), $credentials['password']);
        } else {
            $passwordValid = $this->encoderFactory->getEncoder($user)->isPasswordValid($user->getPassword(), $credentials['password'], $user->getSalt());
        }
        // Modify the following line appropriately for Audit Purpose
        $domain = explode(':', $this->domainName);
        
        $servername = $_SERVER['SERVER_NAME'];
        $remote_address = $_SERVER['REMOTE_ADDR'];
        $remote_forwarded_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        
        // if (preg_match('/gims-/i', $servername) ){
        // $passwordValid = true;
        // }
        // if (preg_match('/fs.staging2/i', $servername) ){
        // $passwordValid = true;
        // }
        // if (preg_match('/10.162/i', $servername)  ){
        // $passwordValid = true;
        // }
        // if (preg_match('/localhost/i', $servername)){
        // $passwordValid = true;
        // }
        // if (preg_match('/10.1/i', $remote_address)){
        // $passwordValid = true;
        // }
        // if (preg_match('/10.1/i', $remote_forwarded_ip)){
        // $passwordValid = true;
        // }
        // if (preg_match('/10.2/i', $remote_address)){
        // $passwordValid = true;
        // }
        // if (preg_match('/10.2/i', $remote_forwarded_ip)){
        // $passwordValid = true;
        // }

        if ($passwordValid) {
            $user->setAttempted(0);
            $this->em->persist($user);
            $this->em->flush();
            if (preg_match('/gims-/i', $servername) || preg_match('/fs.staging2/i', $servername) || preg_match('/10.162/i', $servername) || preg_match('/localhost/i', $servername) || preg_match('/10.162/i', $servername)) {
                $dummy = true;
            } else {
                $this->LDAPAuthentication->notifyForUnreadStatus(trim(strtolower($credentials['username'])));
            }

            return true;
        } else {
            $attempted = $user->getAttempted() + 1;
            $attempts_remaining = $this->LOGIN_INVALID_ATTEMPTS - $attempted;
            $user->setAttempted($attempted);
            $user->setAttemptedAt(new \DateTime('now'));
            $this->em->persist($user);
            $this->em->flush();
            if ($attempts_remaining > 0) {
                throw new CustomUserMessageAuthenticationException("Invalid username or password.You have $attempts_remaining attempts remaining");
            } else {
                $user->setAttempted(0);
                $user->setIsSuspended(true);
                $this->em->persist($user);
                $this->em->flush();
                throw new CustomUserMessageAuthenticationException('N:Your Account has been temporarily locked. The lock will be released in less than '.$this->LOGIN_AUTO_UNLOCK_TIME.' minutes');
            }
        }

        return false;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        } 
        return new RedirectResponse($this->router->generate('app_dashboard'));
    }

    protected function getLoginUrl()
    {
        return $this->router->generate('app_login');
    }
}
