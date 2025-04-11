<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Command\Schedules;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Services\EMailer;

/**
 * Description of MailerCommand
 *
 * @author jithu
 */
class DailyScreenMailerCommand extends Command {

    private $entityManager;
    private $mailer;

    public function __construct(EntityManagerInterface $em, EMailer $emailer, MailerInterface $mailer) {
        parent::__construct();
        $this->entityManager = $em;
        $this->mailer = $mailer;
        $this->emailer = $emailer;
    }

    protected function configure() {
        $this->setName('app:daily-screen-mailer')
                ->setDescription('Daily Screen Shot Mailer command')
                ->setHelp('app:daily-screen-mailer: E-Mails daily screenshots Command');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $em = $this->entityManager;
        $myCon = $em->getConnection();
        $organizations = $em->getRepository("App:Portal\Organization")->findAll();
        foreach ($organizations as $o) {
            $attachPath = '/home/portal_puser/screenshots/org-' . $o->getGuId() . '.pdf';
            $emailsSql = $myCon->prepare("select distinct u.email from gim.portal_user_profiles p inner join gim.portal_users u on u.id = p.user_id inner join gim.portal_masters_roles r on r.id = p.role_id where p.is_enabled = 1 and p.organization_id=:org and r.role IN ('ROLE_NODAL_OFFICER','ROLE_O_ADMIN','ROLE_OU_ADMIN')");
            $emailsSql->bindValue(':org', $o->getId());
            $emailsSql->execute();
            $emails = $emailsSql->fetchAll();
            if ($o->getId() <> 12){
                foreach ($emails as $email) {
                    $this->emailer->sendEmailV5($email['email'], 'SANDES - Organization View','Email will be delivered to '. $email['email'].' for the organization '.$o->getOrganizationName(), $attachPath);
                }
            } else {
                foreach ($emails as $email) {
                    $this->emailer->sendEmailV5("vipin.bose@gov.in", 'SANDES - Organization View','Email WILL NOT be delivered to '. $email['email'].' for the organization '.$o->getOrganizationName(), $attachPath);
                }
            }
        }
        
        $attachPath = '/home/portal_puser/screenshots/org-all-for-managers.pdf';
        $this->emailer->sendToManagersV5($attachPath);
        return true;
    }

}
