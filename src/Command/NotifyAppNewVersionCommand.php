<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Services\EMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Twig\Environment as Environment;

/**
 * This command mainly serve the purpose of notifying the department users regarding
 * a new upload is available mentioning Version Number.
 *
 * @author Vipin Bose
 */
class NotifyAppNewVersionCommand extends Command
{
    private $emailer;
    private $entityManager;
    private $twig;

    public function __construct(EntityManagerInterface $em, EMailer $emailer, Environment $twig)
    {
        parent::__construct();
        $this->emailer = $emailer;
        $this->entityManager = $em;
        $this->twig = $twig;
    }

    protected function configure()
    {
        $this->setName('app:notify-app-download')
                ->setDescription('Notify users about new version release')
                ->setHelp('notify-app-download command : This command mainly serve the purpose of notifying the all registered users about the release of new app');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // $io = new SymfonyStyle($input, $output);
        // $io->note(array(
        //     'Lapse process initialize....',
        //     'Please wait....',
        // ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messageBody = $this->twig->render('/emailer/notify_app_download.html.twig');
        // $this->emailer->sendEmail('syam.krishna@nic.in', 'SANDES new version (0.9.2b) released', $messageBody);
        // $this->emailer->sendEmail('vipin.bose@gov.in', 'SANDES new version released', $messageBody);
        // $this->emailer->sendEmail('manoj.pa@nic.in', 'SANDES new version released', $messageBody);
        // $this->emailer->sendEmail('sunish@nic.in', 'SANDES new version released', $messageBody);
        // $this->emailer->sendEmail('arun.kv@nic.in', 'SANDES new version released', $messageBody);
        // $this->emailer->sendEmail('abby.murali@nic.in', 'SANDES new version released', $messageBody);
        $myCon = $this->entityManager->getConnection();
        // $stmtEmployee = $myCon->prepare("select email from gim.employee where id >= 2949");
        $stmtEmployee = $myCon->prepare("select email from gim.employee");
        // $stmtEmployee = $myCon->prepare("select email from gim.employee where registered = 'Y'");
        $stmtEmployee->execute();
        $resultEmployees = $stmtEmployee->fetchAll();
        $i = 0;
        foreach ($resultEmployees as $employee) {
            echo $i++. ' - '. $employee['email'].PHP_EOL;
            // $this->emailer->sendEmail($employee['email'], 'SANDES new version (0.9.2b) released', $messageBody);
        }
        return 1;
        // return 1;
    }
}
