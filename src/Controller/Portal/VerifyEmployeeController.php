<?php

namespace App\Controller\Portal;

use App\Entity\Masters\OffBoardReason;
use App\Entity\Portal\APIRequestStatus;
use App\Entity\Portal\Employee;
use App\Entity\Portal\EmployeeApps;
use App\Entity\Portal\EmployeeMessages;
use App\Entity\Portal\EmployeeMigrationStatus;
use App\Entity\Portal\FileDetail;
use App\Entity\Portal\Group;
use App\Entity\Portal\MemberInGroup;
use App\Entity\Portal\OneTimeLink;
use App\Entity\Portal\Organization;
use App\Entity\Portal\OrganizationUnit;
use App\Entity\Portal\Profile;
use App\Entity\Portal\User;
use App\Form\Portal\EmployeeAppsType;
use App\Form\Portal\EmployeeOffBoardType;
use App\Form\Portal\EmployeeTransferType;
use App\Form\Portal\EmployeeType;
use App\Interfaces\AuditableControllerInterface;
use App\Security\Encoder\SecuredLoginPasswordEncoder;
use App\Services\DefaultValue;
use App\Services\EMailer;
use App\Services\ImageProcess;
use App\Services\OneTimeLinker;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;
use App\Services\XMPPGeneral;
use App\Services\GIMS;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;
use Psr\Log\LoggerInterface;

/**
 * @Route("")
 */
class VerifyEmployeeController extends AbstractController implements AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $xmppGeneral;
    private $defaultValue;
    private $imageProcess;
    private $gims;
    private $logger_exceptions;

    private $oneTimeLinker;
    private $emailer;
    private $password_encoder;

    public function __construct(SecuredLoginPasswordEncoder $password_encoder, DefaultValue $defVal, XMPPGeneral $xmpp, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, ImageProcess $imageProcess, OneTimeLinker $oneTimeLinker, EMailer $emailer, GIMS $gims,LoggerInterface $exceptionsLogger)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->xmppGeneral = $xmpp;
        $this->defaultValue = $defVal;
        $this->imageProcess = $imageProcess;

        $this->oneTimeLinker = $oneTimeLinker;
        $this->emailer = $emailer;
        $this->password_encoder = $password_encoder;
        $this->gims = $gims;
        $this->logger_exceptions = $exceptionsLogger;
    }

    /**
     * @Route("/portal/ver/emp/", name="portal_verify_emp_index")
     */
    public function index(Request $request)
    {
        $dfConfig = ([['field_alias' => 'emp_name', 'display_text' => 'Employee Name', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
            ['field_alias' => 'email', 'display_text' => 'Email', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
            ['field_alias' => 'mobile', 'display_text' => 'Mobile', 'operator_type' => ['ILIKE', '='], 'input_type' => 'number', 'input_schema' => ''],
            ['field_alias' => 'mobile_all', 'display_text' => 'Mobile All', 'operator_type' => ['ILIKE', '='], 'input_type' => 'number', 'input_schema' => ''],
            ['field_alias' => 'emp_code', 'display_text' => 'Employee Code', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
            ['field_alias' => 'designation', 'display_text' => 'Designation', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
            ['field_alias' => 'ou_admin', 'display_text' => 'List Ou Admins', 'operator_type' => ['='], 'input_type' => 'boolean', 'input_schema' => ''],
            ['field_alias' => 'ou_maneger', 'display_text' => 'List OU Managers', 'operator_type' => ['='], 'input_type' => 'boolean', 'input_schema' => ''],
            ['field_alias' => 'ou_nodal', 'display_text' => 'List Nodal Officers', 'operator_type' => ['='], 'input_type' => 'boolean', 'input_schema' => ''],
            ['field_alias' => 'registered', 'display_text' => 'Registered Users', 'operator_type' => ['='], 'input_type' => 'boolean', 'input_schema' => ''],
            ['field_alias' => 'reg_date', 'display_text' => 'Registered Date', 'operator_type' => ['='], 'input_type' => 'date', 'input_schema' => ''],
            ['field_alias' => 'state', 'display_text' => 'State', 'operator_type' => ['=', 'ILIKE'], 'input_type' => 'codefinder', 'input_schema' => 'State'],
            ['field_alias' => 'district', 'display_text' => 'District', 'operator_type' => ['=', 'ILIKE'], 'input_type' => 'codefinder', 'input_schema' => 'District'],
            ['field_alias' => 'ou_name', 'display_text' => 'Organization Unit', 'operator_type' => ['=', 'ILIKE'], 'input_type' => 'codefinder', 'input_schema' => 'OrganizationUnit'],
            ['field_alias' => 'account_status', 'display_text' => 'Verification Status', 'operator_type' => ['='], 'input_type' => 'choice', 'input_schema' => '', 'choices' => ['V' => 'Verified', 'U' => 'Not verified']],
        ]);

        return $this->render('portal/verify_employee/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
     * @Route("/portal/ver/emp/list", name="portal_verify_emp_list")
     */
    public function list(Request $request, PaginatorInterface $paginator)
    {
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);

        $query = $this->processQry($dynamicFilters);
        $employeesPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
        $employeesPaginated->setUsedRoute('portal_verify_emp_list');

        return $this->render('portal/verify_employee/_list.html.twig', ['pagination' => $employeesPaginated]);
    }
    /**
     * @Route("/portal/ver/emp/o_rolewiselist/", name="portal_verify_emp_oindex")
     */
    public function oindex(): Response
    {
        return $this->render('portal/verify_employee/oindex.html.twig');
    }

    /**
     * @Route("/portal/ver/emp/o_rolewiselist/olist", name="portal_verify_emp_olist")
     */
    public function olist(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $oU = 0;
        $roleName='ROLE_O_ADMIN';

        $quer = $em->createQueryBuilder('e')
                  ->select('e.id,e.guId,e.accountStatus,e.employeeCode,e.isRegistered,e.registeredDate,e.employeeName,e.emailAddress,e.mobileNumber,e.isRetired,e.isDeceased,e.isOUAdmin,e.isOUManager,d.designationName,u.enabled,u.isBetaUser,ou.OUCode,ou.OUName,obr.markerIcon,obr.offBoardReasonName,dt.district,st.state,r.role')
                  ->from('App:Portal\Employee', 'e')
                  ->leftJoin('App:Portal\Designation', 'd', 'WITH', 'd.id = e.designation')
                  ->leftJoin('App:Portal\User', 'u', 'WITH', 'u.id = e.user')
                  ->leftJoin('App:Portal\Profile', 'p', 'WITH', 'p.user = u.id and p.isEnabled=1')
                  ->leftJoin('App:Portal\OrganizationUnit', 'ou', 'WITH', 'ou.id = p.organizationUnit')
                  ->leftJoin('App:Portal\Roles', 'r', 'WITH', 'p.role = r.id')
                  ->leftJoin('App:Masters\OffBoardReason', 'obr', 'WITH', 'obr.id = e.user')
                  ->leftJoin('App:Masters\District', 'dt', 'WITH', 'dt.id = e.district')
                  ->leftJoin('App:Masters\State', 'st', 'WITH', 'st.id = e.state')
                  ->where("r.role = 'ROLE_O_ADMIN'");
        
        $qryListResult = $em->createQuery($quer);
        $employeesPaginated = $paginator->paginate($qryListResult, $request->query->getInt('page', 1), 20);
        $employeesPaginated->setUsedRoute('portal_verify_emp_olist');

        return $this->render('portal/verify_employee/_olist.html.twig', ['pagination' => $employeesPaginated]);
    }

    /**
     * @Route("/portal/ver/emp/m_rolewiselist/", name="portal_verify_emp_mindex")
     */
    public function mindex(): Response
    {
        return $this->render('portal/verify_employee/mindex.html.twig');
    }

    /**
     * @Route("/portal/ver/emp/m_rolewiselist/mlist/", name="portal_verify_emp_mlist")
     */
    public function mlist(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        
        $quer = $em->createQueryBuilder('e')
                  ->select('e.id,e.guId,e.accountStatus,e.employeeCode,e.isRegistered,e.registeredDate,e.employeeName,e.emailAddress,e.mobileNumber,e.isRetired,e.isDeceased,e.isOUAdmin,e.isOUManager,d.designationName,u.enabled,u.isBetaUser,ou.OUCode,ou.OUName,obr.markerIcon,obr.offBoardReasonName,dt.district,st.state,r.role')
                  ->from('App:Portal\Employee', 'e')
                  ->leftJoin('App:Portal\Designation', 'd', 'WITH', 'd.id = e.designation')
                  ->leftJoin('App:Portal\User', 'u', 'WITH', 'u.id = e.user')
                  ->leftJoin('App:Portal\Profile', 'p', 'WITH', 'p.user = u.id and p.isEnabled=1')
                  ->leftJoin('App:Portal\OrganizationUnit', 'ou', 'WITH', 'ou.id = p.organizationUnit')
                  ->leftJoin('App:Portal\Roles', 'r', 'WITH', 'p.role = r.id')
                  ->leftJoin('App:Masters\OffBoardReason', 'obr', 'WITH', 'obr.id = e.user')
                  ->leftJoin('App:Masters\District', 'dt', 'WITH', 'dt.id = e.district')
                  ->leftJoin('App:Masters\State', 'st', 'WITH', 'st.id = e.state')
                  ->where("r.role = 'ROLE_MINISTRY_ADMIN'");
        $qryListResult = $em->createQuery($quer);
        $employeesPaginated = $paginator->paginate($qryListResult, $request->query->getInt('page', 1), 20);
        $employeesPaginated->setUsedRoute('portal_verify_emp_mlist');

        return $this->render('portal/verify_employee/_mlist.html.twig', ['pagination' => $employeesPaginated]);
    }

    /**
     * @Route("/portal/ver/emp/ou_rolewiselist/", name="portal_verify_emp_ouindex")
     */
    public function ouindex(): Response
    {
        return $this->render('portal/verify_employee/ouindex.html.twig');
    }

    /**
     * @Route("/portal/ver/emp/ou_rolewiselist/oulist/", name="portal_verify_emp_oulist")
     */
    public function oulist(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        
        $quer = $em->createQueryBuilder('e')
                  ->select('e.id,e.guId,e.accountStatus,e.employeeCode,e.isRegistered,e.registeredDate,e.employeeName,e.emailAddress,e.mobileNumber,e.isRetired,e.isDeceased,e.isOUAdmin,e.isOUManager,d.designationName,u.enabled,u.isBetaUser,ou.OUCode,ou.OUName,obr.markerIcon,obr.offBoardReasonName,dt.district,st.state,r.role')
                  ->from('App:Portal\Employee', 'e')
                  ->leftJoin('App:Portal\Designation', 'd', 'WITH', 'd.id = e.designation')
                  ->leftJoin('App:Portal\User', 'u', 'WITH', 'u.id = e.user')
                  ->leftJoin('App:Portal\Profile', 'p', 'WITH', 'p.user = u.id and p.isEnabled=1')
                  ->leftJoin('App:Portal\OrganizationUnit', 'ou', 'WITH', 'ou.id = p.organizationUnit')
                  ->leftJoin('App:Portal\Roles', 'r', 'WITH', 'p.role = r.id')
                  ->leftJoin('App:Masters\OffBoardReason', 'obr', 'WITH', 'obr.id = e.user')
                  ->leftJoin('App:Masters\District', 'dt', 'WITH', 'dt.id = e.district')
                  ->leftJoin('App:Masters\State', 'st', 'WITH', 'st.id = e.state')
                  ->where("r.role = 'ROLE_OU_ADMIN'");
        $qryListResult = $em->createQuery($quer);
        $employeesPaginated = $paginator->paginate($qryListResult, $request->query->getInt('page', 1), 20);
        $employeesPaginated->setUsedRoute('portal_emp_oulist');

        return $this->render('portal/verify_employee/_oulist.html.twig', ['pagination' => $employeesPaginated]);
    }

    /**
     * @Route("/portal/ver/emp/csv/", name="portal_verify_emp_csv")
     */
    public function csv(Request $request)
    {
        $param = $request->request->get('csvDownload');
        $filters = base64_decode($param['custom_filter_param']);
        $dynamicFilters = json_decode($filters, true);
        $new_password = $param['new_password'];
        $confirm_password = $param['confirm_password'];
        if ($new_password !== $confirm_password) {
            $this->addFlash('danger', 'password mismatch');
        }
        $query = $this->processQry($dynamicFilters);
        $qryListResult = $query->getResult();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $tmp_folder = sys_get_temp_dir().'/';
        $date = date_create();
        $datatimeStamp = date_timestamp_get($date);
        $csv_file_name_only = $uuid->toString().'.csv';
        $zip_file_name_with_path = $tmp_folder.'gims_members_'.$datatimeStamp.$uuid->toString().'.zip';
        $csv_file_name_with_path = $tmp_folder.'gims_members_'.$datatimeStamp.$csv_file_name_only;
        $handle = fopen($csv_file_name_with_path, 'w+');
        // Add the header of the CSV file
        fputcsv($handle, ['State', 'District', 'OU', 'Employee Code', 'Employee Name', 'Designation Name', 'Email address', 'Mobile Number', 'Is Active', 'Registered', 'Registered Date'], ';');
        foreach ($qryListResult as $row) {
            fputcsv(
                $handle,
                [$row['state'], $row['district'], $row['OUName'], $row['employeeCode'], $row['employeeName'], $row['designationName'], $row['emailAddress'], $row['mobileNumber'], ($row['enabled'] ? 'Yes' : 'No'), $row['isRegistered'], $row['registeredDate'] ? $row['registeredDate']->format('d/m/Y') : ''],
                ';'
            );
        }
        fclose($handle);
        $zip = new \ZipArchive();
        if (true === $zip->open($zip_file_name_with_path, ZipArchive::CREATE)) {
            $zip->setPassword('Nic*123');
            $zip->addFile($csv_file_name_with_path, $csv_file_name_only);
            $zip->setEncryptionName($csv_file_name_only, ZipArchive::EM_AES_256, $confirm_password);
            $zip->close();
        }

        return $this->file($zip_file_name_with_path);
    }

    /**
    * @Route("/portal/ver/emp/csvm/", name="portal_verify_emp_csvm")
    */
    public function csvm(Request $request)
    {
        $param = $request->request->get('csvDownload');
        $filters = base64_decode($param['custom_filter_param']);
        $dynamicFilters = json_decode($filters, true);
        $new_password = $param['new_password'];
        $confirm_password = $param['confirm_password'];
        if ($new_password !== $confirm_password) {
            $this->addFlash('danger', 'password mismatch');
        }
        // Added for rolewise download
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();

        $sqlRoleBased = <<<SQLMS
        SELECT  e.id,
                e.gu_id,
                e.employee_code, 
                e.name as employee_name, 
                d.designation_name as designation_name,
                e.email as email_address,
                e.mobile_no as mobile_number,
                u.enabled,
                ou.ou_code as ou_name,
                e.is_retired,
                e.is_deceased,
                e.is_ou_admin,
                e.is_ou_manager,
                obr.marker_icon,
                obr.offboard_reason_name,
                u.is_beta_user
        FROM gim.employee as e 
        LEFT JOIN gim.designation as d ON e.designation_code = d.id
        LEFT JOIN gim.portal_user_profiles as up ON e.user_id = up.user_id AND is_enabled = 1
        LEFT JOIN gim.portal_masters_roles as pmr ON up.role_id = pmr.id  
        LEFT JOIN gim.organization_unit as ou ON up.organization_unit_id = ou.ou_id
        LEFT JOIN gim.portal_users as u ON e.user_id = u.id        
        LEFT JOIN gim.masters_offboard_reasons as obr ON e.offboard_reason_id = obr.id
        LEFT JOIN gim.masters_districts as dt ON dt.id=e.district_id 
        LEFT JOIN gim.masters_states as st ON st.id = e.state_id            
        WHERE   pmr.role = :roleName
        ORDER BY e.id
SQLMS;

        
        $roleName='ROLE_MINISTRY_ADMIN';
        $qryList = $myCon->prepare($sqlRoleBased);
        $qryList->bindValue('roleName', $roleName);
        $qryList->execute();
        $qryListResult = $qryList->fetchAll();

        // Rolewise download Ends
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $tmp_folder = sys_get_temp_dir().'/';
        $date = date_create();
        $datatimeStamp = date_timestamp_get($date);
        $csv_file_name_only = $uuid->toString().'.csv';
        $zip_file_name_with_path = $tmp_folder.'gims_members_'.$datatimeStamp.$uuid->toString().'.zip';
        $csv_file_name_with_path = $tmp_folder.'gims_members_'.$datatimeStamp.$csv_file_name_only;
        $handle = fopen($csv_file_name_with_path, 'w+');
        // Add the header of the CSV file
        //fputcsv($handle, ['State', 'District', 'OU', 'Employee Code', 'Employee Name', 'Designation Name', 'Email address', 'Mobile Number', 'Is Active', 'Registered', 'Registered Date'], ';');
        fputcsv($handle, ['Employee Code', 'Employee Name', 'Designation Name', 'Email address', 'Mobile Number', 'OU Name', 'Is Active'], ';');

        foreach ($qryListResult as $row) {
            fputcsv(
                $handle,
                [$row['employee_code'], $row['employee_name'], $row['designation_name'], $row['email_address'], $row['mobile_number'], $row['ou_name'], ($row['enabled'] ? 'Yes' : 'No')],
                ';'
            );
        }
        fclose($handle);
        $zip = new \ZipArchive();
        if (true === $zip->open($zip_file_name_with_path, ZipArchive::CREATE)) {
            $zip->setPassword('Nic*123');
            $zip->addFile($csv_file_name_with_path, $csv_file_name_only);
            $zip->setEncryptionName($csv_file_name_only, ZipArchive::EM_AES_256, $confirm_password);
            $zip->close();
        }

        return $this->file($zip_file_name_with_path);
    }


    /**
    * @Route("/portal/ver/emp/csvo/", name="portal_verify_emp_csvo")
    */
    public function csvo(Request $request)
    {
        $param = $request->request->get('csvDownload');
        $filters = base64_decode($param['custom_filter_param']);
        $dynamicFilters = json_decode($filters, true);
        $new_password = $param['new_password'];
        $confirm_password = $param['confirm_password'];
        if ($new_password !== $confirm_password) {
            $this->addFlash('danger', 'password mismatch');
        }
        // Added for rolewise download
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();

        $sqlRoleBased = <<<SQLMS
        SELECT  e.id,
                e.gu_id,
                e.employee_code, 
                e.name as employee_name, 
                d.designation_name as designation_name,
                e.email as email_address,
                e.mobile_no as mobile_number,
                u.enabled,
                ou.ou_code as ou_name,
                e.is_retired,
                e.is_deceased,
                e.is_ou_admin,
                e.is_ou_manager,
                obr.marker_icon,
                obr.offboard_reason_name,
                u.is_beta_user
        FROM gim.employee as e 
        LEFT JOIN gim.designation as d ON e.designation_code = d.id
        LEFT JOIN gim.portal_user_profiles as up ON e.user_id = up.user_id AND is_enabled = 1
        LEFT JOIN gim.portal_masters_roles as pmr ON up.role_id = pmr.id  
        LEFT JOIN gim.organization_unit as ou ON up.organization_unit_id = ou.ou_id
        LEFT JOIN gim.portal_users as u ON e.user_id = u.id        
        LEFT JOIN gim.masters_offboard_reasons as obr ON e.offboard_reason_id = obr.id
        LEFT JOIN gim.masters_districts as dt ON dt.id=e.district_id 
        LEFT JOIN gim.masters_states as st ON st.id = e.state_id            
        WHERE   pmr.role = :roleName
        ORDER BY e.id
SQLMS;
        $roleName='ROLE_O_ADMIN';
        $qryList = $myCon->prepare($sqlRoleBased);
        $qryList->bindValue('roleName', $roleName);
        $qryList->execute();
        $qryListResult = $qryList->fetchAll();

        // Rolewise download Ends
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $tmp_folder = sys_get_temp_dir().'/';
        $date = date_create();
        $datatimeStamp = date_timestamp_get($date);
        $csv_file_name_only = $uuid->toString().'.csv';
        $zip_file_name_with_path = $tmp_folder.'gims_members_'.$datatimeStamp.$uuid->toString().'.zip';
        $csv_file_name_with_path = $tmp_folder.'gims_members_'.$datatimeStamp.$csv_file_name_only;
        $handle = fopen($csv_file_name_with_path, 'w+');
        // Add the header of the CSV file
        //fputcsv($handle, ['State', 'District', 'OU', 'Employee Code', 'Employee Name', 'Designation Name', 'Email address', 'Mobile Number', 'Is Active', 'Registered', 'Registered Date'], ';');
        fputcsv($handle, ['Employee Code', 'Employee Name', 'Designation Name', 'Email address', 'Mobile Number', 'OU Name', 'Is Active'], ';');

        foreach ($qryListResult as $row) {
            fputcsv(
                $handle,
                [$row['employee_code'], $row['employee_name'], $row['designation_name'], $row['email_address'], $row['mobile_number'], $row['ou_name'], ($row['enabled'] ? 'Yes' : 'No')],
                ';'
            );
        }
        fclose($handle);
        $zip = new \ZipArchive();
        if (true === $zip->open($zip_file_name_with_path, ZipArchive::CREATE)) {
            $zip->setPassword('Nic*123');
            $zip->addFile($csv_file_name_with_path, $csv_file_name_only);
            $zip->setEncryptionName($csv_file_name_only, ZipArchive::EM_AES_256, $confirm_password);
            $zip->close();
        }

        return $this->file($zip_file_name_with_path);
    }


    /**
    * @Route("/portal/ver/emp/csvou/", name="portal_verify_emp_csvou")
    */
    public function csvou(Request $request)
    {
        $param = $request->request->get('csvDownload');
        $filters = base64_decode($param['custom_filter_param']);
        $dynamicFilters = json_decode($filters, true);
        $new_password = $param['new_password'];
        $confirm_password = $param['confirm_password'];
        if ($new_password !== $confirm_password) {
            $this->addFlash('danger', 'password mismatch');
        }
        // Added for rolewise download
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();

        $sqlRoleBased = <<<SQLMS
        SELECT  e.id,
                e.gu_id,
                e.employee_code, 
                e.name as employee_name, 
                d.designation_name as designation_name,
                e.email as email_address,
                e.mobile_no as mobile_number,
                u.enabled,
                ou.ou_code as ou_name,
                e.is_retired,
                e.is_deceased,
                e.is_ou_admin,
                e.is_ou_manager,
                obr.marker_icon,
                obr.offboard_reason_name,
                u.is_beta_user
        FROM gim.employee as e 
        LEFT JOIN gim.designation as d ON e.designation_code = d.id
        LEFT JOIN gim.portal_user_profiles as up ON e.user_id = up.user_id AND is_enabled = 1
        LEFT JOIN gim.portal_masters_roles as pmr ON up.role_id = pmr.id  
        LEFT JOIN gim.organization_unit as ou ON up.organization_unit_id = ou.ou_id
        LEFT JOIN gim.portal_users as u ON e.user_id = u.id        
        LEFT JOIN gim.masters_offboard_reasons as obr ON e.offboard_reason_id = obr.id
        LEFT JOIN gim.masters_districts as dt ON dt.id=e.district_id 
        LEFT JOIN gim.masters_states as st ON st.id = e.state_id            
        WHERE   pmr.role = :roleName
        ORDER BY e.id
SQLMS;
        $roleName='ROLE_OU_ADMIN';
        $qryList = $myCon->prepare($sqlRoleBased);
        $qryList->bindValue('roleName', $roleName);
        $qryList->execute();
        $qryListResult = $qryList->fetchAll();

        // Rolewise download Ends
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $tmp_folder = sys_get_temp_dir().'/';
        $date = date_create();
        $datatimeStamp = date_timestamp_get($date);
        $csv_file_name_only = $uuid->toString().'.csv';
        $zip_file_name_with_path = $tmp_folder.'gims_members_'.$datatimeStamp.$uuid->toString().'.zip';
        $csv_file_name_with_path = $tmp_folder.'gims_members_'.$datatimeStamp.$csv_file_name_only;
        $handle = fopen($csv_file_name_with_path, 'w+');
        // Add the header of the CSV file
        //fputcsv($handle, ['State', 'District', 'OU', 'Employee Code', 'Employee Name', 'Designation Name', 'Email address', 'Mobile Number', 'Is Active', 'Registered', 'Registered Date'], ';');
        fputcsv($handle, ['Employee Code', 'Employee Name', 'Designation Name', 'Email address', 'Mobile Number', 'OU Name', 'Is Active'], ';');

        foreach ($qryListResult as $row) {
            fputcsv(
                $handle,
                [$row['employee_code'], $row['employee_name'], $row['designation_name'], $row['email_address'], $row['mobile_number'], $row['ou_name'], ($row['enabled'] ? 'Yes' : 'No')],
                ';'
            );
        }
        fclose($handle);
        $zip = new \ZipArchive();
        if (true === $zip->open($zip_file_name_with_path, ZipArchive::CREATE)) {
            $zip->setPassword('Nic*123');
            $zip->addFile($csv_file_name_with_path, $csv_file_name_only);
            $zip->setEncryptionName($csv_file_name_only, ZipArchive::EM_AES_256, $confirm_password);
            $zip->close();
        }

        return $this->file($zip_file_name_with_path);
    }

    private function processQry($dynamicFilters = null)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();

        $fieldAliases = ['emp_name' => 'e.employeeName', 'email' => 'e.emailAddress', 'mobile' => 'e.mobileNumber', 'mobile' => 'e.mobileNumber', 'emp_code' => 'e.employeeCode',
            'designation' => 'd.designationName', 'emp_level' => 'e.emailAddress', 'ou_admin' => 'e.isOUAdmin', 'ou_maneger' => 'e.isOUManager',
            'registered' => 'e.isRegistered', 'reg_date' => 'e.registeredDate', 'ou_nodal' => 'e.isNodalOfficer', 'state' => 'st.state', 'district' => 'dt.district',
            'ou_name' => 'ou.OUName', 'account_status' => 'e.accountStatus',
        ];
        $quer = $em->createQueryBuilder('e')
                ->select('e.id,e.guId,e.accountStatus,e.employeeCode,e.isRegistered,e.registeredDate,e.employeeName,e.emailAddress,e.mobileNumber,e.isRetired,e.isDeceased,e.isOUAdmin,e.isOUManager,d.designationName,u.enabled,u.isBetaUser,ou.OUCode,ou.OUName,obr.markerIcon,obr.offBoardReasonName,dt.district,st.state')
                ->from('App:Portal\Employee', 'e')
                ->innerJoin('App:Portal\Designation', 'd', 'WITH', 'd.id = e.designation')
                ->innerJoin('App:Portal\OrganizationUnit', 'ou', 'WITH', 'ou.id = e.organizationUnit')
                ->leftJoin('App:Portal\User', 'u', 'WITH', 'u.id = e.user')
                ->leftJoin('App:Masters\OffBoardReason', 'obr', 'WITH', 'obr.id = e.user')
                ->leftJoin('App:Masters\District', 'dt', 'WITH', 'dt.id = e.district')
                ->leftJoin('App:Masters\State', 'st', 'WITH', 'st.id = e.state')
        ;

        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = 0;
            $quer->where('e.organizationUnit = :ou OR :ou = 0')
                ->setParameter('ou', $oU);
        } elseif ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $M = $this->profileWorkspace->getMinistry()->getId();
            $quer->leftJoin('App:Portal\Organization', 'o', 'WITH', 'o.id = ou.organization')
                ->leftJoin('App:Masters\Ministry', 'm', 'WITH', 'm.id = o.ministry')
                ->where('m.id = :m OR :m = 0')
                ->setParameter('m', $M);
        } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $O = $this->profileWorkspace->getOrganization()->getId();
            $quer->leftJoin('App:Portal\Organization', 'o', 'WITH', 'o.id = ou.organization')
                ->leftJoin('App:Masters\Ministry', 'm', 'WITH', 'm.id = o.ministry')
                ->where('o.id = :o OR :o = 0')
                ->setParameter('o', $O);
        } else {
            $oU = $this->profileWorkspace->getOu()->getId();
            $quer->where('e.organizationUnit = :ou OR :ou = 0')
                ->setParameter('ou', $oU);
        }
        $quer->andwhere('e.accountStatus = :val')
        ->setParameter('val', 'U');
        // $quer->andwhere('e.onboardingRemarks IS NULL');
        // This line has been commented as we made User's request remarks mandatory
        // Pramod Sir raised issue of Anshu Malik's case orver whatsapp
        // Arun told that it can be remove so removing it
        // $quer->andwhere('e.onboardingRequestRemarks IS NOT NULL');
        
        return $quer->getQuery();
    }

    /**
     * @Route("/portal/ver/emp/view", name="portal_verify_emp_view")
     */
    public function view(Request $request)
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $employee = $em->getRepository(Employee::class)->findOneByGuId($objid);
        $myCon = $em->getConnection();
        $emp_os = '';
        $app_version = '';
        $sqlMS = <<<SQLMS
            SELECT mv.os, mv.app_version
            FROM gim.user_app_device mv
            LEFT JOIN gim.employee e ON e.id = emp_id
            WHERE e.id = :emp;
SQLMS;
        $emp_app_version = $myCon->prepare($sqlMS);
        $emp_app_version->bindValue('emp', $employee->getId());
        $emp_app_version->execute();
        $emp_app_version = $emp_app_version->fetchAll();
        if ([] != $emp_app_version) {
            $emp_os = $emp_app_version[0]['os'];
            $app_version = $emp_app_version[0]['app_version'];
        }
        $em = $this->getDoctrine()->getManager();
        if ($employee->getPhoto()){
            $photo = base64_encode(stream_get_contents($employee->getPhoto()->getFileData()));
        } else {
            $photo = null;
        }
        return $this->render('portal/verify_employee/_view.html.twig', [
                    'employee' => $employee,
                    'photo' => $photo,
                    'emp_os' => $emp_os,
                    'emp_app_version' => $app_version,
        ]);
    }


   
    /**
     * @Route("/portal/ver/emp/verify", name="portal_verify_emp_verify")
     */
    public function verify(Request $request)
    {
        $objid = $request->request->get('objid');
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
        if ($employee->getPhoto()){
            $photo = base64_encode(stream_get_contents($employee->getPhoto()->getFileData()));
        } else {
            $photo = null;
        }
        return $this->render('portal/verify_employee/_lite_verify.html.twig', [
                    'employee' => $employee,
                    'photo' => $photo,
        ]);
    }

    /**
     * @Route("/portal/ver/emp/verifyConfirm", name="portal_verify_emp_verify_confirm")
     */
    public function verifyConfirm(Request $request)
    {
        $objid = $request->request->get('objid');
        $remarks = $request->request->get('remarks');
        $em = $this->getDoctrine()->getManager();
        $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        if (!$employee->getOrganizationUnit()){
            return new Response(json_encode(['status' => 'danger', 'message' => 'Missing organization unit information']));
        }
        if (!$employee->getDesignation()){
            return new Response(json_encode(['status' => 'danger', 'message' => 'Missing designation information']));
        }
        $loggedUser = $this->getUser();
        try {
            $api_return = $this->xmppGeneral->verifyLiteUser($loggedUser->getId(), $employee->getGuId(), $remarks);
            $api_return_status = json_decode($api_return);
            if ('success' === $api_return_status->status) {
                return new Response(json_encode(['status' => 'success', 'message' => 'Verification Successful']));
            } else {
                return new Response(json_encode(['status' => 'danger', 'message' => 'An internal error has been occured ']));
            }
        } catch (\Exception $ex) {
            return new Response(json_encode(['status' => 'danger', 'message' => 'An error has been occured ']));
        }
    }

    /**
     * @Route("/portal/ver/emp/rejectConfirm", name="portal_verify_emp_reject_confirm")
     */
    public function rejectConfirm(Request $request)
    {
        $objid = $request->request->get('objid');
        $remarks = $request->request->get('remarks');
        $em = $this->getDoctrine()->getManager();
        $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        if (!$employee->getOrganizationUnit()){
            return new Response(json_encode(['status' => 'danger', 'message' => 'Missing organization unit information']));
        }
        if (!$employee->getDesignation()){
            return new Response(json_encode(['status' => 'danger', 'message' => 'Missing designation information']));
        }
        $loggedUser = $this->getUser();
        try {
            $api_return = $this->xmppGeneral->rejectLiteUser($loggedUser->getId(), $employee->getGuId(), $remarks);
            $api_return_status = json_decode($api_return);
            if ('success' === $api_return_status->status) {
                return new Response(json_encode(['status' => 'success', 'message' => 'Rejection Successful']));
            } else {
                return new Response(json_encode(['status' => 'danger', 'message' => 'An internal error has been occured ']));
            }
        } catch (\Exception $ex) {
            return new Response(json_encode(['status' => 'danger', 'message' => 'An error has been occured ']));
        }
    }
}
