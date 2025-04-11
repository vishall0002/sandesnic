<?php

namespace App\Command\Schedules;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\FetchMode;

/**
 * This command mainly serve the purpose of notifying the department users regarding
 *
 */
class PreviousDayUsersMessagesTrialCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->entityManager = $em;
    }

    protected function configure()
    {
        $this->setName('app:schedule:dumuser:trialprevious')
                ->setDescription('Scheduler Daily Users and Messages')
                ->setHelp('Scheduler Daily Users and Messages');
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
        // Creds

        // Client Id: 80fbd095-633f-4e12-aa61-017529ea467d
        // Client Secret: c66e45f5303c94abe70cc116a0c3b771
        // HMAC Key: bd22dba50c589acc7e138d373069eb81d43f91839e60e309ec7db462a57b46d3
        $client_id = '80fbd095-633f-4e12-aa61-017529ea467d';
        $client_secret = 'c66e45f5303c94abe70cc116a0c3b771';
        $curdate = date('Y-m-d');
        $date = new \DateTime(); // For today/now, don't pass an arg.
        $date->modify("-1 day");
        $ladate = $date->format("Y-m-d");
        $displaydate = $date->format("d-m-Y");

        $em =  $this->entityManager;
        $myCon = $em->getConnection();
        
        // ------------------------------------------------
        // Issue#33593
        // Onboarded users - From portal dashboard
        // Registered users - From portal dashboard
        // Active users - From existing daily message
        // Concurrent users - Max of online users (from existing daily message)
        // Ministries - From portal dashboard
        // Organisations - From portal dashboard
        // Total Message count - From portal dashboard (formatted in Cr)
        // Daily message count - From existing daily message (Total Messages)
        
        // Refer Email Dated 07/06/2022 and Issue#
        //  ---------------------- There is change in message structure ------------------------

        // Messages through mobile by NIC – 39,537 (+/- yyy)
        // select sum(message_count) as message_count_nic from report.message_activity_org where date_hour::date=current_date -1 and organization_id =1 ;
        // select sum(message_count) as prev_message_count_nic from report.message_activity_org where date_hour::date=current_date -2 and organization_id =1 ;

        // Refer Email Dated 08/06/2022 and Issue#
        //  ---------------------- There is change in message structure ------------------------
        // $string_message = "Sandes Statistics $displaydate<br /><br />";
        // $qrychat = $myCon->prepare("select sum(message_count) as user_message_count from report.message_activity where date_hour::date=current_date -1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total messages (mobile) = " . $the_data . "<br/>";

        // $the_data = 0;
        // $qrychat = $myCon->prepare("select sum(message_count) as app_message_count from report.app_message_activity where  date_hour::date=current_date -1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total messages (applications) = " . $the_data . "<br/>";

        // $the_data = 0;
        // $qrychat = $myCon->prepare("select sum(message_count) from report.message_activity_org where organization_id <>999999 and date_hour::date=current_date -1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total messages (Govt. users) = " . $the_data . " <br/>";
        
        // $the_data = 0;
        // $qrychat = $myCon->prepare("select sum(message_count) from report.message_activity_org where organization_id =999999 and date_hour::date=current_date -1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total messages (Public users) = " . $the_data . " <br/>";
              
        // $the_data = 0;
        // $qrychat = $myCon->prepare("select count(distinct emp_id) as active_user_count from report.message_activity_emp where date_hour::date=current_date -1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total active users = " . $the_data . "<br/>";
        
        // $the_data = 0;
        // $qrychat = $myCon->prepare("select count(distinct emp_id) as active_user_count from report.message_activity_emp where date_hour::date=current_date -1 and organization_id <>999999");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Active users (Govt.) = " . $the_data . "<br/>";
        
        // $the_data = 0;
        // $qrychat = $myCon->prepare("select count(distinct m.emp_id) as active_user_count from report.message_activity_emp m join gim.user_app_device d
        // on m.emp_id =d.emp_id
        // where date_hour::date=current_date -1 and organization_id <>999999 and d.os ='Android'");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Android(Govt.) = " . $the_data . "<br/>";

        // $the_data = 0;
        // $qrychat = $myCon->prepare("select count(distinct m.emp_id) as active_user_count from report.message_activity_emp m join gim.user_app_device d
        // on m.emp_id =d.emp_id
        // where date_hour::date=current_date -1 and organization_id <>999999 and d.os ='iOS'");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "iOS(Govt.) = " . $the_data . "<br/>";
        
        // $the_data = 0;
        // $qrychat = $myCon->prepare("select count(distinct emp_id) as active_user_count from report.message_activity_emp where date_hour::date=current_date -1 and organization_id =999999");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Active users (Public) = " . $the_data . "<br/>";
        
        // $the_data = 0;
        // $qrychat = $myCon->prepare("SELECT MAX(cnt) as maxonline FROM report.active_user_log where log_time::date='$ladate'");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .=  "Online users = " . $the_data . "<br/>"; 

        // $the_data = 0;
        // $qrychat = $myCon->prepare("select count(*) as yesterday_registered_count from gim.employee e where registered='Y' and account_type='U' and registered_date::date=current_date-1 ;");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .=  "New User Registrations = " . $the_data . "<br/>"; 


        // $string_message .= "<br/> <b>MeitY</b><br/>";

        // $the_data = 0;
        // $qrychat = $myCon->prepare("select count(*) as registered_count_meity from gim.employee where registered='Y' and account_status ='V' and account_type='U' and ou_id in (select ou_id from gim.organization_unit where organization_id=3)");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total registered users = " . $the_data . "<br/>";

        // $the_data = 0;
        // $qrychat = $myCon->prepare("select count(distinct emp_id) as active_users_meity from report.message_activity_emp where date_hour::date=current_date -1 and organization_id =3");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Active users = " . $the_data . "<br/>";

        // $the_data = 0;
        // $qrychat = $myCon->prepare("select sum(message_count) as message_count_meity from report.message_activity_org where date_hour::date=current_date -1 and organization_id =3");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Messages (mobile) = " . $the_data . "<br/>";
        // $string_message .= "<br/><b>NIC</b><br/>";
        
        // $the_data = 0;
        // $qrychat = $myCon->prepare("select count(*) as registered_count_nic from gim.employee e join stage.employee_detail ed  on e.mobile_no=ed.mobile_number where registered='Y' and account_status ='V' and account_type='U' and ou_id in (select ou_id from gim.organization_unit where organization_id=1 and ou_id<>45)");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total registered users = " . $the_data . "<br/>";
       
        // $the_data = 0;
        // $qrychat = $myCon->prepare("select count(distinct emp_id) as active_users_nic from report.message_activity_emp where date_hour::date=current_date -1 and organization_id =1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Active users = " . $the_data . "<br/>";
       
        // $the_data = 0;
        // $qrychat = $myCon->prepare("select sum(message_count) as message_count_nic from report.message_activity_org where date_hour::date=current_date -1 and organization_id =1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Messages (mobile) = " . $the_data . "<br/><br/>";

        // $the_data = 0;
        // $qrychat = $myCon->prepare("SELECT COUNT(1) FROM gim.employee as e WHERE e.registered = 'Y'");
        // $qrychat->execute();
        // $the_data =  $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total registered users = " . round((int)$the_data / 100000, 2). " L <br/>";

        // $the_data = 0;
        // $qrychat = $myCon->prepare("SELECT COUNT(1) FROM gim.employee as e WHERE e.registered = 'Y' and e.account_status ='V' and e.account_type='U'");
        // $qrychat->execute();
        // $the_data =  $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total Govt. users = " . round((int)$the_data / 100000, 2). " L <br/>";
               
        // $the_data = 0;
        // $qrychat = $myCon->prepare("SELECT COUNT(1) FROM gim.employee as e WHERE e.registered = 'Y' and e.account_status <>'V' and e.account_type='U'");
        // $qrychat->execute();
        // $the_data =  $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total public users = " . round((int)$the_data / 100000, 2). " L <br/>";
       
        $qrychat = $myCon->prepare("select * from report.get_daily_statisitcs_message()");
        $qrychat->execute();
        $string_message =  $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        
        echo $string_message;
        return 1;
    }
}
