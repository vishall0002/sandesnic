<?php

namespace App\Command\Schedules;

use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command mainly serve the purpose of notifying the department users regarding
 * a new upload is available mentioning Version Number.
 *
 * @author Vipin Bose
 */
class PreviousDayAppMessagesCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->entityManager = $em;
    }

    protected function configure()
    {
        $this->setName('app:schedule:dumapp:previous')
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
        $date = new \DateTime(); // For today/now, don't pass an arg.
        $date->modify('-1 day');
        $ladate = $date->format('Y-m-d');
        $displaydate = $date->format('d-m-Y');

        $em = $this->entityManager;
        $myCon = $em->getConnection();
        $dql = <<<SQL
        select b.app_title,sum(message_count) as message_count from report.message_activity_app a join gim.apps b on a.app_id=b.id where date_hour::date='$ladate' and app_name not in ('audit-test-app') group by b.app_title order by 2 desc;
SQL;
        $qryTM = $myCon->prepare($dql);
        $qryTM->execute();
        $totalMessages = $qryTM->fetchAll(FetchMode::NUMERIC);

        $gims = '<b>Sandes Gateway Message Statistics <br /><br /><br /> '.$displaydate.' </b><br /><br /> ';
        $gt = 0;
        foreach ($totalMessages as $totalMessage) {
            $gims .= $totalMessage[0].':<b>'.$totalMessage[1].'</b><br/>';
            $gt = $gt + (int) $totalMessage[1];
        }
        $gims .= '<b> TOTAL : '.$gt.'</b><br/>';

        $hmac = '';
        try {
            $client = new Client(['verify' => false]);
            $url = 'http://dwar1.gims.gov.in/v1/api/message/multicast';
            // $encoded_params = '{"message":"'.$gims.'","type":"chat","title":"GIMSIMTest","category":"info","created_on":1549953711,"expire_on":1550126511,"receivers":["bose.vipin@nic.in","arun.kv@nic.in","syam.krishna@nic.in","sunish@nic.in","manoj.pa@nic.in"]}';
            $encoded_params = '{"message":"'.$gims.'","type":"chat","title":"GIMSIMTest","category":"info","created_on":1549953711,"expire_on":1550126511,"receivers":["bose.vipin@nic.in","arun.kv@nic.in","syam.krishna@nic.in","sunish@nic.in","suchitra@nic.in","manoj.pa@nic.in","sapna.kapoor@nic.in","muthu@nic.in","neeta@nic.in","pkmalik@nic.in","deepak.mittal@nic.in","abby.murali@nic.in"]}';
            $hmac = base64_encode(hash_hmac('sha256', $encoded_params, 'bd22dba50c589acc7e138d373069eb81d43f91839e60e309ec7db462a57b46d3', true));
            $headers = ['clientid' => $client_id, 'clientsecret' => $client_secret, 'hmac' => $hmac, 'content-type' => 'application/json', 'Accept' => 'application/json'];
            $response = $client->request('POST', $url, ['body' => $encoded_params, 'headers' => $headers, 'http_errors' => false]);
            $body = $response->getBody();
            $data = json_decode($body);
        } catch (ClientException $ce) {
            echo $ce->getMessage();
        } catch (RequestException $re) {
            echo $re->getMessage();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 1;
    }
}
