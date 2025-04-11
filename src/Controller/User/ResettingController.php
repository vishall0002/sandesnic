<?php

namespace App\Controller\User;

use App\Entity\Portal\EmailLinkStatus;
use App\Form\User\OTPType;
use App\Form\User\PasswordRecoveryType;
use App\Form\User\ResetPasswordType;
use App\Form\User\UserForPasswordChangeType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 *
 * @Route("/public")
 * */
class ResettingController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private function createCreateFormForSMS($entity = null)
    {
        $form = $this->createForm(PasswordRecoveryType::class, null, array(
            'action' => $this->generateUrl('recover_pass_method'),
            'method' => 'POST',
        ));
        return $form;
    }

    private function createCreateFormForUserName($entity = null)
    {
        $form = $this->createForm(UserForPasswordChangeType::class, null, array(
            'action' => $this->generateUrl('recover_pass_method'),
            'method' => 'POST',
        ));
        return $form;
    }

    public function createResetPasswordForm($container = null, $guId)
    {
        if ($container) {
            $this->setContainer($container);
        }
        $form = $this->createForm(ResetPasswordType::class, null, array(
            'action' => $this->generateUrl('reset_password', array('guId' => $guId)),
            'method' => 'POST',
        ));

        return $form;
    }

    private function createValidateOTPForm()
    {
        $form = $this->createForm(OTPType::class, null, array(
            'action' => $this->generateUrl('validate_otp_for_password_reset'),
            'method' => 'POST',
        ));
        return $form;
    }

    /**
     * @Route("/vofpr", name="validate_otp_for_password_reset")
     */
    public function validateOTPAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->createValidateOTPForm();
        $form->handleRequest($request);
        $guId = '';
        $otpAttemptCount = 0;
        if ($form->isValid()) {
            $guId = $request->get('guid');
            if ($guId == '') {
                return new JsonResponse(['type' => 'danger', 'msg' => 'OTP verification failed. Please try again after some time']);
            }

            $user = $em->getRepository('App:User')->findOneByGuId($guId);
            $smsStatus = $em->getRepository('App:EmailMobileVerification')->findOneBy(['user' => $user, 'verificationUse' => 2]);
            $otpAttemptCount = $smsStatus->getOtpAttemptCount();
            $otpAttemptCount += 1;
            $smsStatus->setOtpAttemptCount($otpAttemptCount);
            $em->flush();
            if ($otpAttemptCount > 3) {
                return new JsonResponse(['type' => 'danger', 'msg' => 'OTP verification attempt exceeds. Generate OTP once again']);
            }

            if ($form['otp']->getData() == '' || $form['otp']->getData() == null) {
                $error = new \Symfony\Component\Form\FormError('OTP Should not be blank');
                $form->get('otp')->addError($error);

                return new JsonResponse(['type' => 'danger', 'msg' => 'OTP Should not be blank']);
            }

            if ($smsStatus->getOtp() != trim($form['otp']->getData())) {
                return new JsonResponse(['type' => 'danger', 'msg' => 'Mismatch in One Time Password']);
            }
            $smsStatus->setIsMobileVerified(true);
            $smsStatus->setOtpAttemptCount($otpAttemptCount);
            $smsStatus->setUpdateTime(new \DateTime());
            $smsStatus->setUpdateIp($request->getClientIp());
            $em->flush();

            return new JsonResponse(['type' => 'success', 'msg' => 'OTP Verified Successfully', 'redirectPath' => $this->generateUrl('reset_password', array('guId' => $guId))]);
        }
        return new JsonResponse(['type' => 'danger', 'msg' => 'OTP Verification Failed']);
    }

    /**
     * @Route("/recover-pass", name="recover_pass_method")
     */
    public function recoverPasswordPageAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $formForSMS = $this->createCreateFormForSMS(null);
        $formForUserId = $this->createCreateFormForUserName(null);
        $otpSentCount = 0;
        if ('POST' === $request->getMethod()) {
            $formForSMS->handleRequest($request);
            $formForUserId->handleRequest($request);
            if ($formForSMS->isValid() || $formForUserId->isValid()) {
                if ($formForSMS->getData() != null && array_key_exists('userIdForSMS', $formForSMS->getData()) && array_key_exists('mobile', $formForSMS->getData())) {
                    if ($formForSMS['userIdForSMS']->getData() == '' || $formForSMS['mobile']->getData() == '') {
                        $error = new \Symfony\Component\Form\FormError('Username and mobile field cannot be empty');
                        $formForSMS->get('userIdForSMS')->addError($error);
                        $formForSMS->get('mobile')->addError($error);

                        return new JsonResponse(['type' => 'danger', 'msg' => 'Username and mobile field cannot be empty']);
                    }
                    if (filter_var($formForSMS['userIdForSMS']->getData(), FILTER_VALIDATE_EMAIL)) {
                        $user = $em->getRepository('App:User')->findOneBy(['email' => $formForSMS['userIdForSMS']->getData(), 'mobile' => $formForSMS['mobile']->getData()]);
                    } else {
                        $user = $em->getRepository('App:User')->findOneBy(['username' => $formForSMS['userIdForSMS']->getData(), 'mobile' => $formForSMS['mobile']->getData()]);
                    }
                    if (!$user || empty($user)) {
                        return new JsonResponse(['type' => 'danger', 'msg' => 'Please Enter Email/Mobile No. provided at the time of Registration']);
                    }
                    $smsMessage = 'Your password recovery verification code is : ';
                    try {
                        $entity = $em->getRepository("App:EmailMobileVerification")->findOneBy(['user' => $user, 'verificationUse' => 2]);
                        if (!$entity) {
                            $entity = new \App\Entity\Portal\EmailMobileVerification();
                            $entity->setUser($user);
                        }
                        $sms = $this->container->get('portal.sms.send');
                        $mobileNumber = $formForSMS['mobile']->getData();
                        $otp = mt_rand(100000, 1000000);
                        $smsMessage .= $otp;
                        $sms->sendSMS($mobileNumber, $smsMessage, $user->getId());

                        $entity->setIsOTPSent(true);
                        $entity->setOtp($otp);
                        $entity->setOtpSentTime(new \DateTime('now'));
                        $entity->setMessageText($smsMessage);
                        $entity->setOtpAttemptCount(null);
                        $entity->setVerificationUse(2);
                        $em->persist($entity);
                        $em->flush();
                        $otpForm = $this->createValidateOTPForm();
                        $formView = $this->renderView('App:User\RecoverPassword:otpForm.html.twig', array('form' => $otpForm->createView(), 'guId' => $user->getGuId(), 'userId' => $formForSMS['userIdForSMS']->getData(), 'mobile' => $formForSMS['mobile']->getData()));
                        return new JsonResponse(['type' => 'success', 'msg' => 'SMS sent to your registered mobile number', 'form' => $formView]);
                    } catch (\Exception $e) {
                        return new JsonResponse(['type' => 'danger', 'msg' => 'SMS Sending failed.. Please try after some time']);

                        throw new \RuntimeException('SMS Sending failed.. Please try after some time');
                    }
                }

                if ($formForUserId->getData() != null && array_key_exists('userName', $formForUserId->getData())) {
                    if ($formForUserId['userName']->getData() == '') {
                        $error = new \Symfony\Component\Form\FormError('Username field cannot be empty');
                        $formForUserId->get('userName')->addError($error);

                        return new JsonResponse(['type' => 'danger', 'msg' => 'Username field cannot be empty']);
                    }
                    if (filter_var($formForUserId['userName']->getData(), FILTER_VALIDATE_EMAIL)) {
                        $user = $em->getRepository('App:User')->findOneBy(['email' => $formForUserId['userName']->getData()]);
                    } else {
                        $user = $em->getRepository('App:User')->findOneBy(['username' => $formForUserId['userName']->getData()]);
                    }
                    if (!$user || empty($user)) {
                        return new JsonResponse(['type' => 'danger', 'msg' => 'Invalid Email/UserName']);
                    }

                    if (!$user->getIsEmailVerified()) {
                        return new JsonResponse(['type' => 'danger', 'msg' => 'E-Mail address not verified']);
                    }

                    $em->getConnection()->beginTransaction();
                    try {
                        $mailLinkStatus = new EmailLinkStatus();
                        $entity = $em->getRepository("App:EmailMobileVerification")->findOneBy(['user' => $user, 'verificationUse' => 2]);
                        if (!$entity) {
                            $entity = new \App\Entity\Portal\EmailMobileVerification();
                            $entity->setUser($user);
                        }
                        $entity->setIsEmailSent(true);
                        $entity->setEmailSentTime(new \DateTime('now'));
                        $entity->setVerificationUse(2);
                        $mailLinkStatus->setUserId($user);
                        $mailLinkStatus->setLinkCreatedTime(new \DateTime('now'));

                        $em->persist($entity);
                        $em->persist($mailLinkStatus);
                        $em->flush();
                        $email = $this->container->get('portal.email.send');
                        $email->sendEmail($mailLinkStatus->getGuId(), $user->getEmail(), 'recoverPass');
                        $em->getConnection()->commit();

                        return new JsonResponse(['type' => 'success', 'msg' => 'Password Recovery email sent to your registered email']);
                    } catch (\Exception $e) {
                        $em->getConnection()->rollback();
                        throw new \RuntimeException('Email Sending Failed.. Please try after some time');
                    }
                }
            }
        }
        return $this->render('App:User\RecoverPassword:recoverPassword.html.twig', array('form1' => $formForUserId->createView(), 'form2' => $formForSMS->createView()));
    }

    /**
     * @Route("/{guId}/reset-pass", name="reset_password")
     */
    public function resetPasswordAction(Request $request, $guId)
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->createResetPasswordForm(null, $guId);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $form['newPassword']->getData();
            $form['confirmPassword']->getData();
            if ($form['newPassword']->getData() == $form['confirmPassword']->getData()) {
                $userManager = $this->container->get('fos_user.user_manager');
                $user = $em->getRepository('App:User')->findOneByGuId($guId);
                $user->setPassword('hash:' . $form['newPassword']->getData());
                $userManager->updateUser($user);

                return new JsonResponse(['type' => 'success', 'msg' => 'Password updated successfully', 'redirectPath' => $this->generateUrl('login')]);
            }
            return new JsonResponse(['type' => 'danger', 'msg' => 'Password mismatch']);
        }
        return $this->render('App:User\RecoverPassword:resetPassword.html.twig', array('form' => $form->createView()));
    }
}
