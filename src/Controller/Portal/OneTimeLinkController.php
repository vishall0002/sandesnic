<?php

namespace App\Controller\Portal;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Interfaces\AuditableControllerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\JsonResponse;


class OneTimeLinkController extends AbstractController implements AuditableControllerInterface {

    /**
     * @Route("/public/otl/appdwnld/{guId}/{type}", name="portal_otl_app_dwnld")
     */
    public function appDwnldAction(Request $request, $guId, $type = null) {
        $em = $this->getDoctrine()->getManager();
        $oneTimeLink = $em->getRepository('App:Portal\OneTimeLink')->findOneByGuId($guId);
        if ($oneTimeLink) {
            $dateNow = new \DateTime('now');
            $linkCreationDate = $oneTimeLink->getCreatedAt();
            if ($linkCreationDate->diff($dateNow)->days > 1) {
                $oneTimeLink->setAccessedAt(new \DateTime('now'));
                $oneTimeLink->setIsAccessed(true);
                $oneTimeLink->setAccessedIP($request->getClientIp());
                $em->persist($oneTimeLink);
                $em->flush();
                return $this->redirect($this->generateUrl('portal_otl_expired'));
            }
            if ($oneTimeLink->getIsAccessed()) {
                return $this->redirect($this->generateUrl('app_home'));
            } else {
                $oneTimeLink->setAccessedAt(new \DateTime('now'));
                $oneTimeLink->setIsAccessed(true);
                $oneTimeLink->setAccessedIP($request->getClientIp());
                $em->persist($oneTimeLink);
                $em->flush();
                if ($type == 'ios') {
                    return $this->redirect($this->generateUrl('app_dashboard_download_ios'));
                } else {
                    return $this->redirect($this->generateUrl('app_dashboard_download_android'));
                }
            }
        }

        return $this->redirect($this->generateUrl('app_dashboard'));
    }

    /**
     * @Route("/test", name="portal_otl_test")
     */
    public function testAction() {
        $em = $this->getDoctrine()->getManager();
        $objid = '15e4afaf-8059-4be0-9cf3-0e150eff42c4';
        $employee = $em->getRepository(Employee::class)->findOneByGuId($objid);
        $status = $this->oneTimeLinker->createOTL($employee->getUser(), $employee->getEmailAddress());
        return new JsonResponse($status);
    }

    /**
     * @Route("/testMailView", name="portal_otl_test_mail_view")
     */
    public function testMailViewAction() {
        $em = $this->getDoctrine()->getManager();
        $iosExists = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'isCurrent' => true]);
        return $this->render("emailer/otl_email_template.html.twig", array('guId' => 'f5c2b4ed-b0fe-475c-98dc-3490798b11f9', 'iosExists' => $iosExists));
    }

    /**
     * @Route("/test/email", name="portal_test_email")
     */
    public function emailTesterAction() {

        $message = \Swift_Message::newInstance()
                ->setSubject('Gims received for your review')
                ->setFrom(array('nickerala.pms@gov.in' => 'Gims'))
                ->setTo(array('vipin.bose@gov.in'))
                ->setBody(
                '<h2>Hello</h2>', 'text/html'
        );

        $this->get('mailer')->send($message);
        return new JsonResponse("NO Error so far");
    }

    /**
     * @Route("/expired", name="portal_otl_expired")
     */
    public function otlExpiredAction() {
        return $this->render("exceptions/otlExpired.html.twig");
    }

}
