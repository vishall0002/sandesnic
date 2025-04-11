<?php

namespace App\Controller\Portal;

use App\Entity\Portal\ImportEmployee;
use App\Interfaces\AuditableControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\ProfileWorkspace;
use App\Services\PortalMetadata;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\UserBundle\Model\UserManagerInterface;
use App\Entity\Portal\Employee;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Psr\Log\LoggerInterface;

/**
 * @Route("portal/importEmployee")
 */
class ImportEmployeeController extends AbstractController implements AuditableControllerInterface
{
    private $metadata;
    private $profileWorkspace;
    private $logger;

    public function __construct(ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, LoggerInterface $logger) {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="portal_import_employee_index")
     */
    public function index(Request $request)
    {
        return $this->render('portal/importEmployee/index.html.twig');
    }

    /**
     * @Route("/list", name="portal_import_employee_list")
     */
    public function lists(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = 0;
        } else {
            $oU = $this->profileWorkspace->getOu();
        }

        $dql = "SELECT i FROM App:Portal\ImportEmployee i WHERE i.organizationUnit = :ou or :ou = 0 ORDER BY i.id DESC";
        $query = $em->createQuery($dql);
        $query->setParameter('ou', $oU);
        $ImportEmployeesPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 100);
        $ImportEmployeesPaginated->setUsedRoute('portal_import_employee_list');
        return $this->render('portal/importEmployee/_list.html.twig', array('pagination' => $ImportEmployeesPaginated,
        ));
    }

    /**
     * @Route("/new",name="portal_import_employee_new_upload")
     */
    public function newupload(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $result = '';
        $oU = '';
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $dql = "SELECT m.id,m.ministryName FROM App:Masters\Ministry m ORDER BY m.id DESC";
            $saQry = $em->createQuery($dql);
            $result = $saQry->getResult();
            $oU = 0;

        } elseif ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $Ministry = $this->profileWorkspace->getMinistry();
            $dql = "SELECT o.id,o.organizationName FROM App:Portal\Organization o Where o.ministry = :minId ORDER BY o.id DESC";
            $organizationQry = $em->createQuery($dql);
            $organizationQry->setParameter('minId', $Ministry->getId());
            $result = $organizationQry->getResult();

        } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $Organization = $this->profileWorkspace->getOrganization();
            $dql = "SELECT o.id,o.OUName FROM App:Portal\OrganizationUnit o where o.organization=:ouId ORDER BY o.id DESC";
            $ministries = $em->createQuery($dql);
            $ministries->setParameter('ouId', $Organization->getId());
            $result = $ministries->getResult();

        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
        } 
        return $this->render('portal/importEmployee/_form_new.html.twig', array(
                    'caption' => 'Import Employees',
                    'result' => $result,
                    'oU' => $oU
        ));
    }

    /**
     * @Route("/fetchOrg",name="portal_import_employee_fetch_org")
     */
    public function fetchOrg(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('obj');
        $submittedToken = $request->request->get('token');
        // if (!$this->isCsrfTokenValid('form_intention', $submittedToken)) {
        //     return new JsonResponse(['status' => 'error', 'message' => 'Outdated request attempt, please try again via proper login']);
        // }
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');
        $newToken = $token->getValue();
        $dql = "SELECT o.id,o.organizationName FROM App:Portal\Organization o Where o.ministry = :objid ORDER BY o.id DESC";
        $organizationQry = $em->createQuery($dql);
        $organizationQry->setParameter('objid', $objid);
        $organizations = $organizationQry->getResult();
        $data = [];
        foreach ($organizations as $value) {
            $data[$value['id']] = $value['organizationName'];
        }
        return new JsonResponse(['status' => 'success', 'message' => '', 'token' => $newToken, 'data' => $data]);
    }

    /**
     * @Route("/fetchOrgUnit",name="portal_import_employee_fetch_org_unit")
     */
    public function fetchOrgUnit(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('obj');
        $submittedToken = $request->request->get('token');
        // if (!$this->isCsrfTokenValid('form_intention', $submittedToken)) {
        //     return new JsonResponse(['status' => 'error', 'message' => 'Outdated request attempt, please try again via proper login']);
        // }
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');
        $newToken = $token->getValue();
        $dql = "SELECT o.id,o.OUName FROM App:Portal\OrganizationUnit o where o.organization=:objid ORDER BY o.id DESC";
        $organizationUnitsQry = $em->createQuery($dql);
        $organizationUnitsQry->setParameter('objid', $objid);
        $organizationUnits = $organizationUnitsQry->getResult();
        $data = [];
        foreach ($organizationUnits as $value) {
            $data[$value['id']] = $value['OUName'];
        }
        return new JsonResponse(['status' => 'success', 'message' => '', 'token' => $newToken, 'data' => $data]);
    }

    /**
     * @Route("/Uploadcsv/{guid}", name="portal_import_employee_upload")
     */
    public function uploadCsv(Request $request, $guid)
    {
        $fileBag = $request->files;
        $ImportEmployeeFile = $fileBag->get('file');
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        
        $OrganizationUnit = $em->getRepository("App:Portal\OrganizationUnit")->findOneById($guid);
        if ($ImportEmployeeFile) {
            $iterator_value = 0;
            $fileName = $ImportEmployeeFile->getRealPath();
            $fileType = $ImportEmployeeFile->getMimeType();
            $fileOrginal = $_FILES['file']['name'];
            $fileError = $ImportEmployeeFile->getError();
            if ($fileError == UPLOAD_ERR_OK) {
                $arrSupportFormat = array(
                    'application/csv',
                    'text/plain',
                    'application/vnd.ms-excel',
                    'text/csv',
                    'application/octet-stream',
                    'text/comma-separated-values');
                if (!in_array($fileType, $arrSupportFormat)) {
                    $result['error'] = true;
                    $result['message'] = "Unsupported file format";
                    return new JsonResponse($result);
                }

                $file = fopen($ImportEmployeeFile, "r");
                $keysInitial = fgetcsv($file);
                $keys = array_map('trim', $keysInitial);
                if (!in_array("ename", $keys) ||
                        !in_array("gender", $keys) ||
                        !in_array("designation", $keys) ||
                        !in_array("ecode", $keys) ||
                        !in_array("email", $keys) ||
                        !in_array("alternateemail", $keys) ||
                        !in_array("mobile", $keys)||
                        !in_array("isocountrycode", $keys) ||
                        !in_array("superannuationDate", $keys)
                ) {
                    $result['error'] = true;
                    $result['message'] = "First row of CSV file is not as expected, please make sure that all required fields/columns are included";
                    return new JsonResponse($result);
                }
                $myCon = $em->getConnection();
                $myCon->beginTransaction();
                try {
                    $currentTimeStamp = new \DateTime('now');
                    $uuid = \Ramsey\Uuid\Uuid::uuid4();
                    $guId = $uuid->toString();
                    $batchCode = substr($guId, 0, 8);
                    $batchTime = $currentTimeStamp->format('Y-m-d H:i:s');
                    $qry = "INSERT INTO gim.portal_import_employees_details (ename,gender,designation,ecode,email,alternate_email,mobile,country_code,district_lgdcode,dosa,ou_id,batch_code,batch_time,o_id) values ";
                    $comma = false;
                    //track error
                    $error = false;
                    $html = '<h5 class="mt-5">Please find below the fields with invalid inputs</h5><hr/> <table class="table table-sm table-striped table-bordered table-hover table-condensed">
                <thead>
                    <tr>
                        <th>Row No.</th>
                        <th>ename</th>
                        <th>ecode</th>
                        <th>gender</th>
                        <th>designation</th>
                        <th>email</th>
                        <th>alternateemail</th>
                        <th>mobile</th>
                        <th>isocountrycode</th>
                        <th>superannuationDate</th>
                        <th>district_lgdcode</th>
                    </tr>
                </thead> <tbody>';
                    while (!feof($file)) {
                        $row = fgetcsv($file);
                        if (is_array($row) && count($row) == count($keys)) {
                            $fileRow[$iterator_value] = array_combine($keys, $row);
                            $actualRow = $iterator_value + 2;
                            if (!preg_match("/^[a-z\d\.\s]+$/i", trim($fileRow[$iterator_value]['ename']))) {
                                $error = true;
                                $html .= "<tr><td>" . $actualRow . "</td>
                                          <td class='bg-danger'>" . $fileRow[$iterator_value]['ename'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['ecode'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['gender'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['designation'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['email'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['alternateemail'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['mobile'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['isocountrycode'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['superannuationDate'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['district_lgdcode'] . "</td> </tr>";
                            }
                            if (!preg_match("/^[a-z\d\.\-()&_\/\s]+$/i", trim($fileRow[$iterator_value]['designation']))) {
                                $error = true;
                                $html .= "<tr><td>" . $actualRow . "</td>
                                          <td>" . $fileRow[$iterator_value]['ename'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['ecode'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['gender'] . "</td>
                                          <td class='bg-danger'>" . $fileRow[$iterator_value]['designation'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['email'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['alternateemail'] . "</td>
                                           <td>" . $fileRow[$iterator_value]['mobile'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['isocountrycode'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['superannuationDate'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['district_lgdcode'] . "</td> </tr>";
                            }
                            $avlGender = ['N', 'M', 'm', 'F', 'f', 'Male', 'Female', 'male', 'female'];
                            if (!in_array(trim(str_replace("'", "", trim($fileRow[$iterator_value]['gender']))), $avlGender)) {
                                $error = true;
                                $html .= "<tr><td>" . $actualRow . "</td>
                                          <td>" . $fileRow[$iterator_value]['ename'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['ecode'] . "</td>
                                          <td class='bg-danger'>" . $fileRow[$iterator_value]['gender'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['designation'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['email'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['alternateemail'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['mobile'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['isocountrycode'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['superannuationDate'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['district_lgdcode'] . "</td> </tr>";
                            }
                            if (!preg_match("/^[a-z\d\._\s]+$/i", trim($fileRow[$iterator_value]['ecode']))) {
                                $error = true;
                                $html .= "<tr><td>" . $actualRow . "</td>
                                          <td>" . $fileRow[$iterator_value]['ename'] . "</td>
                                          <td class='bg-danger'>" . $fileRow[$iterator_value]['ecode'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['gender'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['designation'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['email'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['alternateemail'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['mobile'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['isocountrycode'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['superannuationDate'] . "</td> 
                                          <td>" . $fileRow[$iterator_value]['district_lgdcode'] . "</td> </tr>";
                            }
                            if (!preg_match("/^(0)?[0-9]{5,15}$/i", trim($fileRow[$iterator_value]['mobile']))) {
                                // REFERENCE: Please see the mail from Deepak Mittal on 16-11-2021 and subsequent FNA from MPA
                                $error = true;
                                $html .= "<tr><td>" . $actualRow . "</td>
                                          <td>" . $fileRow[$iterator_value]['ename'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['ecode'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['gender'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['designation'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['email'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['alternateemail'] . "</td>
                                          <td class='bg-danger'>" . $fileRow[$iterator_value]['mobile'] . "</td>
                                         <td>" . $fileRow[$iterator_value]['isocountrycode'] . "</td> 
                                         <td>" . $fileRow[$iterator_value]['superannuationDate'] . "</td> 
                                         <td>" . $fileRow[$iterator_value]['district_lgdcode'] . "</td> </tr>";
                            }
                            if (!preg_match('/^[a-zA-Z]{2,5}$/', trim($fileRow[$iterator_value]['isocountrycode']))) {
                                $error = true;
                                $html .= "<tr><td>" . $actualRow . "</td>
                                          <td>" . $fileRow[$iterator_value]['ename'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['ecode'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['gender'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['designation'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['email'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['alternateemail'] . "</td>
                                          <td>" . $fileRow[$iterator_value]['mobile'] . "</td>                                                                                   
                                           <td class='bg-danger'>" . $fileRow[$iterator_value]['isocountrycode'] . "</td>
                                           <td>" . $fileRow[$iterator_value]['superannuationDate'] . "</td> 
                                           <td>" . $fileRow[$iterator_value]['district_lgdcode'] . "</td> </tr>";
                            }

                            if (!$error) {                             
                                
                                $superannuationDate = date('Y-m-d', strtotime(str_replace('/', '-', $fileRow[$iterator_value]['superannuationDate'])));
                                $qry .= ($comma ? ',' : '') . "('" . trim(str_replace("'", "", $fileRow[$iterator_value]['ename'])) . "','" . trim(str_replace("'", "", $fileRow[$iterator_value]['gender'])) . "','" . trim(str_replace("'", "", $fileRow[$iterator_value]['designation'])) . "','" . substr(trim(str_replace("'", "", $fileRow[$iterator_value]['ecode'])), 0, 10) . "','" . strtolower(trim(str_replace("'", "", $fileRow[$iterator_value]['email']))) . "','". strtolower(trim(str_replace("'", "", $fileRow[$iterator_value]['alternateemail']))) ."','" . trim(str_replace("'", "", $fileRow[$iterator_value]['mobile'])) . "','". strtolower(trim(str_replace("'", "", $fileRow[$iterator_value]['isocountrycode']))) ."','". strtolower(trim(str_replace("'", "", $fileRow[$iterator_value]['district_lgdcode'] ?? ''))) ."','". $superannuationDate ."','" . $OrganizationUnit->getId() . "','" . $batchCode . "','" . $batchTime . "','" . $OrganizationUnit->getOrganization()->getId() . "')";
                                $comma = true;
                            }
                            $iterator_value++;
                            if ($iterator_value > 10000) {
                                return new JsonResponse(['error' => true, 'message' => "Sorry, we have some limits on number of import records to avoid bursting, current limit is 10000 records"]);
                            }
                        }
                    }
                    if ($error) {
                        $html .= '  </tbody></table><span class="badge text-danger">*Fix issues marked in red and try again.';
                        return new JsonResponse(['error' => true, 'message' => "There is error in record!!", 'html' => mb_convert_encoding($html, 'UTF-8', 'UTF-8')]);
                    }
                   
                    $importEmployeeObj = new ImportEmployee();
                    $importEmployeeObj->setBatchCode($batchCode);
                    $importEmployeeObj->setUploadDate($currentTimeStamp);
                    $importEmployeeObj->setOrganizationUnit($OrganizationUnit);
                    $importEmployeeObj->setGuId($guId);
                    $importEmployeeObj->setInsertMetaData($this->metadata->getPortalMetadata('I'));
                    $importEmployeeObj->setRecordsCount($iterator_value);
                    $importEmployeeObj->setUser($loggedUser);
                    $em->persist($importEmployeeObj);
                    $preparedQry = $myCon->prepare($qry);
                    $preparedQry->execute();
                    $myCon->commit();
                    $em->flush();
                    fclose($file);
                    return new JsonResponse(['error' => false, 'message' => "Successfully Inserted $iterator_value records!!"]);
                } catch (\Doctrine\DBAL\DBALException $ex) {
                    $myCon->rollBack();
                    $this->logger->error('CSV-IMPORT-ERROR' . $ex->getMessage());

                    return new JsonResponse(['error' => true, 'message' => 'process failed. Please check the CSV dataset for any missing data']);
                }
            } else {
                switch ($fileError) {
                    case UPLOAD_ERR_INI_SIZE:
                        $message = 'Error: The ImportEmployeeed file exceeds the ImportEmployee_max_filesize directive in php.ini';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $message = 'Error: The ImportEmployeeed file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $message = 'Error: The ImportEmployeeed file was only partially ImportEmployeeed. ';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $message = 'Error: No file was ImportEmployeeed.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $message = 'Error: Missing a temporary folder.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $message = 'Error: Failed to write file to disk. ';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $message = 'Error: A PHP extension stopped the file ImportEmployee.';
                        break;
                    default:$message = 'Error: Unknown ImportEmployee error.';
                        break;
                }
                echo json_encode(array(
                    'error' => true,
                    'message' => $message,
                ));
            }
        } else {
            $result['error'] = true;
            $result['message'] = 'Select a file to ImportEmployee';
        }
        return new JsonResponse($result);
    }

    /**
     * @Route("/view", name="portal_import_employee_view")
     */
    public function viewTemp(Request $request, PaginatorInterface $paginator)
    {
        $objid = $request->request->get('objid');
        if (!$objid) {
            $objid = $request->request->get('custom_filter_param');
        }
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $qry = "SELECT ename,gender,designation,ecode,email,alternate_email,mobile,country_code,dosa,state_code, district_lgdcode,is_imported,is_duplicate,remark, state as state_name, district as district_name FROM gim.portal_import_employees_details as i LEFT JOIN gim.masters_districts as d ON d.district_code = i.district_lgdcode LEFT JOIN gim.masters_states as s ON s.id = d.state_id where batch_code=:batchcode order by i.id";
        $preparedQry = $myCon->prepare($qry);
        $preparedQry->bindValue('batchcode', substr($objid, 0, 8));
        $preparedQry->execute();
        $query = $preparedQry->fetchAll();
        $ImportEmployeesPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 100);
        $ImportEmployeesPaginated->setUsedRoute('portal_import_employee_view');
        $ImportEmployee = $em->getRepository("App:Portal\ImportEmployee")->findOneByGuId($objid);
        
        $formView = $this->renderView('portal/importEmployee/_view.html.twig', array('pagination' => $ImportEmployeesPaginated, 'importEmployee' => $ImportEmployee
        ));
        return new JsonResponse(['form' => $formView]);
    }

    /**
     * @Route("/setisProcessed", name="portal_import_employee_set_isprocessed")
     */
    public function setisProcessed(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $myCon = $em->getConnection();
        $myCon->beginTransaction();

        $submittedToken = $request->request->get('token');
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');
        $newToken = $token->getValue();
        try {
            $qryDesignation = "SELECT distinct(designation) FROM gim.portal_import_employees_details where batch_code=:batchcode";
            $preparedQryDesignation = $myCon->prepare($qryDesignation);
            $preparedQryDesignation->bindValue('batchcode', substr($objid, 0, 8));
            $preparedQryDesignation->execute();
            $designationResult = $preparedQryDesignation->fetchAll();
            $qryEmplevel = "SELECT distinct(elevel) FROM gim.portal_import_employees_details where batch_code=:batchcode";
            $preparedQryEmplevel = $myCon->prepare($qryEmplevel);
            $preparedQryEmplevel->bindValue('batchcode', substr($objid, 0, 8));
            $preparedQryEmplevel->execute();
            $empLevelResult = $preparedQryEmplevel->fetchAll();
            $ImportEmployee = $em->getRepository("App:Portal\ImportEmployee")->findOneByGuId($objid);
            $this->insertIntoDesignation($myCon, $em, $designationResult, $ImportEmployee);
            // $this->insertIntoEmpLevel($myCon, $em, $empLevelResult, $ImportEmployee);
            //set designation in temp_employee table
            $updateQry = "UPDATE gim.portal_import_employees_details te SET designation_id = d.id FROM gim.designation d where d.organization_id=:org_id and d.designation_name=te.designation AND te.batch_code=:batchcode";
//            $updateQry = "UPDATE gim.portal_import_employees_details te Inner JOIN ( Select d.id,d.designation_name from gim.designation d where d.organization_id=:org_id ) b ON b.designation_name=te.designation SET  te.designation_id = b.id";
            $preparedUpdateQry = $myCon->prepare($updateQry);
            $preparedUpdateQry->bindValue('org_id', $ImportEmployee->getOrganizationUnit()->getOrganization()->getId());
            $preparedUpdateQry->bindValue('batchcode', substr($objid, 0, 8));
            $preparedUpdateQry->execute();
            $ImportEmployee->setIsProcessed(true);
            $ImportEmployee->setUpdateMetaData($this->metadata->getPortalMetadata('U'));
            $em->flush();
            $myCon->commit();
            return new JsonResponse(['type' => "success", 'message' => 'Set Successfully!', 'token' => $newToken]);
        } catch (\Doctrine\DBAL\DBALException $ex) {
            $myCon->rollBack();
            $this->logger->error('CSV-IMPORT-PROCESS-ERROR' . $ex->getMessage());

            return new JsonResponse(['type' => "danger", 'message' => 'process failed.', 'token' => $newToken]);
        }
    }

    private function insertIntoDesignation($myCon, $em, $result, $ImportEmployee)
    {
        $desgQry = "INSERT INTO gim.designation (designation_name,organization_id,is_published,designation_code,gu_id) values ";
        $comma = false;
        foreach ($result as $value) {
            $designation = $em->getRepository("App:Portal\Designation")->findOneBy(['designationName' => $value['designation'], 'organization' => $ImportEmployee->getOrganizationUnit()->getOrganization()]);
            if (!$designation) {
                //designation escape can be given here
                $uuid = \Ramsey\Uuid\Uuid::uuid4();
                $guid = $uuid->toString();
                $desgQry .= ($comma ? ',' : '') . "('" . $value['designation'] . "','" . $ImportEmployee->getOrganizationUnit()->getOrganization()->getId() . "','" . true . "','" . substr($guid, 0, 8) . "','" . $guid . "')";
                $comma = true;
            }
        }
        if ($comma) {
            $preparedQry = $myCon->prepare($desgQry);
            $preparedQry->execute();
        }
    }

    /**
     * @Route("/schedule", name="portal_import_employee_schedule")
     */
    public function schedule(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');
        $newToken = $token->getValue();
        $myCon = $em->getConnection();
        $myCon->beginTransaction();
        try {
            $ImportEmployee = $em->getRepository("App:Portal\ImportEmployee")->findOneByGuId($objid);
            $ImportEmployee->setIsScheduled(true);
            $ImportEmployee->setUpdateMetaData($this->metadata->getPortalMetadata('U'));
            $em->flush();
            $myCon->commit();
            return new JsonResponse(['type' => "success", 'message' => "Scheduled records Successfully! ", 'token' => $newToken]);
        } catch (\Doctrine\DBAL\DBALException $ex) {
            $this->logger->error('CSV-IMPORT-FINALISE-ERROR' . $ex->getMessage());
            $myCon->rollBack();
            return new JsonResponse(['type' => "danger", 'message' => 'process failed.', 'token' => $newToken]);
        }
    }

    /**
     * @Route("/_delete", name="portal_import_employee_delete")
     */
    public function deleteUpload(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $myCon = $em->getConnection();
        $myCon->beginTransaction();
        try {
            $ImportEmployee = $em->getRepository("App:Portal\ImportEmployee")->findOneByGuId($objid);
            if ($ImportEmployee->getIsScheduled()) {
                return new JsonResponse(['type' => "danger", 'message' => 'Unable to remove this record!']);
            }
            $qry = "Delete FROM gim.portal_import_employees_details where batch_code=:batchcode";
            $preparedQry = $myCon->prepare($qry);
            $preparedQry->bindValue('batchcode', substr($objid, 0, 8));
            $preparedQry->execute();

            $em->remove($ImportEmployee);
            $em->flush();
            $myCon->commit();
            return new JsonResponse(['type' => "success", 'message' => 'Successfully deleted uploaded employees!']);
        } catch (\Doctrine\DBAL\DBALException $ex) {
            $myCon->rollBack();
            $this->logger->error('CSV-IMPORT-DELETE-UPLOAD-ERROR' . $ex->getMessage());

            return new JsonResponse(['type' => "danger", 'message' => 'Unable to remove current record!']);
        }
    }

    /**
     * @Route("/download_schema", name="portal_import_employee_download_schema")
     */
    public function downloadCsvSchema(Request $request)
    {
        $response = new StreamedResponse();
        $response->setCallback(function () {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, array('ename', 'gender', 'designation', 'ecode', 'email', 'alternateemail', 'mobile','isocountrycode','superannuationDate','district_lgdcode'), ',');
            fclose($handle);
        });
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="import_employee_Schema.csv"');

        return $response;
    }
    /**
     * @Route("/download_dlgd", name="portal_import_employee_download_district_lgd")
     */
    public function downloadDistrictLGD(Request $request)
    {
        $response = new StreamedResponse();
        $response->setCallback(function () {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, ['state_lgdcode', 'state_name', 'district_lgdcode', 'district_name'], ',');
            fputcsv($handle, ["35","ANDAMAN AND NICOBAR ISLANDS","603","NICOBARS"], ',');
            fputcsv($handle, ["35","ANDAMAN AND NICOBAR ISLANDS","602","SOUTH ANDAMANS"], ',');
            fputcsv($handle, ["35","ANDAMAN AND NICOBAR ISLANDS","632","NORTH AND MIDDLE ANDAMAN"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","515","SPSR NELLORE"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","517","PRAKASAM"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","511","KURNOOL"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","510","KRISHNA"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","506","GUNTUR"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","505","EAST GODAVARI"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","503","CHITTOOR"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","502","ANANTAPUR"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","504","Y.S.R."], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","521","VIZIANAGARAM"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","523","WEST GODAVARI"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","520","VISAKHAPATANAM"], ',');
            fputcsv($handle, ["28","ANDHRA PRADESH","519","SRIKAKULAM"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","231","EAST KAMENG"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","230","DIBANG VALLEY"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","229","CHANGLANG"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","628","ANJAW"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","243","WEST SIANG"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","241","UPPER SUBANSIRI"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","242","WEST KAMENG"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","238","TAWANG"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","234","LOHIT"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","666","LONGDING"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","235","LOWER DIBANG VALLEY"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","719","LOWER SIANG"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","236","LOWER SUBANSIRI"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","678","NAMSAI"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","237","PAPUM PARE"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","679","SIANG"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","240","UPPER SIANG"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","239","TIRAP"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","233","KURUNG KUMEY"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","677","Kra Daadi"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","718","KAMLE"], ',');
            fputcsv($handle, ["12","ARUNACHAL PRADESH","232","EAST SIANG"], ',');
            fputcsv($handle, ["18","ASSAM","612","CHIRANG"], ',');
            fputcsv($handle, ["18","ASSAM","708","CHARAIDEO"], ',');
            fputcsv($handle, ["18","ASSAM","282","CACHAR"], ',');
            fputcsv($handle, ["18","ASSAM","281","BONGAIGAON"], ',');
            fputcsv($handle, ["18","ASSAM","705","Biswanath"], ',');
            fputcsv($handle, ["18","ASSAM","280","BARPETA"], ',');
            fputcsv($handle, ["18","ASSAM","616","BAKSA"], ',');
            fputcsv($handle, ["18","ASSAM","302","TINSUKIA"], ',');
            fputcsv($handle, ["18","ASSAM","617","UDALGURI"], ',');
            fputcsv($handle, ["18","ASSAM","710","WEST KARBI ANGLONG"], ',');
            fputcsv($handle, ["18","ASSAM","300","SIVASAGAR"], ',');
            fputcsv($handle, ["18","ASSAM","301","SONITPUR"], ',');
            fputcsv($handle, ["18","ASSAM","707","SOUTH SALMARA MANCACHAR"], ',');
            fputcsv($handle, ["18","ASSAM","298","NALBARI"], ',');
            fputcsv($handle, ["18","ASSAM","297","NAGAON"], ',');
            fputcsv($handle, ["18","ASSAM","296","MARIGAON"], ',');
            fputcsv($handle, ["18","ASSAM","706","MAJULI"], ',');
            fputcsv($handle, ["18","ASSAM","295","LAKHIMPUR"], ',');
            fputcsv($handle, ["18","ASSAM","294","KOKRAJHAR"], ',');
            fputcsv($handle, ["18","ASSAM","293","KARIMGANJ"], ',');
            fputcsv($handle, ["18","ASSAM","292","KARBI ANGLONG"], ',');
            fputcsv($handle, ["18","ASSAM","618","KAMRUP METRO"], ',');
            fputcsv($handle, ["18","ASSAM","291","KAMRUP"], ',');
            fputcsv($handle, ["18","ASSAM","290","JORHAT"], ',');
            fputcsv($handle, ["18","ASSAM","709","HOJAI"], ',');
            fputcsv($handle, ["18","ASSAM","289","HAILAKANDI"], ',');
            fputcsv($handle, ["18","ASSAM","288","GOLAGHAT"], ',');
            fputcsv($handle, ["18","ASSAM","287","GOALPARA"], ',');
            fputcsv($handle, ["18","ASSAM","299","DIMA HASAO"], ',');
            fputcsv($handle, ["18","ASSAM","286","DIBRUGARH"], ',');
            fputcsv($handle, ["18","ASSAM","285","DHUBRI"], ',');
            fputcsv($handle, ["18","ASSAM","284","DHEMAJI"], ',');
            fputcsv($handle, ["18","ASSAM","283","DARRANG"], ',');
            fputcsv($handle, ["10","BIHAR","214","PURNIA"], ',');
            fputcsv($handle, ["10","BIHAR","212","PATNA"], ',');
            fputcsv($handle, ["10","BIHAR","211","PASHCHIM CHAMPARAN"], ',');
            fputcsv($handle, ["10","BIHAR","210","NAWADA"], ',');
            fputcsv($handle, ["10","BIHAR","209","NALANDA"], ',');
            fputcsv($handle, ["10","BIHAR","208","MUZAFFARPUR"], ',');
            fputcsv($handle, ["10","BIHAR","207","MUNGER"], ',');
            fputcsv($handle, ["10","BIHAR","206","MADHUBANI"], ',');
            fputcsv($handle, ["10","BIHAR","205","MADHEPURA"], ',');
            fputcsv($handle, ["10","BIHAR","204","LAKHISARAI"], ',');
            fputcsv($handle, ["10","BIHAR","203","KISHANGANJ"], ',');
            fputcsv($handle, ["10","BIHAR","202","KHAGARIA"], ',');
            fputcsv($handle, ["10","BIHAR","201","KATIHAR"], ',');
            fputcsv($handle, ["10","BIHAR","200","KAIMUR (BHABUA)"], ',');
            fputcsv($handle, ["10","BIHAR","199","JEHANABAD"], ',');
            fputcsv($handle, ["10","BIHAR","198","JAMUI"], ',');
            fputcsv($handle, ["10","BIHAR","197","GOPALGANJ"], ',');
            fputcsv($handle, ["10","BIHAR","196","GAYA"], ',');
            fputcsv($handle, ["10","BIHAR","191","BEGUSARAI"], ',');
            fputcsv($handle, ["10","BIHAR","192","BHAGALPUR"], ',');
            fputcsv($handle, ["10","BIHAR","193","BHOJPUR"], ',');
            fputcsv($handle, ["10","BIHAR","188","ARARIA"], ',');
            fputcsv($handle, ["10","BIHAR","194","BUXAR"], ',');
            fputcsv($handle, ["10","BIHAR","195","DARBHANGA"], ',');
            fputcsv($handle, ["10","BIHAR","611","ARWAL"], ',');
            fputcsv($handle, ["10","BIHAR","189","AURANGABAD"], ',');
            fputcsv($handle, ["10","BIHAR","190","BANKA"], ',');
            fputcsv($handle, ["10","BIHAR","220","SHEOHAR"], ',');
            fputcsv($handle, ["10","BIHAR","221","SITAMARHI"], ',');
            fputcsv($handle, ["10","BIHAR","222","SIWAN"], ',');
            fputcsv($handle, ["10","BIHAR","223","SUPAUL"], ',');
            fputcsv($handle, ["10","BIHAR","219","SHEIKHPURA"], ',');
            fputcsv($handle, ["10","BIHAR","224","VAISHALI"], ',');
            fputcsv($handle, ["10","BIHAR","213","PURBI CHAMPARAN"], ',');
            fputcsv($handle, ["10","BIHAR","218","SARAN"], ',');
            fputcsv($handle, ["10","BIHAR","217","SAMASTIPUR"], ',');
            fputcsv($handle, ["10","BIHAR","216","SAHARSA"], ',');
            fputcsv($handle, ["10","BIHAR","215","ROHTAS"], ',');
            fputcsv($handle, ["4","CHANDIGARH","44","CHANDIGARH"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","645","GARIYABAND"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","646","BALOD"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","644","BALODA BAZAR"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","649","BALRAMPUR"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","374","BASTAR"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","650","BEMETARA"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","636","BIJAPUR"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","375","BILASPUR"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","376","DANTEWADA"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","377","DHAMTARI"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","378","DURG"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","379","JANJGIR-CHAMPA"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","380","JASHPUR"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","382","KABIRDHAM"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","381","KANKER"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","643","KONDAGAON"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","383","KORBA"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","384","KOREA"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","385","MAHASAMUND"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","647","MUNGELI"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","637","NARAYANPUR"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","386","RAIGARH"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","387","RAIPUR"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","388","RAJNANDGAON"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","642","SUKMA"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","648","SURAJPUR"], ',');
            fputcsv($handle, ["22","CHHATTISGARH","389","SURGUJA"], ',');
            fputcsv($handle, ["26","DADRA AND NAGAR HAVELI","465","DADRA AND NAGAR HAVELI"], ',');
            fputcsv($handle, ["25","DAMAN AND DIU","463","DAMAN"], ',');
            fputcsv($handle, ["25","DAMAN AND DIU","464","DIU"], ',');
            fputcsv($handle, ["7","DELHI","77","CENTRAL"], ',');
            fputcsv($handle, ["7","DELHI","78","EAST"], ',');
            fputcsv($handle, ["7","DELHI","79","NEW DELHI"], ',');
            fputcsv($handle, ["7","DELHI","80","NORTH"], ',');
            fputcsv($handle, ["7","DELHI","81","NORTH EAST"], ',');
            fputcsv($handle, ["7","DELHI","82","NORTH WEST"], ',');
            fputcsv($handle, ["7","DELHI","671","SHAHDARA"], ',');
            fputcsv($handle, ["7","DELHI","83","SOUTH"], ',');
            fputcsv($handle, ["7","DELHI","670","South East"], ',');
            fputcsv($handle, ["7","DELHI","84","SOUTH WEST"], ',');
            fputcsv($handle, ["7","DELHI","85","WEST"], ',');
            fputcsv($handle, ["30","GOA","551","NORTH GOA"], ',');
            fputcsv($handle, ["30","GOA","552","SOUTH GOA"], ',');
            fputcsv($handle, ["24","GUJARAT","449","KACHCHH"], ',');
            fputcsv($handle, ["24","GUJARAT","462","VALSAD"], ',');
            fputcsv($handle, ["24","GUJARAT","450","KHEDA"], ',');
            fputcsv($handle, ["24","GUJARAT","451","MAHESANA"], ',');
            fputcsv($handle, ["24","GUJARAT","669","Mahisagar"], ',');
            fputcsv($handle, ["24","GUJARAT","673","MORBI"], ',');
            fputcsv($handle, ["24","GUJARAT","452","NARMADA"], ',');
            fputcsv($handle, ["24","GUJARAT","453","NAVSARI"], ',');
            fputcsv($handle, ["24","GUJARAT","454","PANCH MAHALS"], ',');
            fputcsv($handle, ["24","GUJARAT","455","PATAN"], ',');
            fputcsv($handle, ["24","GUJARAT","456","PORBANDAR"], ',');
            fputcsv($handle, ["24","GUJARAT","457","RAJKOT"], ',');
            fputcsv($handle, ["24","GUJARAT","458","SABAR KANTHA"], ',');
            fputcsv($handle, ["24","GUJARAT","459","SURAT"], ',');
            fputcsv($handle, ["24","GUJARAT","460","SURENDRANAGAR"], ',');
            fputcsv($handle, ["24","GUJARAT","641","TAPI"], ',');
            fputcsv($handle, ["24","GUJARAT","461","VADODARA"], ',');
            fputcsv($handle, ["24","GUJARAT","448","JUNAGADH"], ',');
            fputcsv($handle, ["24","GUJARAT","447","JAMNAGAR"], ',');
            fputcsv($handle, ["24","GUJARAT","675","GIR SOMNATH"], ',');
            fputcsv($handle, ["24","GUJARAT","446","GANDHINAGAR"], ',');
            fputcsv($handle, ["24","GUJARAT","445","DOHAD"], ',');
            fputcsv($handle, ["24","GUJARAT","674","DEVBHUMI DWARKA"], ',');
            fputcsv($handle, ["24","GUJARAT","444","DANG"], ',');
            fputcsv($handle, ["24","GUJARAT","668","CHHOTAUDEPUR"], ',');
            fputcsv($handle, ["24","GUJARAT","676","BOTAD"], ',');
            fputcsv($handle, ["24","GUJARAT","443","BHAVNAGAR"], ',');
            fputcsv($handle, ["24","GUJARAT","442","BHARUCH"], ',');
            fputcsv($handle, ["24","GUJARAT","441","BANAS KANTHA"], ',');
            fputcsv($handle, ["24","GUJARAT","672","ARVALLI"], ',');
            fputcsv($handle, ["24","GUJARAT","440","ANAND"], ',');
            fputcsv($handle, ["24","GUJARAT","439","AMRELI"], ',');
            fputcsv($handle, ["24","GUJARAT","438","AHMADABAD"], ',');
            fputcsv($handle, ["6","HARYANA","62","GURUGRAM"], ',');
            fputcsv($handle, ["6","HARYANA","61","FATEHABAD"], ',');
            fputcsv($handle, ["6","HARYANA","60","FARIDABAD"], ',');
            fputcsv($handle, ["6","HARYANA","701","CHARKI DADRI"], ',');
            fputcsv($handle, ["6","HARYANA","59","BHIWANI"], ',');
            fputcsv($handle, ["6","HARYANA","58","AMBALA"], ',');
            fputcsv($handle, ["6","HARYANA","71","PANIPAT"], ',');
            fputcsv($handle, ["6","HARYANA","72","REWARI"], ',');
            fputcsv($handle, ["6","HARYANA","73","ROHTAK"], ',');
            fputcsv($handle, ["6","HARYANA","74","SIRSA"], ',');
            fputcsv($handle, ["6","HARYANA","75","SONIPAT"], ',');
            fputcsv($handle, ["6","HARYANA","76","YAMUNANAGAR"], ',');
            fputcsv($handle, ["6","HARYANA","70","PANCHKULA"], ',');
            fputcsv($handle, ["6","HARYANA","619","PALWAL"], ',');
            fputcsv($handle, ["6","HARYANA","604","MEWAT"], ',');
            fputcsv($handle, ["6","HARYANA","69","MAHENDRAGARH"], ',');
            fputcsv($handle, ["6","HARYANA","68","KURUKSHETRA"], ',');
            fputcsv($handle, ["6","HARYANA","67","KARNAL"], ',');
            fputcsv($handle, ["6","HARYANA","66","KAITHAL"], ',');
            fputcsv($handle, ["6","HARYANA","65","JIND"], ',');
            fputcsv($handle, ["6","HARYANA","64","JHAJJAR"], ',');
            fputcsv($handle, ["6","HARYANA","63","HISAR"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","19","KINNAUR"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","15","BILASPUR"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","16","CHAMBA"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","17","HAMIRPUR"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","26","UNA"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","18","KANGRA"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","20","KULLU"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","21","LAHUL AND SPITI"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","22","MANDI"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","23","SHIMLA"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","24","SIRMAUR"], ',');
            fputcsv($handle, ["2","HIMACHAL PRADESH","25","SOLAN"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","6","KARGIL"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","7","KATHUA"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","620","KISHTWAR"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","622","KULGAM"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","8","KUPWARA"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","9","LEH LADAKH"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","10","POONCH"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","11","PULWAMA"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","12","RAJAURI"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","621","RAMBAN"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","627","REASI"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","624","SAMBA"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","625","SHOPIAN"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","13","SRINAGAR"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","14","UDHAMPUR"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","3","BARAMULLA"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","623","BANDIPORA"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","4","DODA"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","626","GANDERBAL"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","5","JAMMU"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","1","ANANTNAG"], ',');
            fputcsv($handle, ["1","JAMMU AND KASHMIR","2","BADGAM"], ',');
            fputcsv($handle, ["20","JHARKHAND","606","KHUNTI"], ',');
            fputcsv($handle, ["20","JHARKHAND","334","KODERMA"], ',');
            fputcsv($handle, ["20","JHARKHAND","343","WEST SINGHBHUM"], ',');
            fputcsv($handle, ["20","JHARKHAND","322","BOKARO"], ',');
            fputcsv($handle, ["20","JHARKHAND","323","CHATRA"], ',');
            fputcsv($handle, ["20","JHARKHAND","324","DEOGHAR"], ',');
            fputcsv($handle, ["20","JHARKHAND","325","DHANBAD"], ',');
            fputcsv($handle, ["20","JHARKHAND","326","DUMKA"], ',');
            fputcsv($handle, ["20","JHARKHAND","327","EAST SINGHBUM"], ',');
            fputcsv($handle, ["20","JHARKHAND","328","GARHWA"], ',');
            fputcsv($handle, ["20","JHARKHAND","329","GIRIDIH"], ',');
            fputcsv($handle, ["20","JHARKHAND","330","GODDA"], ',');
            fputcsv($handle, ["20","JHARKHAND","331","GUMLA"], ',');
            fputcsv($handle, ["20","JHARKHAND","335","LATEHAR"], ',');
            fputcsv($handle, ["20","JHARKHAND","336","LOHARDAGA"], ',');
            fputcsv($handle, ["20","JHARKHAND","337","PAKUR"], ',');
            fputcsv($handle, ["20","JHARKHAND","338","PALAMU"], ',');
            fputcsv($handle, ["20","JHARKHAND","332","HAZARIBAGH"], ',');
            fputcsv($handle, ["20","JHARKHAND","607","RAMGARH"], ',');
            fputcsv($handle, ["20","JHARKHAND","339","RANCHI"], ',');
            fputcsv($handle, ["20","JHARKHAND","340","SAHEBGANJ"], ',');
            fputcsv($handle, ["20","JHARKHAND","341","SARAIKELA KHARSAWAN"], ',');
            fputcsv($handle, ["20","JHARKHAND","342","SIMDEGA"], ',');
            fputcsv($handle, ["20","JHARKHAND","333","JAMTARA"], ',');
            fputcsv($handle, ["29","KARNATAKA","544","MANDYA"], ',');
            fputcsv($handle, ["29","KARNATAKA","524","BAGALKOT"], ',');
            fputcsv($handle, ["29","KARNATAKA","528","BALLARI"], ',');
            fputcsv($handle, ["29","KARNATAKA","527","BELAGAVI"], ',');
            fputcsv($handle, ["29","KARNATAKA","526","BENGALURU RURAL"], ',');
            fputcsv($handle, ["29","KARNATAKA","525","BENGALURU URBAN"], ',');
            fputcsv($handle, ["29","KARNATAKA","529","BIDAR"], ',');
            fputcsv($handle, ["29","KARNATAKA","531","CHAMARAJANAGAR"], ',');
            fputcsv($handle, ["29","KARNATAKA","630","CHIKBALLAPUR"], ',');
            fputcsv($handle, ["29","KARNATAKA","532","CHIKKAMAGALURU"], ',');
            fputcsv($handle, ["29","KARNATAKA","533","CHITRADURGA"], ',');
            fputcsv($handle, ["29","KARNATAKA","534","DAKSHIN KANNAD"], ',');
            fputcsv($handle, ["29","KARNATAKA","535","DAVANGERE"], ',');
            fputcsv($handle, ["29","KARNATAKA","536","DHARWAD"], ',');
            fputcsv($handle, ["29","KARNATAKA","537","GADAG"], ',');
            fputcsv($handle, ["29","KARNATAKA","539","HASSAN"], ',');
            fputcsv($handle, ["29","KARNATAKA","540","HAVERI"], ',');
            fputcsv($handle, ["29","KARNATAKA","538","KALABURAGI"], ',');
            fputcsv($handle, ["29","KARNATAKA","541","KODAGU"], ',');
            fputcsv($handle, ["29","KARNATAKA","542","KOLAR"], ',');
            fputcsv($handle, ["29","KARNATAKA","543","KOPPAL"], ',');
            fputcsv($handle, ["29","KARNATAKA","545","MYSURU"], ',');
            fputcsv($handle, ["29","KARNATAKA","546","RAICHUR"], ',');
            fputcsv($handle, ["29","KARNATAKA","631","RAMANAGARA"], ',');
            fputcsv($handle, ["29","KARNATAKA","547","SHIVAMOGGA"], ',');
            fputcsv($handle, ["29","KARNATAKA","548","TUMAKURU"], ',');
            fputcsv($handle, ["29","KARNATAKA","549","UDUPI"], ',');
            fputcsv($handle, ["29","KARNATAKA","550","UTTAR KANNAD"], ',');
            fputcsv($handle, ["29","KARNATAKA","530","VIJAYAPURA"], ',');
            fputcsv($handle, ["29","KARNATAKA","635","YADGIR"], ',');
            fputcsv($handle, ["32","KERALA","554","ALAPPUZHA"], ',');
            fputcsv($handle, ["32","KERALA","567","WAYANAD"], ',');
            fputcsv($handle, ["32","KERALA","566","THRISSUR"], ',');
            fputcsv($handle, ["32","KERALA","565","THIRUVANANTHAPURAM"], ',');
            fputcsv($handle, ["32","KERALA","564","PATHANAMTHITTA"], ',');
            fputcsv($handle, ["32","KERALA","563","PALAKKAD"], ',');
            fputcsv($handle, ["32","KERALA","562","MALAPPURAM"], ',');
            fputcsv($handle, ["32","KERALA","561","KOZHIKODE"], ',');
            fputcsv($handle, ["32","KERALA","560","KOTTAYAM"], ',');
            fputcsv($handle, ["32","KERALA","559","KOLLAM"], ',');
            fputcsv($handle, ["32","KERALA","558","KASARAGOD"], ',');
            fputcsv($handle, ["32","KERALA","557","KANNUR"], ',');
            fputcsv($handle, ["32","KERALA","556","IDUKKI"], ',');
            fputcsv($handle, ["32","KERALA","555","ERNAKULAM"], ',');
            fputcsv($handle, ["31","LAKSHADWEEP","553","LAKSHADWEEP DISTRICT"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","420","PANNA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","421","RAISEN"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","422","RAJGARH"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","423","RATLAM"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","424","REWA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","425","SAGAR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","426","SATNA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","427","SEHORE"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","428","SEONI"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","429","SHAHDOL"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","430","SHAJAPUR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","431","SHEOPUR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","432","SHIVPURI"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","433","SIDHI"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","638","SINGRAULI"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","434","TIKAMGARH"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","435","UJJAIN"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","436","UMARIA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","437","VIDISHA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","404","DINDORI"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","403","DHAR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","402","DEWAS"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","401","DATIA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","400","DAMOH"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","399","CHHINDWARA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","398","CHHATARPUR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","405","EAST NIMAR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","396","BHOPAL"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","395","BHIND"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","394","BETUL"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","393","BARWANI"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","392","BALAGHAT"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","391","ASHOKNAGAR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","390","ANUPPUR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","639","ALIRAJPUR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","667","AGAR MALWA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","397","BURHANPUR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","406","GUNA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","407","GWALIOR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","408","HARDA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","409","HOSHANGABAD"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","410","INDORE"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","411","JABALPUR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","412","JHABUA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","413","KATNI"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","414","KHARGONE"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","415","MANDLA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","416","MANDSAUR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","417","MORENA"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","418","NARSINGHPUR"], ',');
            fputcsv($handle, ["23","MADHYA PRADESH","419","NEEMUCH"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","494","SATARA"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","466","AHMEDNAGAR"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","467","AKOLA"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","468","AMRAVATI"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","469","AURANGABAD"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","470","BEED"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","471","BHANDARA"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","472","BULDHANA"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","473","CHANDRAPUR"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","474","DHULE"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","475","GADCHIROLI"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","476","GONDIA"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","477","HINGOLI"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","478","JALGAON"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","479","JALNA"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","480","KOLHAPUR"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","481","LATUR"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","482","MUMBAI"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","483","MUMBAI SUBURBAN"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","484","NAGPUR"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","485","NANDED"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","486","NANDURBAR"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","487","NASHIK"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","488","OSMANABAD"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","665","PALGHAR"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","489","PARBHANI"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","490","PUNE"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","491","RAIGAD"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","492","RATNAGIRI"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","493","SANGLI"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","495","SINDHUDURG"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","496","SOLAPUR"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","497","THANE"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","498","WARDHA"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","499","WASHIM"], ',');
            fputcsv($handle, ["27","MAHARASHTRA","500","YAVATMAL"], ',');
            fputcsv($handle, ["14","MANIPUR","256","IMPHAL WEST"], ',');
            fputcsv($handle, ["14","MANIPUR","253","CHANDEL"], ',');
            fputcsv($handle, ["14","MANIPUR","713","JIRIBAM"], ',');
            fputcsv($handle, ["14","MANIPUR","711","KAKCHING"], ',');
            fputcsv($handle, ["14","MANIPUR","717","KAMJONG"], ',');
            fputcsv($handle, ["14","MANIPUR","712","KANGPOKPI"], ',');
            fputcsv($handle, ["14","MANIPUR","715","PHERZAWL"], ',');
            fputcsv($handle, ["14","MANIPUR","257","SENAPATI"], ',');
            fputcsv($handle, ["14","MANIPUR","258","TAMENGLONG"], ',');
            fputcsv($handle, ["14","MANIPUR","714","NONEY"], ',');
            fputcsv($handle, ["14","MANIPUR","252","BISHNUPUR"], ',');
            fputcsv($handle, ["14","MANIPUR","716","TENGNOUPAL"], ',');
            fputcsv($handle, ["14","MANIPUR","259","THOUBAL"], ',');
            fputcsv($handle, ["14","MANIPUR","260","UKHRUL"], ',');
            fputcsv($handle, ["14","MANIPUR","254","CHURACHANDPUR"], ',');
            fputcsv($handle, ["14","MANIPUR","255","IMPHAL EAST"], ',');
            fputcsv($handle, ["17","MEGHALAYA","656","NORTH GARO HILLS"], ',');
            fputcsv($handle, ["17","MEGHALAYA","273","EAST GARO HILLS"], ',');
            fputcsv($handle, ["17","MEGHALAYA","657","EAST JAINTIA HILLS"], ',');
            fputcsv($handle, ["17","MEGHALAYA","274","EAST KHASI HILLS"], ',');
            fputcsv($handle, ["17","MEGHALAYA","276","RI BHOI"], ',');
            fputcsv($handle, ["17","MEGHALAYA","277","SOUTH GARO HILLS"], ',');
            fputcsv($handle, ["17","MEGHALAYA","663","SOUTH WEST GARO HILLS"], ',');
            fputcsv($handle, ["17","MEGHALAYA","658","SOUTH WEST KHASI HILLS"], ',');
            fputcsv($handle, ["17","MEGHALAYA","278","WEST GARO HILLS"], ',');
            fputcsv($handle, ["17","MEGHALAYA","275","WEST JAINTIA HILLS"], ',');
            fputcsv($handle, ["17","MEGHALAYA","279","WEST KHASI HILLS"], ',');
            fputcsv($handle, ["15","MIZORAM","263","KOLASIB"], ',');
            fputcsv($handle, ["15","MIZORAM","264","LAWNGTLAI"], ',');
            fputcsv($handle, ["15","MIZORAM","265","LUNGLEI"], ',');
            fputcsv($handle, ["15","MIZORAM","266","MAMIT"], ',');
            fputcsv($handle, ["15","MIZORAM","262","CHAMPHAI"], ',');
            fputcsv($handle, ["15","MIZORAM","261","AIZAWL"], ',');
            fputcsv($handle, ["15","MIZORAM","267","SAIHA"], ',');
            fputcsv($handle, ["15","MIZORAM","268","SERCHHIP"], ',');
            fputcsv($handle, ["13","NAGALAND","251","ZUNHEBOTO"], ',');
            fputcsv($handle, ["13","NAGALAND","244","DIMAPUR"], ',');
            fputcsv($handle, ["13","NAGALAND","614","KIPHIRE"], ',');
            fputcsv($handle, ["13","NAGALAND","245","KOHIMA"], ',');
            fputcsv($handle, ["13","NAGALAND","615","LONGLENG"], ',');
            fputcsv($handle, ["13","NAGALAND","246","MOKOKCHUNG"], ',');
            fputcsv($handle, ["13","NAGALAND","247","MON"], ',');
            fputcsv($handle, ["13","NAGALAND","613","PEREN"], ',');
            fputcsv($handle, ["13","NAGALAND","248","PHEK"], ',');
            fputcsv($handle, ["13","NAGALAND","249","TUENSANG"], ',');
            fputcsv($handle, ["13","NAGALAND","250","WOKHA"], ',');
            fputcsv($handle, ["21","ODISHA","364","MALKANGIRI"], ',');
            fputcsv($handle, ["21","ODISHA","352","DHENKANAL"], ',');
            fputcsv($handle, ["21","ODISHA","351","DEOGARH"], ',');
            fputcsv($handle, ["21","ODISHA","350","CUTTACK"], ',');
            fputcsv($handle, ["21","ODISHA","349","BOUDH"], ',');
            fputcsv($handle, ["21","ODISHA","348","BHADRAK"], ',');
            fputcsv($handle, ["21","ODISHA","347","BARGARH"], ',');
            fputcsv($handle, ["21","ODISHA","346","BALESHWAR"], ',');
            fputcsv($handle, ["21","ODISHA","345","BALANGIR"], ',');
            fputcsv($handle, ["21","ODISHA","344","ANUGUL"], ',');
            fputcsv($handle, ["21","ODISHA","365","MAYURBHANJ"], ',');
            fputcsv($handle, ["21","ODISHA","362","KHORDHA"], ',');
            fputcsv($handle, ["21","ODISHA","363","KORAPUT"], ',');
            fputcsv($handle, ["21","ODISHA","361","KENDUJHAR"], ',');
            fputcsv($handle, ["21","ODISHA","360","KENDRAPARA"], ',');
            fputcsv($handle, ["21","ODISHA","359","KANDHAMAL"], ',');
            fputcsv($handle, ["21","ODISHA","358","KALAHANDI"], ',');
            fputcsv($handle, ["21","ODISHA","357","JHARSUGUDA"], ',');
            fputcsv($handle, ["21","ODISHA","356","JAJAPUR"], ',');
            fputcsv($handle, ["21","ODISHA","355","JAGATSINGHAPUR"], ',');
            fputcsv($handle, ["21","ODISHA","354","GANJAM"], ',');
            fputcsv($handle, ["21","ODISHA","366","NABARANGPUR"], ',');
            fputcsv($handle, ["21","ODISHA","369","PURI"], ',');
            fputcsv($handle, ["21","ODISHA","368","NUAPADA"], ',');
            fputcsv($handle, ["21","ODISHA","367","NAYAGARH"], ',');
            fputcsv($handle, ["21","ODISHA","353","GAJAPATI"], ',');
            fputcsv($handle, ["21","ODISHA","373","SUNDARGARH"], ',');
            fputcsv($handle, ["21","ODISHA","372","SONEPUR"], ',');
            fputcsv($handle, ["21","ODISHA","371","SAMBALPUR"], ',');
            fputcsv($handle, ["21","ODISHA","370","RAYAGADA"], ',');
            fputcsv($handle, ["34","PUDUCHERRY","601","YANAM"], ',');
            fputcsv($handle, ["34","PUDUCHERRY","600","PONDICHERRY"], ',');
            fputcsv($handle, ["34","PUDUCHERRY","599","MAHE"], ',');
            fputcsv($handle, ["34","PUDUCHERRY","598","KARAIKAL"], ',');
            fputcsv($handle, ["3","PUNJAB","662","PATHANKOT"], ',');
            fputcsv($handle, ["3","PUNJAB","27","AMRITSAR"], ',');
            fputcsv($handle, ["3","PUNJAB","605","BARNALA"], ',');
            fputcsv($handle, ["3","PUNJAB","28","BATHINDA"], ',');
            fputcsv($handle, ["3","PUNJAB","29","FARIDKOT"], ',');
            fputcsv($handle, ["3","PUNJAB","30","FATEHGARH SAHIB"], ',');
            fputcsv($handle, ["3","PUNJAB","651","FAZILKA"], ',');
            fputcsv($handle, ["3","PUNJAB","31","FIROZEPUR"], ',');
            fputcsv($handle, ["3","PUNJAB","32","GURDASPUR"], ',');
            fputcsv($handle, ["3","PUNJAB","33","HOSHIARPUR"], ',');
            fputcsv($handle, ["3","PUNJAB","34","JALANDHAR"], ',');
            fputcsv($handle, ["3","PUNJAB","35","KAPURTHALA"], ',');
            fputcsv($handle, ["3","PUNJAB","36","LUDHIANA"], ',');
            fputcsv($handle, ["3","PUNJAB","37","MANSA"], ',');
            fputcsv($handle, ["3","PUNJAB","38","MOGA"], ',');
            fputcsv($handle, ["3","PUNJAB","41","PATIALA"], ',');
            fputcsv($handle, ["3","PUNJAB","42","RUPNAGAR"], ',');
            fputcsv($handle, ["3","PUNJAB","43","SANGRUR"], ',');
            fputcsv($handle, ["3","PUNJAB","608","S.A.S Nagar"], ',');
            fputcsv($handle, ["3","PUNJAB","40","Shahid Bhagat Singh Nagar"], ',');
            fputcsv($handle, ["3","PUNJAB","39","SRI MUKTSAR SAHIB"], ',');
            fputcsv($handle, ["3","PUNJAB","609","Tarn Taran"], ',');
            fputcsv($handle, ["8","RAJASTHAN","117","UDAIPUR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","115","SIROHI"], ',');
            fputcsv($handle, ["8","RAJASTHAN","114","SIKAR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","113","SAWAI MADHOPUR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","112","RAJSAMAND"], ',');
            fputcsv($handle, ["8","RAJASTHAN","629","PRATAPGARH"], ',');
            fputcsv($handle, ["8","RAJASTHAN","111","PALI"], ',');
            fputcsv($handle, ["8","RAJASTHAN","110","NAGAUR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","109","KOTA"], ',');
            fputcsv($handle, ["8","RAJASTHAN","108","KARAULI"], ',');
            fputcsv($handle, ["8","RAJASTHAN","107","JODHPUR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","106","JHUNJHUNU"], ',');
            fputcsv($handle, ["8","RAJASTHAN","105","JHALAWAR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","104","JALORE"], ',');
            fputcsv($handle, ["8","RAJASTHAN","103","JAISALMER"], ',');
            fputcsv($handle, ["8","RAJASTHAN","102","JAIPUR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","101","HANUMANGARH"], ',');
            fputcsv($handle, ["8","RAJASTHAN","100","GANGANAGAR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","99","DUNGARPUR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","98","DHOLPUR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","97","DAUSA"], ',');
            fputcsv($handle, ["8","RAJASTHAN","96","CHURU"], ',');
            fputcsv($handle, ["8","RAJASTHAN","95","CHITTORGARH"], ',');
            fputcsv($handle, ["8","RAJASTHAN","94","BUNDI"], ',');
            fputcsv($handle, ["8","RAJASTHAN","93","BIKANER"], ',');
            fputcsv($handle, ["8","RAJASTHAN","92","BHILWARA"], ',');
            fputcsv($handle, ["8","RAJASTHAN","91","BHARATPUR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","90","BARMER"], ',');
            fputcsv($handle, ["8","RAJASTHAN","89","BARAN"], ',');
            fputcsv($handle, ["8","RAJASTHAN","88","BANSWARA"], ',');
            fputcsv($handle, ["8","RAJASTHAN","87","ALWAR"], ',');
            fputcsv($handle, ["8","RAJASTHAN","86","AJMER"], ',');
            fputcsv($handle, ["8","RAJASTHAN","116","TONK"], ',');
            fputcsv($handle, ["11","SIKKIM","228","WEST DISTRICT"], ',');
            fputcsv($handle, ["11","SIKKIM","225","EAST DISTRICT"], ',');
            fputcsv($handle, ["11","SIKKIM","226","NORTH DISTRICT"], ',');
            fputcsv($handle, ["11","SIKKIM","227","SOUTH DISTRICT"], ',');
            fputcsv($handle, ["33","TAMIL NADU","596","VILLUPURAM"], ',');
            fputcsv($handle, ["33","TAMIL NADU","610","Ariyalur"], ',');
            fputcsv($handle, ["33","TAMIL NADU","568","CHENNAI"], ',');
            fputcsv($handle, ["33","TAMIL NADU","569","COIMBATORE"], ',');
            fputcsv($handle, ["33","TAMIL NADU","570","CUDDALORE"], ',');
            fputcsv($handle, ["33","TAMIL NADU","571","DHARMAPURI"], ',');
            fputcsv($handle, ["33","TAMIL NADU","572","DINDIGUL"], ',');
            fputcsv($handle, ["33","TAMIL NADU","573","ERODE"], ',');
            fputcsv($handle, ["33","TAMIL NADU","574","KANCHIPURAM"], ',');
            fputcsv($handle, ["33","TAMIL NADU","575","KANNIYAKUMARI"], ',');
            fputcsv($handle, ["33","TAMIL NADU","576","KARUR"], ',');
            fputcsv($handle, ["33","TAMIL NADU","577","KRISHNAGIRI"], ',');
            fputcsv($handle, ["33","TAMIL NADU","578","MADURAI"], ',');
            fputcsv($handle, ["33","TAMIL NADU","579","NAGAPATTINAM"], ',');
            fputcsv($handle, ["33","TAMIL NADU","580","NAMAKKAL"], ',');
            fputcsv($handle, ["33","TAMIL NADU","581","PERAMBALUR"], ',');
            fputcsv($handle, ["33","TAMIL NADU","582","PUDUKKOTTAI"], ',');
            fputcsv($handle, ["33","TAMIL NADU","583","RAMANATHAPURAM"], ',');
            fputcsv($handle, ["33","TAMIL NADU","584","SALEM"], ',');
            fputcsv($handle, ["33","TAMIL NADU","585","SIVAGANGA"], ',');
            fputcsv($handle, ["33","TAMIL NADU","586","THANJAVUR"], ',');
            fputcsv($handle, ["33","TAMIL NADU","588","THENI"], ',');
            fputcsv($handle, ["33","TAMIL NADU","587","THE NILGIRIS"], ',');
            fputcsv($handle, ["33","TAMIL NADU","589","THIRUVALLUR"], ',');
            fputcsv($handle, ["33","TAMIL NADU","590","THIRUVARUR"], ',');
            fputcsv($handle, ["33","TAMIL NADU","591","TIRUCHIRAPPALLI"], ',');
            fputcsv($handle, ["33","TAMIL NADU","592","TIRUNELVELI"], ',');
            fputcsv($handle, ["33","TAMIL NADU","634","TIRUPPUR"], ',');
            fputcsv($handle, ["33","TAMIL NADU","593","TIRUVANNAMALAI"], ',');
            fputcsv($handle, ["33","TAMIL NADU","594","TUTICORIN"], ',');
            fputcsv($handle, ["33","TAMIL NADU","595","VELLORE"], ',');
            fputcsv($handle, ["33","TAMIL NADU","597","VIRUDHUNAGAR"], ',');
            fputcsv($handle, ["36","TELANGANA","518","RANGA REDDY"], ',');
            fputcsv($handle, ["36","TELANGANA","501","ADILABAD"], ',');
            fputcsv($handle, ["36","TELANGANA","690","BHADRADRI KOTHAGUDEM"], ',');
            fputcsv($handle, ["36","TELANGANA","507","HYDERABAD"], ',');
            fputcsv($handle, ["36","TELANGANA","681","Jagitial"], ',');
            fputcsv($handle, ["36","TELANGANA","689","JANGOAN"], ',');
            fputcsv($handle, ["36","TELANGANA","687","JAYASHANKAR BHUPALAPALLY"], ',');
            fputcsv($handle, ["36","TELANGANA","695","JOGULAMBA GADWAL"], ',');
            fputcsv($handle, ["36","TELANGANA","685","KAMAREDDY"], ',');
            fputcsv($handle, ["36","TELANGANA","508","KARIMNAGAR"], ',');
            fputcsv($handle, ["36","TELANGANA","509","KHAMMAM"], ',');
            fputcsv($handle, ["36","TELANGANA","699","KUMURAM BHEEM ASIFABAD"], ',');
            fputcsv($handle, ["36","TELANGANA","688","MAHABUBABAD"], ',');
            fputcsv($handle, ["36","TELANGANA","512","MAHABUBNAGAR"], ',');
            fputcsv($handle, ["36","TELANGANA","684","MANCHERIAL"], ',');
            fputcsv($handle, ["36","TELANGANA","513","MEDAK"], ',');
            fputcsv($handle, ["36","TELANGANA","700","MEDCHAL MALKAJGIRI"], ',');
            fputcsv($handle, ["36","TELANGANA","694","NAGARKURNOOL"], ',');
            fputcsv($handle, ["36","TELANGANA","514","NALGONDA"], ',');
            fputcsv($handle, ["36","TELANGANA","680","Nirmal"], ',');
            fputcsv($handle, ["36","TELANGANA","516","NIZAMABAD"], ',');
            fputcsv($handle, ["36","TELANGANA","682","PEDDAPALLI"], ',');
            fputcsv($handle, ["36","TELANGANA","683","RAJANNA SIRCILLA"], ',');
            fputcsv($handle, ["36","TELANGANA","691","SANGAREDDY"], ',');
            fputcsv($handle, ["36","TELANGANA","692","SIDDIPET"], ',');
            fputcsv($handle, ["36","TELANGANA","696","SURYAPET"], ',');
            fputcsv($handle, ["36","TELANGANA","698","VIKARABAD"], ',');
            fputcsv($handle, ["36","TELANGANA","693","WANAPARTHY"], ',');
            fputcsv($handle, ["36","TELANGANA","522","WARANGAL RURAL"], ',');
            fputcsv($handle, ["36","TELANGANA","686","WARANGAL URBAN"], ',');
            fputcsv($handle, ["36","TELANGANA","697","YADADRI BHUVANAGIRI"], ',');
            fputcsv($handle, ["16","TRIPURA","269","Dhalai"], ',');
            fputcsv($handle, ["16","TRIPURA","654","Gomati"], ',');
            fputcsv($handle, ["16","TRIPURA","652","Khowai"], ',');
            fputcsv($handle, ["16","TRIPURA","270","North Tripura"], ',');
            fputcsv($handle, ["16","TRIPURA","653","Sepahijala"], ',');
            fputcsv($handle, ["16","TRIPURA","271","South Tripura"], ',');
            fputcsv($handle, ["16","TRIPURA","655","Unakoti"], ',');
            fputcsv($handle, ["16","TRIPURA","272","West Tripura"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","136","CHITRAKOOT"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","137","DEORIA"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","138","ETAH"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","139","ETAWAH"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","140","FAIZABAD"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","141","FARRUKHABAD"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","142","FATEHPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","143","FIROZABAD"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","144","GAUTAM BUDDHA NAGAR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","145","GHAZIABAD"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","146","GHAZIPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","147","GONDA"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","148","GORAKHPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","128","BANDA"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","127","BALRAMPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","126","BALLIA"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","125","BAHRAICH"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","124","BAGHPAT"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","123","AZAMGARH"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","122","AURAIYA"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","129","BARABANKI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","640","Amethi"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","121","AMBEDKAR NAGAR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","119","ALIGARH"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","118","AGRA"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","154","AMROHA"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","130","BAREILLY"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","131","BASTI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","179","BHADOHI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","132","BIJNOR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","133","BUDAUN"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","134","BULANDSHAHR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","135","CHANDAULI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","153","JHANSI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","155","KANNAUJ"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","156","KANPUR DEHAT"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","157","KANPUR NAGAR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","633","Kasganj"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","158","KAUSHAMBI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","159","KHERI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","160","KUSHI NAGAR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","161","LALITPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","162","LUCKNOW"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","164","MAHARAJGANJ"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","165","MAHOBA"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","166","MAINPURI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","167","MATHURA"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","168","MAU"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","169","MEERUT"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","170","MIRZAPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","171","MORADABAD"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","172","MUZAFFARNAGAR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","173","PILIBHIT"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","174","PRATAPGARH"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","120","PRAYAGRAJ"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","175","RAE BARELI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","176","RAMPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","177","SAHARANPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","659","SAMBHAL"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","178","SANT KABEER NAGAR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","180","SHAHJAHANPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","660","SHAMLI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","181","SHRAVASTI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","182","SIDDHARTH NAGAR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","183","SITAPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","184","SONBHADRA"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","185","SULTANPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","186","UNNAO"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","187","VARANASI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","149","HAMIRPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","661","HAPUR"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","150","HARDOI"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","163","HATHRAS"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","151","JALAUN"], ',');
            fputcsv($handle, ["9","UTTAR PRADESH","152","JAUNPUR"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","49","DEHRADUN"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","48","CHAMPAWAT"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","47","CHAMOLI"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","46","BAGESHWAR"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","45","ALMORA"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","50","HARIDWAR"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","51","NAINITAL"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","52","PAURI GARHWAL"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","53","PITHORAGARH"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","54","RUDRA PRAYAG"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","55","TEHRI GARHWAL"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","56","UDAM SINGH NAGAR"], ',');
            fputcsv($handle, ["5","UTTARAKHAND","57","UTTAR KASHI"], ',');
            fputcsv($handle, ["19","WEST BENGAL","306","PURBA BARDHAMAN"], ',');
            fputcsv($handle, ["19","WEST BENGAL","321","PURULIA"], ',');
            fputcsv($handle, ["19","WEST BENGAL","303","24 PARAGANAS NORTH"], ',');
            fputcsv($handle, ["19","WEST BENGAL","304","24 PARAGANAS SOUTH"], ',');
            fputcsv($handle, ["19","WEST BENGAL","664","Alipurduar"], ',');
            fputcsv($handle, ["19","WEST BENGAL","305","BANKURA"], ',');
            fputcsv($handle, ["19","WEST BENGAL","307","BIRBHUM"], ',');
            fputcsv($handle, ["19","WEST BENGAL","308","COOCHBEHAR"], ',');
            fputcsv($handle, ["19","WEST BENGAL","309","DARJEELING"], ',');
            fputcsv($handle, ["19","WEST BENGAL","318","MEDINIPUR WEST"], ',');
            fputcsv($handle, ["19","WEST BENGAL","319","MURSHIDABAD"], ',');
            fputcsv($handle, ["19","WEST BENGAL","320","NADIA"], ',');
            fputcsv($handle, ["19","WEST BENGAL","310","DINAJPUR DAKSHIN"], ',');
            fputcsv($handle, ["19","WEST BENGAL","311","DINAJPUR UTTAR"], ',');
            fputcsv($handle, ["19","WEST BENGAL","312","HOOGHLY"], ',');
            fputcsv($handle, ["19","WEST BENGAL","313","HOWRAH"], ',');
            fputcsv($handle, ["19","WEST BENGAL","314","JALPAIGURI"], ',');
            fputcsv($handle, ["19","WEST BENGAL","703","Jhargram"], ',');
            fputcsv($handle, ["19","WEST BENGAL","702","KALIMPONG"], ',');
            fputcsv($handle, ["19","WEST BENGAL","315","KOLKATA"], ',');
            fputcsv($handle, ["19","WEST BENGAL","316","MALDAH"], ',');
            fputcsv($handle, ["19","WEST BENGAL","317","MEDINIPUR EAST"], ',');
            fputcsv($handle, ["19","WEST BENGAL","704","PASCHIM BARDHAMAN"], ',');
            fclose($handle);
        });
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="district_lgdirectory.csv"');

        return $response;
    }
}
