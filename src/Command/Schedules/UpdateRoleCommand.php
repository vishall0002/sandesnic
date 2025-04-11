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
class UpdateRoleCommand extends Command
{
    // a good practice is to use the 'app:' prefix to group all your custom application commands
    protected static $defaultName = 'app:update-role';
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
            ->setDescription('Update role command')
            ->setHelp(
                <<<'HELP'
Update invalid role to valid role, role created from API seems invalid.
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
        $sql = <<<SQL
            update gim.portal_users set roles = '["ROLE_MEMBER"]' WHERE roles = 'a:1:{i:0;s:11:"ROLE_MEMBER";}'
SQL;
        $chatStatistics = $myCon->prepare($sql);
        $chatStatistics->execute();
        return 1;
    }
}
