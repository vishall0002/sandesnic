<?php

namespace App\Command;

use App\Entity\Portal\Employee;
use App\Entity\Portal\FileDetail;
use App\Entity\Portal\Profile;
use App\Entity\Portal\User;
use App\Services\APIMethods;
use App\Services\DefaultValue;
use App\Services\EMailer;
use App\Services\ImageProcess;
use App\Services\OneTimeLinker;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;
use App\Services\XMPPGeneral;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment as Environment;
use App\Security\Encoder\SecuredLoginPasswordEncoder;

/**
 * This command mainly serve the purpose of notifying the department users regarding
 * a new upload is available mentioning Version Number.
 *
 * @author Amal
 */
class ImportEmployeeCommand extends Command
{
    private $emailer;
    private $entityManager;
    private $profileWorkspace;
    private $metadata;
    private $defaultValue;
    private $imageProcess;
    private $twig;
    private $oneTimeLinker;
    private $api_methods;
    private $xmppGeneral;

    public function __construct(EntityManagerInterface $em, APIMethods $api_methods, XMPPGeneral $xmpp, EMailer $emailer, Environment $twig, DefaultValue $defVal, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, ImageProcess $imageProcess, OneTimeLinker $oneTimeLinker, SecuredLoginPasswordEncoder $password_encoder)
    {
        parent::__construct();
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->defaultValue = $defVal;
        $this->imageProcess = $imageProcess;
        $this->api_methods = $api_methods;
        $this->xmppGeneral = $xmpp;

        $this->emailer = $emailer;
        $this->entityManager = $em;
        $this->twig = $twig;
        $this->oneTimeLinker = $oneTimeLinker;
        $this->password_encoder = $password_encoder;
    }

    protected function configure()
    {
        $this->setName('app:import-employees')
                ->setDescription('Import Employees')
                ->setHelp('app:import-employee command : This may be used in the command line to import employees in bulk')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        echo date('d-M-y H:i:s'). ' Another round of import getting started '.PHP_EOL;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $em = $this->entityManager;
        // $ImportEmployees = $em->getRepository("App:Portal\ImportEmployee")->findBy(['isScheduled' => true, 'batchCode' => 'a60644da']);
        $whether_running = $em->getRepository("App:Portal\ImportEmployee")->findBy(['isScheduled' => true, 'isProcessing' => true, 'isFinalised' => false, 'isRejected' => false], ['id' => 'ASC']);
        if ($whether_running) {
            echo date('d-M-y H:i:s'). ' A job is in progress'.PHP_EOL;
            return 0;
        } else {
            $ImportEmployee = $em->getRepository("App:Portal\ImportEmployee")->findOneBy(['isScheduled' => true, 'isProcessing' => false, 'isFinalised' => false, 'isRejected' => false]);
            if (!$ImportEmployee) {
                echo date('d-M-y H:i:s'). ' There is no data pending to be imported now...!'.PHP_EOL;
                return 0;
            }
            
            $objid = $ImportEmployee->getGuId();
            echo date('d-M-y H:i:s'). 'Processing the batch '. strtoupper(substr($objid, 0, 8)). PHP_EOL;

            $myCon = $em->getConnection();
            $qry = 'SELECT ename,gender,designation,ecode,elevel,lower(email) as email,lower(alternate_email) as alternate_email,mobile,lower(country_code) as country_code,district_lgdcode,dosa,designation_id,employee_level_id,ou_id,o_id,id as import_record_id FROM gim.portal_import_employees_details  where batch_code=:batchcode AND is_imported IS NULL ORDER BY id ASC limit 200';
            $preparedQry = $myCon->prepare($qry);
            $preparedQry->bindValue('batchcode', substr($objid, 0, 8));
            $preparedQry->execute();
            $result = $preparedQry->fetchAll();
            $ImportEmployee->setIsProcessing(true);
            $ImportEmployee->setProcessingStartTime(new \DateTime('now'));
            $em->flush();
            $status = $this->finaliseProcess($em, $result, $ImportEmployee, $objid);
            $msgEmail = '';
            $msgMobile = '';
            if ($status['duplicates']) {
                $msgEmail = 'Duplicate E-mail records found - '.$status['duplicates'].'!!   ';
            }
            if ($status['duplicateMobileNumber']) {
                $msgMobile = 'Duplicate MobileNumber records found - '.$status['duplicateMobileNumber'].'!!   ';
            }
            $duplicateTotCount = $status['duplicates'] + $status['duplicateMobileNumber'];
            $ImportEmployee->setDuplicatesCount($ImportEmployee->getDuplicatesCount() + (int)$duplicateTotCount);

            $myCon = $em->getConnection();
            $qry = 'SELECT count(1) as pendingcount FROM gim.portal_import_employees_details  where batch_code=:batchcode AND is_imported IS NULL';
            $preparedQry = $myCon->prepare($qry);
            $preparedQry->bindValue('batchcode', substr($objid, 0, 8));
            $preparedQry->execute();
            $result = $preparedQry->fetchAll();
            if ($result[0]['pendingcount'] == 0){
                $qry = 'select count(e.mobile_no) as importedcount
                from gim.portal_import_employees as i INNER JOIN gim.portal_import_employees_details as im ON i.batch_code = im.batch_code 
                inner join gim.employee as e ON im.mobile = e.mobile_no
                WHERE i.batch_code = :batchcode';
                $preparedQry = $myCon->prepare($qry);
                $preparedQry->bindValue('batchcode', substr($objid, 0, 8));
                $preparedQry->execute();
                $result = $preparedQry->fetchAll();
                $ImportEmployee->setInsertedCount($result[0]['importedcount']);
                $ImportEmployee->setDuplicatesCount($ImportEmployee->getRecordsCount() - (int)$result[0]['importedcount']);
                $ImportEmployee->setIsFinalised(true);
                echo date('d-M-y H:i:s'). ' **** A Batch fully completed ' . strtoupper(substr($objid, 0, 8)).PHP_EOL;
            } else {
                $qry = 'SELECT count(1) as importedcount FROM gim.portal_import_employees_details  where batch_code=:batchcode AND is_imported = true';
                $preparedQry = $myCon->prepare($qry);
                $preparedQry->bindValue('batchcode', substr($objid, 0, 8));
                $preparedQry->execute();
                $result = $preparedQry->fetchAll();
                $ImportEmployee->setInsertedCount($result[0]['importedcount']);
                $ImportEmployee->setIsProcessing(false);
                echo date('d-M-y H:i:s'). ' ---- batch not fully completed '. strtoupper(substr($objid, 0, 8)).PHP_EOL;
                
            }
            $ImportEmployee->setUpdateMetaData($this->metadata->getPortalMetadata('U'));
            $em->flush();
            
        }
        return 1;
    }

    private function finaliseProcess($em, $result, $ImportEmployee, $objid)
    {
        $employeeCoverImage = $em->getReference('App:Portal\FileDetail', 6);
        $duplicates = '0';
        $duplicateMobileNumber = '0';

        $inserted = '0';
        $emailDuplicate = 'Duplicate E-mail Address';
        $mobileDuplicate = 'Duplicate Mobile Number';
        $status = [];
        $iterator = 0;

        $logged_employee = $em->getRepository("App:Portal\Employee")->findOneByUser($ImportEmployee->getUser());

        foreach ($result as $emp2I) {
            $iterator = $iterator + 1;
            $myCon = $em->getConnection();
            $myCon->beginTransaction();
            try {
                $employeeName = $emp2I['ename'];
                $employeeCode = $emp2I['ecode'];
                $employeeCountryCode = $emp2I['country_code'];
                $district_lgdcode = $emp2I['district_lgdcode'];
                $import_record_id = $emp2I['import_record_id'];

                $employee_district = $em->getRepository('App:Masters\District')->findOneByDistrictCode($district_lgdcode);

                if ('male' == strtolower($emp2I['gender']) || 'm' == strtolower($emp2I['gender'])) {
                    $gender = 'M';
                } elseif ('female' == strtolower($emp2I['gender']) || 'f' == strtolower($emp2I['gender'])) {
                    $gender = 'F';
                } else {
                    $gender = $emp2I['gender'];
                }
                $employeeGender = $em->getReference('App:Masters\Gender', $gender);
                $employeeEmail = strtolower($emp2I['email']);
                $employeeAlternateEmail = strtolower($emp2I['alternate_email']);
                if ('1970-01-01' == $emp2I['dosa']) {
                    $employeeDateSuperannuation = null;
                } else {
                    $employeeDateSuperannuation = new \DateTime($emp2I['dosa']);
                }
                echo date('d-M-y H:i:s'). ' Performing Importing for '.$employeeName.' - code - '.$employeeCode.' - '.$employeeEmail.PHP_EOL;
                $uuid = \Ramsey\Uuid\Uuid::uuid4();
                $employeeGuId = $uuid->toString();
                $employeeMobile = $emp2I['mobile'];
                $employeeDesignation = $em->getReference('App:Portal\Designation', $emp2I['designation_id']);
                $employeeOU = $ImportEmployee->getOrganizationUnit();
                $host = $employeeOU->getOrganization()->getVhost();
                $employeeHost = $host;
                $jabberName = 'b0'.substr(str_replace('-', '', $employeeGuId), 0, 14);
                $jabberID = $jabberName.'@'.$employeeHost;
                $employeeActive = 'Y';
                $employeeRegistered = 'N';

                $employee_exists_bymobile = $em->getRepository('App:Portal\Employee')->findBy(['mobileNumber' => $employeeMobile]);

                if ($employee_exists_bymobile) {

                    // If user is unverified - Paras
                    $unverified_employee = $em->getRepository('App:Portal\Employee')->findOneBy(['mobileNumber' => $employeeMobile, 'accountStatus' => 'U', 'isRegistered' => 'Y']);

                    // if($unverified_employee) {

                        $user_id = $unverified_employee->getUser();
                        $gu_id = $unverified_employee->getGuId();
                        $remarks = "OK";
                        $employeeRemark = null;
                        try {
                            // $api_return = $this->xmppGeneral->verifyLiteUser($user_id, $gu_id, $remarks);
                            // $api_return_status = json_decode($api_return);

                            // if ('success' === $api_return_status->status) {
                                $employee_exists_byemail = $em->getRepository('App:Portal\Employee')->findBy(['emailAddress' => $employeeEmail]);
                                $employee_exists_byaltemail = $em->getRepository(Employee::class)->findOneByAlternateEmailAddress($employeeEmail);
                                $user_exists_by_email = $em->getRepository(User::class)->findOneByEmail($employeeEmail);

                                $profileUser = $em->getRepository(Profile::class)->findOneByUser($unverified_employee->getUser());
                                $profileUser->setOrganizationUnit($unverified_employee->getOrganizationUnit());

                                $portalUser = $em->getRepository(User::class)->findOneByUser($unverified_employee->getUser());
                                $portalUser->setUsername($employeeMobile);
                                if (!$user_exists_by_email && strlen($employeeEmail) > 0) {
                                    $portalUser->setEmail($employeeEmail);
                                    $portalUser->setUsername($employeeEmail);
                                }

                                $unverified_employee->setEmployeeName(substr($employeeName,0,50));
                                $unverified_employee->setGender($employeeGender);
                                $unverified_employee->setDesignation($employeeDesignation);
                                $unverified_employee->setEmployeeCode($employeeCode);
                                $unverified_employee->setOrganizationUnit($employeeOU);

                                if (!$employee_exists_byemail && strlen($employeeEmail) > 0) {
                                    $unverified_employee->setEmailAddress($employeeEmail);
                                }
                                if (!$employee_exists_byaltemail && strlen($employeeAlternateEmail) > 0) {
                                    $unverified_employee->setAlternateEmailAddress($employeeAlternateEmail);
                                }

                                $locationCountry = $em->getRepository("App:Masters\Country")->findOneBy(['countryCode' => strtoupper($employeeCountryCode)]);
                                if (!$locationCountry) {
                                    $locationCountry = $em->getRepository("App:Masters\Country")->findOneBy(['countryCode' => 'IN']);
                                }

                                $countryMobileCode = $locationCountry->getPhoneCode();
                                $countryLocation = $locationCountry->getCountryCode();
                                $unverified_employee->setLocation($countryLocation);
                                $unverified_employee->setPhoneCode($countryMobileCode);
                                $unverified_employee->setCountry($locationCountry);
                                $unverified_employee->setDosa($employeeDateSuperannuation);

                                $locationCountry = $em->getRepository("App:Masters\Country")->findOneBy(['countryCode' => strtoupper($employeeCountryCode)]);
                                if ($employee_district) {
                                    $unverified_employee->setDistrict($employee_district);
                                    $unverified_employee->setState($employee_district->getState());
                                } else {
                                    $ouStateDistrict = $em->getRepository("App:Portal\OrganizationUnit")->findOneById($employeeOU);
                                    $oUStateCode = $ouStateDistrict->getState();
                                    $oUDistrictCode = $ouStateDistrict->getDistrict();
                                    $unverified_employee->setState($oUStateCode);
                                    $unverified_employee->setDistrict($oUDistrictCode);
                                }
                                // $unverified_employee->setAccountStatus('V');
                                $em->flush();
                                $employeeRemark = 'Employee Details updated';

                            // } else {
                            //     $employeeRemark = 'Verification failed for existing user';
                            // }
                        } catch (\Exception $ex) {
                                $employeeRemark = 'Internal Server Error';
                        }

                        if($employeeRemark) {
                            $qryM2I = $myCon->prepare('update gim.portal_import_employees_details set is_imported = true, is_duplicate=true, remark=:employeeRemark where  id=:recordid');
                            $qryM2I->bindValue('employeeRemark', $employeeRemark);
                            $qryM2I->bindValue('recordid', $import_record_id);
                            $qryM2I->execute();
                            ++$duplicateMobileNumber;
                            echo date('d-M-y H:i:s'). ' ' . $employeeRemark . '  : ' . $duplicateMobileNumber . PHP_EOL;
                        }

                        // If user is unverified - Paras

                } else {
                    echo date('d-M-y H:i:s'). ' Employee ready for import'.PHP_EOL;
                    $photoFileName = '/var/photos_di/'.$employeeCode.'.jpg';
                    if (file_exists($photoFileName)) {
                        $employeePhoto = stream_get_contents(fopen($photoFileName, 'rb'));
                        $imageDetails = getimagesize($photoFileName);
                        $mime = $imageDetails['mime'];
                        $fileDetail = new FileDetail();
                        $uuid = \Ramsey\Uuid\Uuid::uuid4();
                        $fileDetail->setFileHash($uuid->toString());
                        $fileDetail->setCreatedDate(new \DateTime('now'));
                        $fileDetail->setFileData($employeePhoto);
                        $thumb = $this->imageProcess->generateThumbnail($employeePhoto);
                        $fileDetail->setThumbnail($thumb);
                        $fileDetail->setContentTypeCode($em->getRepository('App:Portal\ContentType')->findOneByDescription($mime));
                        $fileDetail->setFileType($em->getRepository('App:Portal\FileType')->findOneByCode('DP'));
                        $em->persist($fileDetail);
                    } else {
                        if ('F' === $employeeGender) {
                            $fileDetail = $em->getReference('App:Portal\FileDetail', 1);
                        } else {
                            $fileDetail = $em->getReference('App:Portal\FileDetail', 4);
                        }
                    }

                    $uuid = \Ramsey\Uuid\Uuid::uuid4();

                    $theUser = $em->getRepository(User::class)->findOneByUsername($employeeMobile);

                    $role = $em->getRepository("App:Portal\Roles")->findOneByRole('ROLE_MEMBER');

                    if ($theUser) {
                        $qryM2I = $myCon->prepare('update gim.portal_import_employees_details set is_imported = true, is_duplicate=true, remark=:mobileDuplicate where id =:recordid');
                        $qryM2I->bindValue('mobileDuplicate', "The USER already available");
                        $qryM2I->bindValue('recordid', $import_record_id);
                        $qryM2I->execute();
                        ++$duplicateMobileNumber;
                        echo date('d-M-y H:i:s'). ' The User '.$employeeMobile.' already exists '.PHP_EOL;
                    } else {
                        echo date('d-M-y H:i:s'). ' Portal user does not exist, so creating a new one '.PHP_EOL;
                        $user_email = null;
                        // The following are tricky... the optional E-mail creates complexity
                        // Users typically add duplicate emails.
                        if (strlen($employeeEmail) > 0){
                            $employee_exists_byemail = $em->getRepository('App:Portal\Employee')->findBy(['emailAddress' => $employeeEmail]);
                            $employee_exists_byaltemail = $em->getRepository(Employee::class)->findOneByAlternateEmailAddress($employeeEmail);
                            $user_exists_by_email = $em->getRepository(User::class)->findOneByEmail($employeeEmail);
                            if ($user_exists_by_email){
                                $user_email = $employeeMobile.'@sandes.gov.in';
                            } else {
                                $user_email = $employeeEmail;
                            }
                        } else {
                            $employee_exists_byemail = null;
                            $employee_exists_byaltemail = null;
                            $user_email = $employeeMobile.'@sandes.gov.in';
                        }

                        if (!$theUser){
                            // This change is being done as per the instructions received in e-Mail from Sapna Kapoor and FNI from Manoj PA on 10/02/2022
                            $theUser = new User();
                            $theUser->setUsername($employeeMobile);
                            $uuid = \Ramsey\Uuid\Uuid::uuid4();
                            $theUser->setGuid($uuid->toString());
                        } 
                        $theUser->setEmail($user_email);
                        $theUser->setRoles(['ROLE_MEMBER']);
                        $the_salt = password_hash(uniqid(null, true), PASSWORD_BCRYPT);
                        $theUser->setSalt($the_salt);
                        $the_password = $this->password_encoder->encodePassword('nic*123', $the_salt);
                        $theUser->setPassword($the_password);
                        $theUser->setIsFcp(false);
                        $theUser->setEnabled(true);
                        
                        if (!$employee_exists_bymobile) {
                            // This change is being done as per the instructions received in e-Mail from Sapna Kapoor and FNI from Manoj PA on 10/02/2022
                            $employee = new Employee();
                            $employee->setGuId($employeeGuId);
                            $employee->setMobileNumber($employeeMobile);
                            $employee->setJabberName($jabberName);
                            $employee->setJabberId($jabberID);
                            $employee->setHost($employeeHost);
                            $employee->setPhoto($fileDetail);
                            $employee->setCoverImage($employeeCoverImage);
                        } else {
                            $employee = $employee_exists_bymobile;
                        }
                        $employee->setEmployeeCode($employeeCode);
                        $employee->setEmployeeName(substr($employeeName,0,50));
                        if (!$employee_exists_byemail) {
                            if (strlen($employeeEmail) > 0){
                                $employee->setEmailAddress($employeeEmail);
                            }
                        }
                        if ('' != $employeeAlternateEmail) {
                            if (!$employee_exists_byaltemail) {
                                if (strlen($employeeAlternateEmail) > 0){
                                    $employee->setAlternateEmailAddress($employeeAlternateEmail);
                                }
                            }
                        }
                        $employee->setIsActive($employeeActive);
                        $employee->setIsRegistered($employeeRegistered);
                        $employee->setOrganizationUnit($employeeOU);
                        $employee->setUser($theUser);
                        $employee->setAuthPrivilege($this->defaultValue->getDefaultPrivilege());
                        $employee->setDesignation($employeeDesignation);
                        $metada = $this->metadata->getPortalMetadata('I');
                        $employee->setInsertMetaData($metada);
                        $employee->setGender($employeeGender);
                      

                        $locationCountry = $em->getRepository("App:Masters\Country")->findOneBy(['countryCode' => strtoupper($employeeCountryCode)]);
                        if (!$locationCountry) {
                            $locationCountry = $em->getRepository("App:Masters\Country")->findOneBy(['countryCode' => 'IN']);
                        }

                        $countryMobileCode = $locationCountry->getPhoneCode();
                        $countryLocation = $locationCountry->getCountryCode();
                        $employee->setLocation($countryLocation);
                        $employee->setPhoneCode($countryMobileCode);
                        $employee->setCountry($locationCountry);
                        $employee->setDosa($employeeDateSuperannuation);

                        $locationCountry = $em->getRepository("App:Masters\Country")->findOneBy(['countryCode' => strtoupper($employeeCountryCode)]);
                        if ($employee_district) {
                            $employee->setDistrict($employee_district);
                            $employee->setState($employee_district->getState());
                        } else {
                            $ouStateDistrict = $em->getRepository("App:Portal\OrganizationUnit")->findOneById($employeeOU);
                            $oUStateCode = $ouStateDistrict->getState();
                            $oUDistrictCode = $ouStateDistrict->getDistrict();
                            $employee->setState($oUStateCode);
                            $employee->setDistrict($oUDistrictCode);
                        }

                        $employee->setAppType('P');
                        $employee->setRegistrationMode('O');
                        $employee->setAccountStatus('V');
                        $employee->setUserType(6);

                        $uuid = \Ramsey\Uuid\Uuid::uuid4();
                        $employee->setBackupKey(password_hash($uuid->toString(), PASSWORD_BCRYPT));

                        $profile = new Profile();
                        $profile->setUser($employee->getUser());
                        $uuid = \Ramsey\Uuid\Uuid::uuid4();
                        $profile->setGuid($uuid->toString());
                        $profile->setFromDate(new \DateTime('now'));
                        $profile->setIsEnabled(true);
                        $profile->setIsDefault(true);
                        $profile->setIsCurrent(true);
                        $profile->setIsAdditional(false);
                        $profile->setInsertMetaData($this->metadata->getPortalMetadata('I'));
                        $profile->setRole($role);
                        $profile->setOrganizationUnit($employee->getOrganizationUnit());

                        $em->persist($theUser);
                        $em->persist($employee);
                        $em->persist($profile);
                        $em->flush();
                        $welcomeMessageContent = $this->twig->render('/emailer/welcome.html.twig');
                        echo date('d-M-y H:i:s'). ' It seems everything fine, employee is onboarded.. notify hook started '.PHP_EOL;
                        //  This code is being commented on 03-11-2021, the email delivery has been dead slow and there is no easy solution
                        //  for backgrounding..
                        //  Arun Varghese and Sapna Madam notified about this.
                        
                        // if (!$employee_exists_byemail && $employeeEmail) {
                        //     $this->emailer->sendEmail($employeeEmail, $this->defaultValue->getDefaultValue('EMAILER-WELCOME-MESSAGE-SUBJECT'), $welcomeMessageContent);
                        // //     $this->oneTimeLinker->createOTL($employee->getUser(), $employeeEmail);
                        //     echo date('d-M-y H:i:s'). ' Notification EMail sent '.PHP_EOL;
                        // }
                        // if ($employeeMobile) {
                        //     $sms_mobileno = $countryMobileCode.$employeeMobile;
                        //     $sms_message = "You are officially onboarded to Sandes, the Government instant messaging system. You may install Sandes app from https://www.sandes.gov.in/get and register using your mobile number +$sms_mobileno.Sandes-NICSI";
                        //     $sms_template_id = '1107162383170334979';
                        //     $this->api_methods->sendSMS($ImportEmployee->getUser()->getId(), $sms_mobileno, $sms_message, $sms_template_id);
                        //     echo date('d-M-y H:i:s'). ' Notification SMS sent '.PHP_EOL;
                        // }
                        $qryE2I = $myCon->prepare('update gim.portal_import_employees_details set is_imported = true where id=:recordid');
                        $qryE2I->bindValue('recordid', $import_record_id);
                        $qryE2I->execute();
                        ++$inserted;
                        echo date('d-M-y H:i:s'). ' Inserted Row : '.$inserted.PHP_EOL;
                    }
                }
                $myCon->commit();
            } catch (\Doctrine\DBAL\DBALException $ex) {
                $myCon->rollBack();
                echo date('d-M-y H:i:s'). ' process failed '.$ex->getMessage().PHP_EOL;
            }
           
        }

        $status['duplicates'] = $duplicates;
        $status['duplicateMobileNumber'] = $duplicateMobileNumber;
        $status['inserted'] = $inserted;

        return $status;
    }
}