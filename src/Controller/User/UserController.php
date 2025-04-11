<?php

namespace App\Controller\User;

use App\Entity\Portal\User;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\Portal\Employee;

/**
 * @Route("/usr")
 * */
class UserController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $noOfInvalidAttempts = 10;

    /**
     * @Route("/uscpf", name="user_show_change_password_form")
     */
    public function showChangePasswordForm()
    {
        $csrfToken = $this->container->has('security.csrf.token_manager') ? $this->container->get('security.csrf.token_manager')->getToken('authenticate')->getValue() : null;

        return $this->container->get('templating')->renderResponse(
            'user\changePassword.html.twig',
            ['csrf_token' => $csrfToken]
        );
    }

    public function changePassword(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $password = $request->request->get('password');
        $newpassword = $request->request->get('newpassword');

        $userexists = $this->getDoctrine()
                ->getRepository('App:User')
                ->findOneBy(['password' => $password]);

        if (!$userexists) {
            $userManager = $this->container->get('fos_user.user_manager');
            $user->setPassword('hashsalted:'.$newpassword);
            $user->setCredentialsExpireAt();
            $user->setCredentialsExpired(false);
            $userManager->updateUser($user);
            $this->addFlash('status', 'Change Password Seems to be successful.');

            return $this->redirect($this->generateUrl('change_user_password'));
        } else {
            $csrfToken = $this->container->has('security.csrf.token_manager') ? $this->container->get('security.csrf.token_manager')->getToken('authenticate')->getValue() : null;

            return $this->container->get('templating')->renderResponse(
                'user\changePassword.html.twig',
                ['csrf_token' => $csrfToken]
            );
        }
    }

    public function credentialsExpired()
    {
        $this->addFlash('status', 'Your login credentials seems to be expired.Please change your username and password .');
        $csrfToken = $this->container->has('security.csrf.token_manager') ? $this->container->get('security.csrf.token_manager')->getToken('authenticate')->getValue() : null;

        return $this->container->get('templating')->renderResponse(
            'user\changePassword.html.twig',
            ['csrf_token' => $csrfToken]
        );
    }

    /**
     * @Route("/afla/{username}", name="add_failed_login_attempts")
     */
    public function addFailedLoginAttempts($username)
    {
        // $userManager = $this->container->get('fos_user.user_manager');
        // if ($this->container->hasParameter('login_invalid_attempts')) {
        //
        //     $this->noOfInvalidAttempts = $this->container->getParameter('login_invalid_attempts');
        // }
        // $user = $this->getDoctrine()
        //         ->getRepository('App:User')
        //         ->findOneBy(array('username' => $username));
        // if ($user) {
        //     if ($user->getAttempted() < $this->noOfInvalidAttempts) {
        //         $user->setAttempted($user->getAttempted() + 1);
        //         $user->setAttemptedAt();
        //         $userManager->updateUser($user);
        //     } else {
        //         $user->setLocked(true);
        //         $userManager->updateUser($user);
        //     }
        // }
        return $this->redirect($this->generateUrl('app_home'));
    }

    public function changeLockStatus($username)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $this->getDoctrine()
                ->getRepository('App:User')
                ->findOneBy(['username' => $username]);
        $user->setLocked(false);
        $user->setAttempted(0);
        $userManager->updateUser($user);

        return $this->redirectToRoute('_index');
    }

    /**
     * @Route("/uebn/{username}", name="user_exists_by_name")
     */
    public function userExistsByName($username)
    {
        $user = $this->getDoctrine()
                ->getRepository('App:Portal\User')
                ->findOneBy(['username' => $username]);
        if ($user) {
            return new Response(1);
        } else {
            return new Response(0);
        }
    }

    /**
     * @Route("/uebe/{email}", name="user_exists_by_email")
     */
    public function userExistsByEmail($email, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        if($objid != '')
        {
            $user = $em->createQueryBuilder('u')
                ->select('u.email')
                ->from('App:Portal\User', 'u')
                ->where('u.email = :email')
                ->setParameter('email', trim($email))
                ->getQuery()
                ->getResult();
            $employeeEmail = $em->createQueryBuilder('e')
                ->select('e.emailAddress')
                ->from('App:Portal\Employee', 'e')
                ->where('e.emailAddress = :email')
                ->setParameter('email', trim($email))
                ->getQuery()
                ->getResult();
            $alternateEmail = $em->createQueryBuilder('e')
                ->select('e.alternateEmailAddress')
                ->from('App:Portal\Employee', 'e')
                ->where('e.alternateEmailAddress = :email')
                ->setParameter('email', $email)
                ->getQuery()
                ->getResult();
            if($alternateEmail)
            {
                if($email === $alternateEmail[0]['alternateEmailAddress'])
                {
                    return new Response(0);
                }
            }
            if($user!=null || $employeeEmail != null)
            {
                $employeeByObjId = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
                if($employeeByObjId)
                {
                    if($email === $employeeByObjId->getEmailAddress() && $email === $employeeByObjId->getUser()->getEmail() )
                    {
                        return new Response(0);
                    }
                }
            }
        }
        else
        {
            $user = $em->createQueryBuilder('u')
                ->select('u.email')
                ->from('App:Portal\User', 'u')
                ->where('u.email = :email')
                ->setParameter('email', trim($email))
                ->getQuery()
                ->getResult();
            $employeeEmail = $this->getDoctrine()->getRepository(Employee::class)->findOneByEmailAddress($email);
            $alternateEmail = $this->getDoctrine()->getRepository(Employee::class)->findOneByAlternateEmailAddress($email);   
        }
        if ($user == null && $employeeEmail == null && $alternateEmail == null) {
            return new Response(0);
        } else {
            return new Response(1);
        }
    }

    /**
     * @Route("/uem", name="update_email_mobile")
     */
    public function updateEmailMobile(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->createFormBuilder()
                ->setAction($this->generateUrl('update_email_mobile'))
                ->add('email', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false])
                ->add('mobileNumber', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false])
                ->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $user = $this->getUser();
            $verificationInstance = $em->getRepository('App:EmailMobileVerification')->findOneBy(['user' => $user, 'verificationUse' => 1]);
            $userManager = $this->get('fos_user.user_manager');
            $email = $form['email']->getData();
            $mobile = $form['mobileNumber']->getData();
            $isExists = [];

            if (!$email && !$mobile) {
                return new JsonResponse(['type' => 'danger', 'msg' => 'Please input atleast one entry before update']);
            }

            if ($email && !$mobile) {
                $isExists = $this->check($email, 'email', $this->container);
                $isExists['Purpose'] = 'E';
            } elseif ($mobile && !$email) {
                $isExists = $this->check($mobile, 'mobile', $this->container);
                $isExists['Purpose'] = 'M';
            } else {
                $isEmailExists = $this->check($email, 'email', $this->container);
                $isMobileExists = $this->check($mobile, 'mobile', $this->container);
                if (false == $isEmailExists['status'] && false == $isMobileExists['status']) {
                    $isExists['status'] = false;
                    $isExists['Purpose'] = 'E&M';
                } else {
                    $isExists['status'] = true;
                }
            }

            if (false == $isExists['status']) {
                $em->getConnection()->beginTransaction();
                try {
                    if ('E' == $isExists['Purpose']) {
                        $user->setEmail($email);
                        $user->setIsEmailVerified(false);
                        $userManager->updateUser($user);
                        if (!empty($jobSeeker)) {
                            foreach ($jobSeeker as $js) {
                                $js->setEmailAddress($email);
                            }
                            $em->flush();
                        }
                        if ($verificationInstance) {
                            $verificationInstance->setIsEmailVerified(false);
                            $verificationInstance->setIsEmailSent(false);
                            $em->flush();
                        }
                        $em->flush();
                        $em->getConnection()->commit();

                        return new JsonResponse(['type' => 'success', 'msg' => 'E-Mail Updated Successfully']);
                    } elseif ('M' == $isExists['Purpose']) {
                        $user->setMobile($mobile);
                        $user->setIsMobileVerified(false);
                        $userManager->updateUser($user);
                        if (!empty($jobSeeker)) {
                            foreach ($jobSeeker as $js) {
                                $js->setMobileNumber($mobile);
                            }
                            $em->flush();
                        }
                        if ($verificationInstance) {
                            $verificationInstance->setIsMobileVerified(false);
                            $verificationInstance->setIsOTPSent(false);
                            $em->flush();
                        }
                        $em->flush();
                        $em->getConnection()->commit();

                        return new JsonResponse(['type' => 'success', 'msg' => 'Mobile Number Updated Successfully']);
                    } else {
                        $user->setEmail($email);
                        $user->setIsEmailVerified(false);
                        $user->setMobile($mobile);
                        $user->setIsMobileVerified(false);
                        $userManager->updateUser($user);
                        if (!empty($jobSeeker)) {
                            foreach ($jobSeeker as $js) {
                                $js->setEmailAddress($email);
                                $js->setMobileNumber($mobile);
                            }
                            $em->flush();
                        }
                        if ($verificationInstance) {
                            $verificationInstance->setIsEmailVerified(false);
                            $verificationInstance->setIsEmailSent(false);
                            $verificationInstance->setIsMobileVerified(false);
                            $verificationInstance->setIsOTPSent(false);
                            $em->flush();
                        }
                        $em->flush();
                        $em->getConnection()->commit();

                        return new JsonResponse(['type' => 'success', 'msg' => 'Email and Mobile number updated Successfully']);
                    }
                } catch (Exception $e) {
                    $em->getConnection()->rollback();

                    return new JsonResponse(['type' => 'danger', 'msg' => 'Cannot update email now. Please try after some time']);
                }
            } else {
                return new JsonResponse(['type' => 'danger', 'msg' => 'E-Mail/Mobile already exists']);
            }
        }

        return $this->render('user\updateEmailMobile.html.twig', [
                    'form' => $form->createView(),
        ]);
    }

    public function check($check, $type, $container = null)
    {
        if ($container) {
            $this->container = $container;
        }
        $em = $this->getDoctrine()->getManager();
        $user = new User();
        if ('email' == $type) {
            $user = $em->getRepository('App:User')->findOneByEmail($check);
        } elseif ('mobile' == $type) {
            $user = $em->getRepository('App:User')->findOneBy(['mobile' => $check]);
        }
        if ('mobile' != $type) {
            if (null === $user) {
                $status = ['status' => false];
            } else {
                $status = ['status' => true];
            }
        } else {
            if (count($user) > 3) {
                $status = ['status' => true];
            } else {
                $status = ['status' => false];
            }
        }

        return $status;
    }

    /**
     * @Route("/uac/type}/{check}", name="user_availability_check")
     */
    public function availabilityCheck(Request $request, $check, $type)
    {
        $status = $this->check($check, $type);

        return new JsonResponse($status);
    }

    public function verifyEmailMobile($container = null)
    {
        if ($container) {
            $this->container = $container;
        }
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_home');
        }
        $request = $this->container->get('request');
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $guId = $user ? $user->getGuId() : '';
        $emailSentStatus = false;
        $smsSentStatus = false;
        $smsContollerInstance = new \App\Controller\Ads\SMSController();
        $form = $smsContollerInstance->createOTPForm($this->container);
        if ($user) {
            $sentStatus = $em->getRepository('App:EmailMobileVerification')->findOneBy(['user' => $user, 'verificationUse' => 1]);
            $emailSentStatus = $sentStatus ? $sentStatus->getIsEmailSent() : false;
            $smsSentStatus = $sentStatus ? $sentStatus->getIsOTPSent() : false;
        }
        $email = $user ? $user->getEmail() : '';
        $mobile = $user ? $user->getMobile() : '';
        $hiddenEmail = $email ? $this->hideMail($email) : '';
        $hiddenMobile = $mobile ? $this->hidePhone($mobile) : '';

        return $this->render('user\emailMobileNotVerified.html.twig', [
                        'email' => $hiddenEmail,
                        'mobile' => $hiddenMobile,
                        'user' => $user,
                        'emailSentStatus' => $emailSentStatus,
                        'smsSentStatus' => $smsSentStatus,
                        'form' => $form->createView(),
                        'guId' => $guId,
            ]);
    }

    private function hideMail($email)
    {
        $mail_segments = explode('@', $email);
        $mail_segments[0] = '****'.substr($mail_segments[0], -3);

        return implode('@', $mail_segments);
    }

    private function hidePhone($phone)
    {
        return '*******'.substr($phone, -3);
    }

    public function verifyLater(Request $request)
    {
        $session = $this->get('session');
        $session->set('verification', ['skipped' => true]);

        return $this->redirectToRoute('app_dashboard');
    }

    /**
     * @Route("/uebm/{mobile}", name="user_exists_by_mobile")
     */
    public function userExistsByMobile($mobile, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        if($objid)
        {
            $employee = $em->getRepository('App:Portal\Employee')
                ->findOneByGuId($objid);
            $user = $em->createQueryBuilder('u')
                ->select('u.mobileNumber')
                ->from('App:Portal\User', 'u')
                ->where('u.id = :id')
                ->setParameter('id', $employee->getUser())
                ->getQuery()
                ->getResult();
            if($mobile === $user[0]['mobileNumber'])
            {
                return new Response(0);
            }
            else
            {
                $user = $em->createQueryBuilder('u')
                    ->select('u.mobileNumber')
                    ->from('App:Portal\User', 'u')
                    ->where('u.mobileNumber = :m')
                    ->setParameter('m', trim($mobile))
                    ->getQuery()
                    ->getResult();
            }
        }
        else
        {
            $user = $em->createQueryBuilder('u')
                ->select('u.mobileNumber')
                ->from('App:Portal\User', 'u')
                ->where('u.mobileNumber = :m')
                ->setParameter('m', trim($mobile))
                ->getQuery()
                ->getResult();
        }
        if ($user) {
            return new Response(1);
        } else {
            return new Response(0);
        }
    }
}
