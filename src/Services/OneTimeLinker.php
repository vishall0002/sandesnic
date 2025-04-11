<?php

/**
 * Description : Common Class for Sending Emailes.
 *
 * @author Vipin
 */

namespace App\Services;

use App\Entity\Portal\OneTimeLink;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\EMailer;
use Twig\Environment as Environment;

class OneTimeLinker {

    private $emr;
    private $emailer;
    private $twig;

    public function __construct(EntityManagerInterface $em, EMailer $emailer, Environment $twig) {
        $this->emr = $em;
        $this->emailer = $emailer;
        $this->twig = $twig;
    }

    public function createOTL($forUser, $otlFor) {
        // We shall be creating OTL only on the following condition
        // 1. The OTL creation time has exceeded 24 hrs.
        // 2. The user has already accessed
//        if ($forUser->getIsEmailVerified()) {
        $dateNow = new \DateTime('now');
        // $otlExists = $this->emr->getRepository('App:Portal\OneTimeLink')->findOneBy(array('forUser' => $forUser), array('id' => 'DESC'));
        // if (!$otlExists or $otlExists->getIsAccessed() or $otlExists->getCreatedAt()->diff($dateNow)->days > 1) {
            $oneTimeLink = new OneTimeLink();
            $oneTimeLink->setIsAccessed(false);
            $oneTimeLink->setIsSent(false);
            $oneTimeLink->setOtlFor($otlFor);
            $oneTimeLink->setForUser($forUser);
            $oneTimeLink->setCreatedAt(new \DateTime('now'));
            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $oneTimeLink->setGuId($uuid->toString());
            $this->emr->persist($oneTimeLink);
            $this->emr->flush();
            return $this->emailOTL($oneTimeLink->getGuId());
        // } else if ($otlExists->getIsSent() === 0) {
        //     return $this->emailOTL($otlExists->getGuId());
        // } else {
        //     return "OTL is already sent and active";
        // }
//        } else {
//            return "E-Mail not verified";
//            return true;
//        }
    }

    public function emailOTL($guId) {
        $qb = $this->emr->createQueryBuilder();
        $otlRecord = $qb->select('o.guId, u.email, o.otlFor')
                ->from("App:Portal\OneTimeLink", 'o')
                ->innerJoin("App:Portal\User", 'u', 'WITH', 'u.id = o.forUser')
                ->where('o.guId = :otlGuId')
//                ->andWhere('u.isEmailVerified = 1')
                ->setParameters(array(':otlGuId' => $guId))
                ->getQuery()
                ->getResult();
        if ($otlRecord) {
            $toAddress = $otlRecord[0]['email'];
            $emailSubject = 'Sandes One Time Link for app download.';
            $em = $this->emr;
            $iosExists = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => 'IOS', 'isCurrent' => true]);
            $emailContent = $this->twig->render('emailer/otl_email_template.html.twig', array('guId' => $guId,'iosExists'=>$iosExists));
            $otlStatus = $this->emailer->sendEmail($toAddress, $emailSubject, $emailContent);
            if (strpos($otlStatus, 'EMail Sent!') !== false) {
                $oneTimeLink = $this->emr->getRepository('App:Portal\OneTimeLink')->findOneByGuId($guId);
                if ($oneTimeLink) {
                    $oneTimeLink->setIsSent(true);
                    $this->emr->persist($oneTimeLink);
                    $this->emr->flush();
                }
                return "Email attempt successful";
            } else {
                return "Email attempt unsuccessful";
            }
        } else {
            return "OTL not found";
        }
    }

}
