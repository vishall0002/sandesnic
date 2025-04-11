<?php

namespace App\Command\Onetimes;

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
class GIMSBroadcastCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->entityManager = $em;
    }

    protected function configure()
    {
        $this->setName('app:onetime:broadcast')
                ->setDescription('One time broadcast command')
                ->setHelp('One time broadcast command');
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
        

        // $gims = "<font color= '#ff0000'><strong>⚙</strong></font><font color= '#993366'><strong>Down Time Intimation - 20 Dec 2019 (5PM-10PM)</font></strong><br /><br /><font color= '#333333'>Dear GIMS User,<br /></font><br /><font color= '#333333'>We have scheduled an upgrade of GIMS messaging platform and services on 20-12-2019 from 5:00 PM IST onwards. This activity is expected to complete in 5 hours.</font><br /><br /><font color= '#333333'>GIMS messaging and API services will not be available during this period.</font><br /><br /><font color= '#666699'>Regards<br />GIMS Team</font>";
        $gims ="Dear Sir <br />Government Instant Messenger (GIMS) is planned for migration to Ver 2.0 along with server upgrades. The migration activity is scheduled on 18th August 2020 (Today) from 5:00 PM to 8:00 PM.<br />GIMS services will be down during this period and the current GIMS app (Android/iOS) shall be permanently deactivated.<br />GIMS 2.0 (Android/iOS) shall be released by today evening and the download link for the app shall be intimated.<br /><u><b>Please note that you must download and update to GIMS Ver. 2.0 to continue using GIMS services after the upgrade.</b></u><br />Regards<br />GIMS Team";
        $hmac = '';
        try {
            $client = new Client(['verify' => false]);
            // die('Please remove this line of code if you are really intended to do');
            // $url = 'http://10.247.143.141/v1/api/message/broadcast';
            $url = 'http://apigateway.gimkerala.nic.in/v2/api/message/broadcast';
            $encoded_params = '{"message":"'.$gims.'","type":"chat","title":"GIMS Maintenance Info","category":"info","created_on":1549953711,"expire_on":1550126511}';
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
    }
}

