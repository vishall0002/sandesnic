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
class GIMSBroadcastOrganizationCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->entityManager = $em;
    }

    protected function configure()
    {
        $this->setName('app:onetime:broadcast:organization')
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
        

	$gims="Swachhta Pakhwada <br /> <br /> Swachhta Pakhwada to be observed from 1st to 15th February 2020 in National Informatics Centre (NIC). <br/> <br/>Kindly ensure maximum participation and do cleanliness of your office and surroundings during the Pakhwada. Include the water tank, water cooler and RO cleanliness also in your activities.<br/> <br/>Participate in the Swachhta Pledge being taken on 3rd February 2020.<br />";
        $hmac = '';
        try {
             $client = new Client(['verify' => false]);
             // die('Please remove this line of code if you are really intended to do');
             // $url = 'http://10.247.143.141/v1/api/message/broadcast';
             $url = 'http://apigateway.gimkerala.nic.in/v1/api/message/broadcast/organization';
             $encoded_params = '{"org_id":"a12c8450-56c0-45a0-a6ce-addfc997e09a", "message":{"message":"'.$gims.'","type":"chat","title":"General Broadcast Message","category":"info","created_on":1549953711,"expire_on":1550126511}}';
             $hmac = base64_encode(hash_hmac('sha256', $encoded_params, 'bd22dba50c589acc7e138d373069eb81d43f91839e60e309ec7db462a57b46d3', true));
             $headers = ['clientid' => $client_id, 'clientsecret' => $client_secret, 'hmac' => $hmac, 'content-type' => 'application/json', 'Accept' => 'application/json'];
             $response = $client->request('POST', $url, ['body' => $encoded_params, 'headers' => $headers, 'http_errors' => false]);
             $body = $response->getBody();
             $data = json_decode($body);
	     dump($data);
        } catch (ClientException $ce) {
            echo $ce->getMessage();
        } catch (RequestException $re) {
            echo $re->getMessage();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
