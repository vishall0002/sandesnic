<?php

namespace App\Services;

use App\Entity\Portal\MetaData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class PortalMetadata
{
    private $emr;
    private $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->emr = $em;
        $this->security = $security;
    }

    public function getPortalMetadata($transactionType)
    {
        $em = $this->emr;
        $loggedUser = $this->security->getUser();
//        $loggedUser = $this->myContainer->get('security.token_storage')->getToken()->getUser();
        if ('anon.' == $loggedUser) {
            $userId = $this->myContainer->getParameter('mobile_view_user_id');
            $loggedUser = $em->getRepository('App:Portal/User')->find($userId);
        }
//        $clientIp = $this->myContainer->get('request')->getClientIp();
        // $serverIpAddress = $_SERVER['SERVER_ADDR'];
        if (isset($_SERVER['SERVER_ADDR'])) {
            $serverIpAddress = $_SERVER['SERVER_ADDR'];
        } else {
            $serverIpAddress = '127.0.0.1';
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $remoteIpAddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $remoteIpAddress = '127.0.0.1';
        }

        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $rmIP = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $remoteIpAddress = array_pop($rmIP);
        }
        $metaData = new MetaData();
        $metaData->setTransactionUserId($loggedUser);
        $metaData->setTransactionRemoteIp($serverIpAddress);
        $metaData->setTransactionServerIp($remoteIpAddress);
        $metaData->setTransactionType($transactionType);
        $em->persist($metaData);
        $em->flush();

        return $metaData;
    }

    public function getPortalMetadataIDforCRONs($transactionType)
    {
        $em = $this->emr;
        $conn = $em->getConnection();
        $sql = "INSERT INTO portal_metadata (transaction_date_time, transaction_user_id, transaction_remote_ip, transaction_server_ip, transaction_type ) VALUES (now(),1,'127.0.0.1', '127.0.0.1','I')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $conn->lastInsertId();
    }
    public function getMetadataValue($metadata_id)
    {
        $em = $this->emr;
        $metadata = $em->getRepository(Metadata::class)->findOneById($metadata_id);
        if ($metadata){
            return $metadata;
        } else {
            return null;
        }
    }
}
