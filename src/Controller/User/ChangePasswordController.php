<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\User\ChangePasswordType;
use App\Entity\Portal\PasswordHistory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ChangePasswordController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    public function indexAction(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createChangePasswordForm($user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Validations on Existing Password Check, New Password and Repeat Password are
            // Handled at Form Level itself
            $em = $this->getDoctrine()->getManager();
            $currentPassword = $form['current_password']->getData();
            $newpassword = $form['new_password']->getData();
            if ($currentPassword === $newpassword) {
                return new JsonResponse(['type' => 'danger', 'msg' => 'Current password and New password should not be the same']);
            }
            $userManager = $this->container->get('fos_user.user_manager');

            $qb = $em->createQueryBuilder();
            $previousPasswords = $qb->select('h.password')
                             ->from('App:PasswordHistory', 'h')
                             ->where('h.user = :currentUserID')
                             ->orderBy('h.id', 'DESC')
                             ->getQuery()
                             ->setParameter(':currentUserID', $user->getId())
                             ->setMaxResults(3)
                             ->getArrayResult();
            if (array_search($newpassword, array_column($previousPasswords, 'password'))) {
                return new JsonResponse(['type' => 'danger', 'msg' => 'This password has been already used before']);
            }
            $passwordHistory = new PasswordHistory();
            $passwordHistory->setUser($user);
            $passwordHistory->setPassword($newpassword);
            $em->persist($passwordHistory);
            $em->flush();

            $user->setPassword('hash:'.$newpassword);
            $user->setCredentialsExpired(false);
            $userManager->updateUser($user);

            return new JsonResponse(['type' => 'success', 'msg' => 'Password Changed Successfully']);
        }

        $formView = $this->renderView('App:User\ChangePassword:index.html.twig', array('form' => $form->createView()));

        return new JsonResponse(['form' => $formView, 'type' => 'danger', 'msg' => $form->getErrors()]);
    }

    private function createChangePasswordForm($user = null)
    {
        $form = $this->createForm(ChangePasswordType::class, null, array(
                'action' => $this->generateUrl('app_self_change_password'),
                'method' => 'POST',
                'currentUserSalt' => $user->getSalt(),
            ));

        return $form;
    }
}
