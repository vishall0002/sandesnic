<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\LDAPAuthentication;
use App\Services\XMPPGeneral;



class DashboardIndexController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $LDAPAuthentication;

    public function __construct(LDAPAuthentication $ldapauthenticator, XMPPGeneral $xmpp)
    {
        $this->LDAPAuthentication = $ldapauthenticator;
        $this->xmppGeneral = $xmpp;
    }

    /**
     * @Route("/getindexdata", name="get_index_data")
     */

    public function getIndexData()
    {
        $jsonFilePath = $this->getParameter('kernel.project_dir') . '/data.json';
        // Check if the file exists
        if (!file_exists($jsonFilePath)) {
            throw $this->createNotFoundException('JSON file not found');
        }
        // Read the contents of the JSON file
        $jsonData = file_get_contents($jsonFilePath);
        // Decode the JSON data
        $data = json_decode($jsonData, true);
        return new JsonResponse($data);
    }

    /**
     * @Route("/getmessagedata", name="get_message_data")
     */
    // Paras
    public function getMessageData()
    {
        $jsonFilePath = $this->getParameter('kernel.project_dir') . '/message.json';
        // Check if the file exists
        if (!file_exists($jsonFilePath)) {
            throw $this->createNotFoundException('JSON file not found');
        }
        // Read the contents of the JSON file
        $jsonData = file_get_contents($jsonFilePath);
        // Decode the JSON data
        $data = json_decode($jsonData, true);
        return new JsonResponse($data);
    }


    //new function by vishal to get the count of message in last 12 months
    
    /**
    * @Route("/getmonthlymessagedata", name="get_monthly_message_data")
    */
    public function getMonthlyMessageData()
    {
        $jsonFilePath = $this->getParameter('kernel.project_dir') . '/MonthlymessageCountdata.json';

        if (!file_exists($jsonFilePath)) {
            throw $this->createNotFoundException('Monthly message count JSON file not found');
        }

        $jsonData = file_get_contents($jsonFilePath);
        $data = json_decode($jsonData, true);

        return new JsonResponse($data);
    }

}
