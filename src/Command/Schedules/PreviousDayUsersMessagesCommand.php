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
class PreviousDayUsersMessagesCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->entityManager = $em;
    }

    protected function configure()
    {
        $this->setName('app:schedule:dumuser:previous')
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

        // Refer Email Dated 08/06/2022 and Issue#
        //  ---------------------- There is change in message structure ------------------------
        // $string_message = "Sandes Statistics $displaydate<br /><br />";
        // $qrychat = $myCon->prepare("select sum(message_count) as user_message_count from report.message_activity where date_hour::date=current_date -1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total messages (mobile) = " . $the_data . "<br/>";

        // $qrychat = $myCon->prepare("select sum(message_count) from report.message_activity_emp_kind maek where kind ='D' and date_hour::date=current_date-1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total messages delivered (mobile) = " . $the_data . "<br/>";

        // $the_data = 0;
        // $qrychat = $myCon->prepare("select sum(message_count) as app_message_count from report.app_message_activity where  date_hour::date=current_date -1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total messages (applications) = " . $the_data . "<br/>";
        
        // $the_data = 0;
        // $qrychat = $myCon->prepare("select sum(delivered_count) from gim.app_message_log where delivered_last_updated_on::date=current_date-1");
        // $qrychat->execute();
        // $the_data = $qrychat->fetchAll(FetchMode::NUMERIC)[0][0];
        // $string_message .= "Total messages delivered (applications) = " . $the_data . "<br/>";

       
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
        
        $hmac = '';
        
        try {
            $client = new Client(['verify' => false]);
            $url = 'http://dwar1.gims.gov.in/v1/api/message/multicast';
            // $encoded_params = '{"message":"'.$string_message.'","type":"chat","title":"GIMSIMTest","category":"info","created_on":1549953711,"expire_on":1550126511,"receivers":["bose.vipin@nic.in","arun.kv@nic.in"]}';
            $encoded_params = '{"message":"' . $string_message . '","type":"chat","title":"GIMSIMTest","category":"info","created_on":1549953711,"expire_on":1550126511,"receivers":["arun.kv@nic.in","syam.krishna@nic.in","sunish@nic.in","suchitra@nic.in","manoj.pa@nic.in","sapna.kapoor@nic.in","muthu@nic.in","dg@nic.in","pkmalik@nic.in","deepak.mittal@nic.in","abby.murali@nic.in"]}';
            $hmac = base64_encode(hash_hmac('sha256', $encoded_params, 'bd22dba50c589acc7e138d373069eb81d43f91839e60e309ec7db462a57b46d3', true));
            $headers = ['clientid' => $client_id, 'clientsecret' => $client_secret, 'hmac' => $hmac, 'content-type' => 'application/json', 'Accept' => 'application/json'];
            $response = $client->request('POST', $url, ['body' => $encoded_params, 'headers' => $headers, 'http_errors' => false]);
            $body = $response->getBody();
            $data = json_decode($body);
            echo $body;
            echo $string_message;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return 1;
    }
}
