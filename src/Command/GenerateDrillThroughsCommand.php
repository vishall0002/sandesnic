<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * A console command that generates statistics on Chat run by a cron job.
 *
 * To use this command, open a terminal window, enter into your project directory
 * and execute the following:
 *
 *     $ php bin/console app:generate-drill-throughs
 */
class GenerateDrillThroughsCommand extends Command
{
    // a good practice is to use the 'app:' prefix to group all your custom application commands
    protected static $defaultName = 'app:generate-drill-throughs';
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
            ->setDescription('Drill Through Report data generation')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command generates data for drill through reports

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
        $chatStatistics = $myCon->prepare("call report.refresh_drillthrough()");
        $chatStatistics->execute();
        // $chatStatisticsResult = $chatStatistics->fetchAll()[0];
        return 1;
        // The below code has been commented due to huge increase in load earlier it was run every 30 minutes
        // its now made to run daily once.
        // Arun has refactored it to the procedure
        // Now the procedure will be called
        // 16-12-2021

//         $sqlDT = <<<SQL
//         TRUNCATE TABLE report.drill_throughs;
// SQL;
//         $qryDT = $myCon->prepare($sqlDT);
//         $qryDT->execute();

//         $sqlDT = <<<SQL
//         INSERT INTO report.drill_throughs (ou_id, report_date, onboarded_count, registered_count, group_count, active_users, total_messages, update_time)
//         SELECT  COALESCE(oc.ou_id, rc.ou_id, gc.ou_id,au.ou_id, tm.ou_id) as ou_id, 
//                 COALESCE(oc.report_date::timestamptz,rc.report_date::timestamptz,gc.report_date::timestamptz,au.report_date::timestamptz,tm.report_date::timestamptz) as report_date, 
//                 onboarded_count,
//                 registered_count,
//                 group_count,
//                 active_users,
//                 total_messages,
//                 now()
//             FROM 
//                 (select COALESCE(ou_id, 999999) as ou_id, to_char(m.transaction_date_time::timestamptz,'YYYY-MM-DD') as report_date, count(1) as onboarded_count
//                 from gim.employee as e join gim.portal_metadata as m ON e.insert_metadata_id = m.id
//                 where m.transaction_date_time is not null
//                 group by COALESCE(ou_id, 999999), report_date) as oc
//                 FULL OUTER JOIN 
//                 (select COALESCE(ou_id, 999999) as ou_id, to_char(COALESCE(registered_date::timestamptz,'2018-10-01'),'YYYY-MM-DD') as report_date, count(1) as registered_count
//                 from gim.employee
//                 where registered = 'Y'
//                 group by COALESCE(ou_id, 999999),report_date) as rc ON oc.ou_id = rc.ou_id AND oc.report_date = rc.report_date
//                 FULL OUTER JOIN 
//                 (select COALESCE(parent_ou, 999999) as ou_id, to_char(m.transaction_date_time::timestamptz,'YYYY-MM-DD') as report_date, count(1) as group_count 
//                 from gim.group as g join gim.portal_metadata as m ON g.insert_metadata_id = m.id AND m.transaction_date_time is not null group by COALESCE(parent_ou, 999999), report_date) as gc 
//                 ON COALESCE(oc.ou_id,rc.ou_id) = gc.ou_id AND COALESCE(oc.report_date,rc.report_date) = gc.report_date
//                 FULL OUTER JOIN 
//                 (select COALESCE(ou_id, 999999) as ou_id, to_char(m.date_hour::timestamptz,'YYYY-MM-DD') as report_date, count(distinct emp_id) as active_users
//                 from report.message_activity_emp as m
//                 group by COALESCE(ou_id, 999999), report_date) as au 
//                 ON COALESCE(oc.ou_id, rc.ou_id, gc.ou_id) = au.ou_id AND COALESCE(oc.report_date,rc.report_date,gc.report_date) = au.report_date 
//                 FULL OUTER JOIN 
//                 (select COALESCE(ou_id, 999999) as ou_id, to_char(m.date_hour::timestamptz,'YYYY-MM-DD') as report_date, sum(message_count) as total_messages
//                 from report.message_activity_ou as m
//                 group by COALESCE(ou_id, 999999), report_date) as tm
//                 ON COALESCE(oc.ou_id, rc.ou_id, gc.ou_id,au.ou_id) = tm.ou_id  AND COALESCE(oc.report_date,rc.report_date,gc.report_date,au.report_date) = tm.report_date  
//                  Order by 1,2
// SQL;
//         $qryDT = $myCon->prepare($sqlDT);
//         $qryDT->execute();
    }
}
