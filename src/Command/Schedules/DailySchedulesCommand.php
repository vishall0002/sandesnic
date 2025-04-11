<?php

namespace App\Command\Schedules;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

/**
 * A console command that generates statistics on Chat run by a cron job.
 *
 * To use this command, open a terminal window, enter into your project directory
 * and execute the following:
 *
 *
 */
class DailySchedulesCommand extends Command
{
    // a good practice is to use the 'app:' prefix to group all your custom application commands
    protected static $defaultName = 'app:daily-schedules';
    private $emr;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->emr = $em;
    }
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Statistics Generation on  Chats')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command generates statistics on chat data:

  <info>php %command.full_name%</info>

There are no arguments required, a new record of statistics will be created each time the 
command is run. Idealy this command should be run as a CRON job
HELP
            );
    }

    /**
     * This method is executed after initialize(). It usually contains the logic
     * to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $myCon = $this->emr->getConnection();

        $chatStatistics = $myCon->prepare("select * from report.refresh_registration_activity()");
        $chatStatistics->execute();
        $chatStatisticsResult = $chatStatistics->fetchAll()[0];
     
        // $chatStatistics = $myCon->prepare("select * from report.refresh_message_delivery_stats()");
        // $chatStatistics->execute();
        // $chatStatisticsResult = $chatStatistics->fetchAll()[0];
        
        // $chatStatistics = $myCon->prepare("select * from report.refresh_message_read_stats()");
        // $chatStatistics->execute();
        // $chatStatisticsResult = $chatStatistics->fetchAll()[0];

        return 1;
    }
}
