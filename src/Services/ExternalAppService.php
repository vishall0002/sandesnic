<?php

namespace App\Services;

use App\Entity\Portal\Employee;
use App\Entity\Portal\ExternalApps;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class ExternalAppService
{
    private $emr;
    private $security;
    private $defaultValue;
    private $appENV;
    private $logger;

    public function __construct(LoggerInterface $externalAppLogger, EntityManagerInterface $em, Security $security, DefaultValue $defVal)
    {
        $this->emr = $em;
        $this->security = $security;
        $this->defaultValue = $defVal;
        $this->appENV = $this->defaultValue->getEnvironment();
        $this->logger = $externalAppLogger;
    }

    public function externalAppServiceCall($app_title, $client_secret, $hmac_key, $ip_list, $mobileNumber)
    {
        // Same GuId for all Tables
        $em = $this->emr;
        $em->getConnection()->beginTransaction();

        try {
            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $userName = $app_title;
            $userEmail = $app_title.'@gimkerala.nic.in';
            $userEnabled = true;
            // $userSalt = 'D.uKCe.VMirGIkUB/MFUJpf2VRdOrG5bHklwCafIAlY';
            // $userPassword = 'f382fce15bf81326ecaabf9ac7ebacb1c3ce86455e3ebe527ece28561f4017a8';
            // $date = new \DateTime();
            //$curdate = date('Y-m-d H:i:s.u');
            $userLastLogin = new \DateTime('now');
            $userConfirmationToken = null;
            $userGuId = $uuid->toString();
            $userAttemped = '0';
            $userIsLogged = '0';
            $userIsSuspended = '0';
            $userAttempedAt = null;
            $userIsFcp = true;
            $userIsEmailVerified = false;
            $userIsMobileVerified = true;
            $userNotificationOpted = '0';
            $userIsLdap = true;
            $userMobileNumber = $mobileNumber;
            $userIsBetaUser = '0';

            $theUser = $this->security->createUser();
            $theUser->setUsername($userName);
            $theUser->setEmail($userEmail);
            $theUser->setRoles(['ROLE_MEMBER']);
            $theUser->setPassword('nic*123');
            $theUser->setIsFcp(false);
            $theUser->setEnabled(true);
            $this->security->updateUser($theUser);

            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $appUser = $em->getRepository("App:Portal\User")->findOneByUsername($app_title);

            $employeeName = $app_title;
            $employeeEmail = $app_title.'@gimkerala.nic.in';
            $employeeMobile = $mobileNumber;
            $employeeDesignation = '66';
            $employeeJID = $app_title.'@gimkerala.nic.in';
            $employeeOU = '1';
            $employeeActive = 'Y';
            $employeeJabberName = $app_title;
            $employeeRegistered = 'N';
            $employeeRegisteredDate = new \DateTime('now');
            $employeeCoverImage = '6';
            $employeeGender = 'M';
            $employeePhoto = '4';
            $employeeHost = 'apigateway.gimkerala.nic.in';
            $employeeLevel = '1';
            $employeeUserId = $appUser->getId();
            $employeee2ee = 'v1';
            //$employeeGuId = $uuid->toString();
            $employeeGuId = $userGuId;
            $employeeCode = '0';

            $employee = new Employee();
            $employee->setEmployeeName($employeeName);
            $employee->setEmailAddress($employeeEmail);
            $employee->setMobileNumber($employeeMobile);
            $employeeDesignation = $em->getReference('App:Portal\Designation', $employeeDesignation);
            $employee->setDesignation($employeeDesignation);
            $employee->setJabberId($employeeJID);
            $employeeOU = $em->getReference('App:Portal\OrganizationUnit', $employeeOU);
            $employee->setOrganizationUnit($employeeOU);
            $employee->setIsActive($employeeActive);
            $employee->setJabberName($employeeJabberName);
            $employee->setIsRegistered($employeeRegistered);
            $employee->setRegisteredDate($employeeRegisteredDate);
            $employeeCoverImage = $em->getReference('App:Portal\FileDetail', $employeeCoverImage);
            $employee->setCoverImage($employeeCoverImage);
            $employeeGender = $em->getReference('App:Masters\Gender', $employeeGender);
            $employee->setGender($employeeGender);
            $employeePhoto = $em->getReference('App:Portal\FileDetail', $employeePhoto);
            $employee->setPhoto($employeePhoto);
            $employee->setHost($employeeHost);
            $employee->setEmployeeLevel($employeeLevel);
            $employeeUserId = $em->getReference('App:Portal\User', $employeeUserId);
            $employee->setUser($employeeUserId);
            $employee->setE2ee($employeee2ee);
            $employee->setGuId($employeeGuId);
            $employee->setEmployeeCode($employeeCode);

            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $appName = $app_title;
            $appTitle = $app_title;
            $appMobile = $mobileNumber;
            $appClientId = $client_secret;
            $appHmacKeyEnc = $hmac_key;
            $appIpWhiteList = $ip_list;
            $appLogoId = null;
            $appHomeURL = 'gims.kerala.nic.in';
            $appPrivacyLink = '0';
            $appIntegrationScope = 'PR';
            $appActive = true;
            $appParentOU = '1';
            $appDescription = 'External App - '.$app_title;
            //$appGuId =  $uuid->toString();
            $appGuId = $userGuId;

            $externalApps = new ExternalApps();
            $externalApps->setGuId($appGuId);
            $externalApps->setAppName($appName);
            $externalApps->setMobileNumber($appMobile);
            $externalApps->setAppTitle($appTitle);
            $externalApps->setClientId($appClientId);
            $externalApps->setHmacKey($appHmacKeyEnc);
            $externalApps->setIpWhiteList($appIpWhiteList);
            $externalApps->setAppLogoId($appLogoId);
            $externalApps->setHomeURL($appHomeURL);
            $externalApps->setPrivatePolicyLink($appPrivacyLink);
            $externalApps->setIntegrationScope($appIntegrationScope);
            $externalApps->setActive($appActive);
            $externalApps->setParentOuId($appParentOU);
            $externalApps->setAppDescription($appDescription);

            //$em->persist($user);
            $em->persist($employee);
            $em->persist($externalApps);
            $em->flush();
            $em->getConnection()->commit();
            $message = 'Service Completed';
            $status = 'success';
        } catch (Exception $ex) {
            $em->getConnection()->rollback();
            $message = $ex->getMessage();
            $status = 'failed';
            //echo 'FAIL - An error has been occurred for this service <br/>'.PHP_EOL;
        }

        return $status;
    }

    public function externalAppRegister($userID, $payload)
    {
        $url = "http://10.162.0.164:8081/v5/api/apps/register";
        return $this->externalAppServiceCall1($url, $userID, $payload, 'POST');
    }

    public function externalAppServiceCall1($URL, $userID, $payload, $requestType = 'POST')
    {
        $appENV = 'OFFLINE';
        // if ('OFFLINE' !== $this->appENV) {
        if('OFFLINE' == $appENV)
        {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');
            try {
                $client = new Client();
                $headers = ['user_id' => $userID, 'Content-Type' => 'application/json'];
                $this->logger->info('XMPP V5 Group Management Service Call Initiated ');
                $this->logger->info('XMPP V5 API URL '.$URL);
                $this->logger->info('XMPP V5 API Headers '.json_encode($headers));
                $this->logger->info('XMPP V5 API Values Passed '.$payload);
                $response = $client->request($requestType, $URL, ['body' => $payload, 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('XMPP V5 API Response Received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (Exception $e) {
                $this->status = 'false';
                $message = $e->getMessage();
                $this->logger->info('XMPP V5 API Exception GENERAL '.$message);
                $data = [];
            }
        } else {
            return false;
        }
    }

    public function externalAppActivateDeactivate($userID, $payload)
    {
       
        $url = "http://10.162.0.164:8081/v5/api/apps/deactivate";
        return $this->externalAppActivateDeactivateCall($url, $userID, $payload, 'POST');
    }

    public function externalAppActivateDeactivateCall($URL, $userID, $payload, $requestType = 'POST')
    {
        $appENV = 'OFFLINE';
        // if ('OFFLINE' !== $this->appENV) {
        if('OFFLINE' == $appENV)
        {
            $status = 'false';
            $message = 'Init';
            $data = json_decode('init');
            try {
                $client = new Client();
                $headers = ['user_id' => $userID, 'Content-Type' => 'application/json'];
                $this->logger->info('XMPP V5 Group Management Service Call Initiated ');
                $this->logger->info('XMPP V5 API URL '.$URL);
                $this->logger->info('XMPP V5 API Headers '.json_encode($headers));
                $this->logger->info('XMPP V5 API Values Passed '.$payload);
                $response = $client->request($requestType, $URL, ['body' => $payload, 'headers' => $headers, 'http_errors' => true]);
                $body = $response->getBody();
                $this->logger->info('XMPP V5 API Response Received '.$body);
                $status = 'true';
                $message = 'Data found!';
                $data = json_decode($body);
            } catch (Exception $e) {
                $this->status = 'false';
                $message = $e->getMessage();
                $this->logger->info('XMPP V5 API Exception GENERAL '.$message);
                $data = [];
            }
        } else {
            return false;
        }
    }

}
