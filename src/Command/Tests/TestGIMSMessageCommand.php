<?php

namespace App\Command\Tests;

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
class TestGIMSMessageCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->entityManager = $em;
    }

    protected function configure()
    {
        $this->setName('app:test-gims-message')
                ->setDescription('Test GIMS Message Command')
                ->setHelp('test-gims-message command : Send GIMS Message');
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

        $hmac = '';
        echo 'CLIENTID-'.$client_id.PHP_EOL;
        echo 'CLIENTSECRET-'.$client_secret.PHP_EOL;
        try {
            $client = new Client(['verify' => false]);
            $url = 'http://dwar1.gims.gov.in/v1/api/message/multicast';
            $encoded_params = '{"message":"GIMS Instant Message sending from portal test","type":"chat","title":"GIMSIMTest","category":"info","created_on":1549953711,"expire_on":1550126511,"receivers":["bose.vipin@nic.in"]}';
            echo $encoded_params.PHP_EOL;
            $hmac = base64_encode(hash_hmac('sha256', $encoded_params, 'bd22dba50c589acc7e138d373069eb81d43f91839e60e309ec7db462a57b46d3', true));
            echo "HMAC Key -> bd22dba50c589acc7e138d373069eb81d43f91839e60e309ec7db462a57b46d3".PHP_EOL;
            dump($hmac);
            $headers = ['clientid' => $client_id, 'clientsecret' => $client_secret, 'hmac' => $hmac];
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
        
        // // Arun Data
        // try {
        //     $client = new Client(['verify' => false]);
        //     $url = 'http://dwar1.gims.gov.in/v1/api/message/multicast';
        //     $encoded_params = '{"message":"GIM meeting scheduled","type":"chat","title":"Meeting Reminder","file_url":"https://xyz.gov.in/abcd.pdf","file_content_type":"application/pdf","category":"event","event_start":1549953711,"event_end":1550126511,"created_on":1549953711,"expire_on":1550126511,"receivers":["arun.kv@nic.in","abby.murali@nic.in"]}';
        //     echo $encoded_params.PHP_EOL;
        //     $hmac = base64_encode(hash_hmac('sha256', $encoded_params, 'a86c4eecf12446ff273afc03e1b3a09a911d0b7981db1af58cb45c439161295', true));
        //     echo "HMAC Key -> a86c4eecf12446ff273afc03e1b3a09a911d0b7981db1af58cb45c439161295".PHP_EOL;
        //     dump($hmac);
        //     $headers = ['clientid' => $client_id, 'clientsecret' => $client_secret, 'hmac' => $hmac];
        //     $response = $client->request('POST', $url, ['body' => $encoded_params, 'headers' => $headers, 'http_errors' => false]);
        //     $body = $response->getBody();
        //     $data = json_decode($body);
        //     dump($data);
        // } catch (ClientException $ce) {
        //     echo $ce->getMessage();
        // } catch (RequestException $re) {
        //     echo $re->getMessage();
        // } catch (Exception $e) {
        //     echo $e->getMessage();
        // }
    }
}
