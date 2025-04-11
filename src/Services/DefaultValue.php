<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\FetchMode;

class DefaultValue
{
    private $emr;
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->emr = $em;
        $this->logger = $logger;
    }

    public function getDefaultValue($defaultValueCode)
    {
        $em = $this->emr;
        $defaultValue = $em->getRepository('App:Masters\DefaultValue')->findOneBy(['code' => $defaultValueCode]);
        if ($defaultValue) {
            return $defaultValue->getDefaultValue();
        } else {
            return 0;
        }
    }

    public function getDefaultValueObject($defaultValueCode)
    {
        $em = $this->emr;
        $defaultValue = $em->getRepository('App:Masters\DefaultValue')->findOneBy(['code' => $defaultValueCode]);
        if ($defaultValue) {
            return json_decode($defaultValue->getDefaultValue());
        } else {
            return 0;
        }
    }

    public function updateTBAPIData($apiData)
    {
        $em = $this->emr;
        $defaultValue = $em->getRepository('App:Masters\DefaultValue')->findOneBy(['code' => 'TBAPI']);
        $defaultValue->setDefaultValue(\json_encode($apiData));
        $em->persist($defaultValue);
        $em->flush();
    }

    public function getTBAPIData()
    {
        $em = $this->emr;
        $defaultValue = $em->getRepository('App:Masters\DefaultValue')->findOneBy(['code' => 'TBAPI']);

        return $defaultValue->getDefaultValue();
    }

    public function getEnvironment()
    {
        $this->logger->info('Current environment '.$_ENV['RUNNING_MODE']);
        if ('PRODUCTION' === $_ENV['RUNNING_MODE']) {
            $appEnv = 'PROD';
        } elseif ('NPRODUCTION' === $_ENV['RUNNING_MODE']) {
            $appEnv = 'NPROD';
        } elseif ('DEVELOPMENT' === $_ENV['RUNNING_MODE']) {
            $appEnv = 'DEV';
        } else {
            $appEnv = 'OFFLINE';
        }
        // $em = $this->emr;
        // $appEnv = $em->getRepository('App:Masters\DefaultValue')->findOneByDefaultValue($hostName);
        // if ($appEnv) {
        //     return $appEnv->getEnvironment();
        // } else {
        //     return 0;
        // }
        return $appEnv;
    }

    public function getDefaultPrivilege(){
        $em = $this->emr;
        $myCon = $em->getConnection();
        $dql = <<<SQL
        select bit_or(auth_privilege)::bigint from gim.v_employee_privilege_group_masks where group_code in ('REGISTERED','AADHAR_LINKED','VERIFIED');
SQL;
        $qryTM = $myCon->prepare($dql);
        $qryTM->execute();
        $default_privilege_value = $qryTM->fetchAll(FetchMode::NUMERIC);
        return $default_privilege_value[0][0];
                
    }
}
