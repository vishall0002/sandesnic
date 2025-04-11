<?php

namespace App\Controller\Portal;

use App\Entity\Masters\OffBoardReason;
use App\Entity\Portal\APIRequestStatus;
use App\Entity\Portal\Employee;
use App\Entity\Portal\EmployeeApps;
use App\Entity\Portal\EmployeeGroupAdmin;
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
use App\Form\Portal\EmployeeDeleteType;
use App\Form\Portal\EmployeeOffBoardType;
use App\Form\Portal\EmployeeTransferType;
use App\Form\Portal\EmployeeType;
use App\Interfaces\AuditableControllerInterface;
use App\Security\Encoder\SecuredLoginPasswordEncoder;
use App\Services\DefaultValue;
use App\Services\EMailer;
use App\Services\GIMS;
use App\Services\ImageProcess;
use App\Services\OneTimeLinker;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;
use App\Services\XMPPGeneral;
use App\Services\XMPPGroupV5;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

/**
 * @Route("")
 */
class EmployeeController extends AbstractController implements AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $xmppGeneral;
    private $xmppGroupV5;
    private $defaultValue;
    private $imageProcess;
    private $gims;
    private $logger_exceptions;

    private $oneTimeLinker;
    private $emailer;
    private $password_encoder;

    public function __construct(SecuredLoginPasswordEncoder $password_encoder, DefaultValue $defVal, XMPPGeneral $xmpp, XMPPGroupV5 $xmppv5, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, ImageProcess $imageProcess, OneTimeLinker $oneTimeLinker, EMailer $emailer, GIMS $gims, LoggerInterface $exceptionsLogger)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->xmppGeneral = $xmpp;
        $this->xmppGroupV5 = $xmppv5;
        $this->defaultValue = $defVal;
        $this->imageProcess = $imageProcess;

        $this->oneTimeLinker = $oneTimeLinker;
        $this->emailer = $emailer;
        $this->password_encoder = $password_encoder;
        $this->gims = $gims;
        $this->logger_exceptions = $exceptionsLogger;
    }

    /**
     * @Route("/portal/emp/", name="portal_emp_index")
     */
    public function index(Request $request)
    {
        $dfConfig = ([['field_alias' => 'emp_name', 'display_text' => 'Employee Name', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
            ['field_alias' => 'email', 'display_text' => 'Email', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
            ['field_alias' => 'mobile', 'display_text' => 'Mobile', 'operator_type' => ['ILIKE', '='], 'input_type' => 'number', 'input_schema' => ''],
            ['field_alias' => 'emp_code', 'display_text' => 'Employee Code', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
            ['field_alias' => 'designation', 'display_text' => 'Designation', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
            ['field_alias' => 'ou_admin', 'display_text' => 'List Ou Admins', 'operator_type' => ['='], 'input_type' => 'boolean', 'input_schema' => ''],
            ['field_alias' => 'ou_maneger', 'display_text' => 'List OU Managers', 'operator_type' => ['='], 'input_type' => 'boolean', 'input_schema' => ''],
            ['field_alias' => 'ou_nodal', 'display_text' => 'List Nodal Officers', 'operator_type' => ['='], 'input_type' => 'boolean', 'input_schema' => ''],
            ['field_alias' => 'registered', 'display_text' => 'Registered Users', 'operator_type' => ['='],  'input_type' => 'choice', 'input_schema' => '', 'choices' => ['Y' => 'Registered', 'N' => 'Not Registered']],
            ['field_alias' => 'reg_date', 'display_text' => 'Registered Date', 'operator_type' => ['='], 'input_type' => 'date', 'input_schema' => ''],
            ['field_alias' => 'country', 'display_text' => 'Country', 'operator_type' => ['='], 'input_type' => 'codefinder', 'input_schema' => 'SubscriberCountry'],
            ['field_alias' => 'state', 'display_text' => 'State', 'operator_type' => ['=', 'ILIKE'], 'input_type' => 'codefinder', 'input_schema' => 'State'],
            ['field_alias' => 'district', 'display_text' => 'District', 'operator_type' => ['=', 'ILIKE'], 'input_type' => 'codefinder', 'input_schema' => 'District'],
            ['field_alias' => 'ou_id', 'display_text' => 'Organization Unit', 'operator_type' => ['='], 'input_type' => 'codefinder', 'input_schema' => 'OrganizationUnit'],
            ['field_alias' => 'ou_name', 'display_text' => 'Organization Unit like', 'operator_type' => ['ILIKE'], 'input_type' => 'text', 'input_schema' => ''],
            ['field_alias' => 'account_status', 'display_text' => 'Verification Status', 'operator_type' => ['='], 'input_type' => 'choice', 'input_schema' => '', 'choices' => ['V' => 'Verified', 'U' => 'Not verified']],
            ['field_alias' => "mobilenull", 'display_text' => "Missing mobile numbers", 'operator_type' => ['='], 'input_type' => "choice", 'choices' => ['NULL' => 'missing']],
        ]);

        return $this->render('portal/employee/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    private function processQry($dynamicFilters = null)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();

        $fieldAliases = ['emp_name' => 'e.employeeName', 'email' => 'e.emailAddress', 'mobile' => 'e.mobileNumber', 'mobilenull' => "coalesce(e.mobileNumber,'NULL')", 'emp_code' => 'e.employeeCode',
            'designation' => 'd.designationName', 'emp_level' => 'e.emailAddress', 'ou_admin' => 'e.isOUAdmin', 'ou_maneger' => 'e.isOUManager',
            'registered' => 'e.isRegistered', 'reg_date' => 'e.registeredDate', 'ou_nodal' => 'e.isNodalOfficer', 'country' => 'e.country', 'state' => 'e.state', 'district' => 'e.district',
            'ou_id' => 'ou.id', 'ou_name' => 'ou.OUName', 'account_status' => 'e.accountStatus',
        ];
        $quer = $em->createQueryBuilder('e')
                ->select('e.id,e.guId,e.accountStatus,e.employeeCode,e.isRegistered,e.registeredDate,e.employeeName,e.emailAddress,e.mobileNumber,e.isRetired,e.isDeceased,e.isOUAdmin,e.isOUManager,d.designationName,u.enabled,u.isBetaUser,ou.OUCode,ou.OUName,obr.markerIcon,obr.offBoardReasonName,cy.countryName,dt.district,st.state')
                ->from('App:Portal\Employee', 'e')
                ->leftJoin('App:Portal\Designation', 'd', 'WITH', 'd.id = e.designation')
                ->leftJoin('App:Portal\OrganizationUnit', 'ou', 'WITH', 'ou.id = e.organizationUnit')
                ->leftJoin('App:Portal\User', 'u', 'WITH', 'u.id = e.user')
                ->leftJoin('App:Masters\OffBoardReason', 'obr', 'WITH', 'obr.id = e.user')
                ->leftJoin('App:Masters\District', 'dt', 'WITH', 'dt.id = e.district')
                ->leftJoin('App:Masters\State', 'st', 'WITH', 'st.id = e.state')
                ->leftJoin('App:Masters\Country', 'cy', 'WITH', 'cy.id = e.country')
        ;

        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = 0;
            // $quer->where('e.organizationUnit = :ou OR :ou = 0')
            //     ->setParameter('ou', $oU);
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
        if ($dynamicFilters) {
            foreach ($dynamicFilters as $k => $v) {
                if ('ILIKE' === $v['operator']) {
                    $quer->andwhere($v['operator'].'('.$fieldAliases[$k].",:$k )=TRUE");
                    $quer->setParameter($k, '%'.trim($v['fvalue']).'%');
                } else {
                    $quer->andwhere($fieldAliases[$k].' '.$v['operator']." :$k");
                    $quer->setParameter($k, trim($v['fvalue']));
                }
            }
        }

        return $quer->getQuery();
    }
    /**
     * @Route("/portal/emp/search", name="portal_emp_search_index")
     */
    public function searchIndex(Request $request)
    {
        return $this->render('portal/employee/index_search.html.twig');
    }

    /**
     * @Route("/portal/emp/searchlist", name="portal_emp_search_list")
     */
    public function searchList(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $mobileno = $request->request->get('mobileno');
        $query = $em->createQueryBuilder('e')
                ->select('e.id,e.guId,e.accountStatus,e.employeeCode,e.isRegistered,e.registeredDate,e.employeeName,e.emailAddress,e.mobileNumber,e.isRetired,e.isDeceased,e.isOUAdmin,e.isOUManager,d.designationName,u.enabled,u.isBetaUser,ou.OUCode,ou.OUName,obr.markerIcon,obr.offBoardReasonName,dt.district,st.state')
                ->from('App:Portal\Employee', 'e')
                ->leftJoin('App:Portal\Designation', 'd', 'WITH', 'd.id = e.designation')
                ->leftJoin('App:Portal\OrganizationUnit', 'ou', 'WITH', 'ou.id = e.organizationUnit')
                ->leftJoin('App:Portal\User', 'u', 'WITH', 'u.id = e.user')
                ->leftJoin('App:Masters\OffBoardReason', 'obr', 'WITH', 'obr.id = e.user')
                ->leftJoin('App:Masters\District', 'dt', 'WITH', 'dt.id = e.district')
                ->leftJoin('App:Masters\State', 'st', 'WITH', 'st.id = e.state')
                ->where('e.mobileNumber = :mobileno')
                ->andwhere('e.accountStatus = :val')
                ->setParameter('val', 'U')
                ->setParameter('mobileno', $mobileno);

        $employeesPaginated = $paginator->paginate($query->getQuery(), $request->query->getInt('page', 1), 20);
        $employeesPaginated->setUsedRoute('portal_emp_list');

        return $this->render('portal/employee/_list_search.html.twig', ['pagination' => $employeesPaginated]);
    }

    /**
     * @Route("/portal/emp/list", name="portal_emp_list")
     */
    public function list(Request $request, PaginatorInterface $paginator)
    {
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);

        $query = $this->processQry($dynamicFilters);
        $employeesPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
        $employeesPaginated->setUsedRoute('portal_emp_list');

        return $this->render('portal/employee/_list.html.twig', ['pagination' => $employeesPaginated]);
    }

    /**
     * @Route("/portal/emp/o_rolewiselist/", name="portal_emp_oindex")
     */
    public function oindex(): Response
    {
        return $this->render('portal/employee/oindex.html.twig');
    }

    /**
     * @Route("/portal/emp/o_rolewiselist/olist", name="portal_emp_olist")
     */
    public function olist(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $oU = 0;
        $roleName = 'ROLE_O_ADMIN';

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
        $employeesPaginated->setUsedRoute('portal_emp_olist');

        return $this->render('portal/employee/_olist.html.twig', ['pagination' => $employeesPaginated]);
    }

    /**
     * @Route("/portal/emp/m_rolewiselist/", name="portal_emp_mindex")
     */
    public function mindex(): Response
    {
        return $this->render('portal/employee/mindex.html.twig');
    }

    /**
     * @Route("/portal/emp/m_rolewiselist/mlist/", name="portal_emp_mlist")
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
        $employeesPaginated->setUsedRoute('portal_emp_mlist');

        return $this->render('portal/employee/_mlist.html.twig', ['pagination' => $employeesPaginated]);
    }

    /**
     * @Route("/portal/emp/ou_rolewiselist/", name="portal_emp_ouindex")
     */
    public function ouindex(): Response
    {
        return $this->render('portal/employee/ouindex.html.twig');
    }

    /**
     * @Route("/portal/emp/ou_rolewiselist/oulist/", name="portal_emp_oulist")
     */
    public function oulist(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();

        $quer = $em->createQueryBuilder('e')
                  ->select('e.id,e.guId,e.accountStatus,e.employeeCode,e.isRegistered,e.registeredDate,e.employeeName,e.emailAddress,e.mobileNumber,e.isRetired,e.isDeceased,e.isOUAdmin,e.isOUManager,d.designationName,u.enabled,u.isBetaUser,ou.OUCode,ou.OUName,obr.markerIcon,obr.offBoardReasonName,cy.countryName,dt.district,st.state,r.role')
                  ->from('App:Portal\Employee', 'e')
                  ->leftJoin('App:Portal\Designation', 'd', 'WITH', 'd.id = e.designation')
                  ->leftJoin('App:Portal\User', 'u', 'WITH', 'u.id = e.user')
                  ->leftJoin('App:Portal\Profile', 'p', 'WITH', 'p.user = u.id and p.isEnabled=1')
                  ->leftJoin('App:Portal\OrganizationUnit', 'ou', 'WITH', 'ou.id = p.organizationUnit')
                  ->leftJoin('App:Portal\Roles', 'r', 'WITH', 'p.role = r.id')
                  ->leftJoin('App:Masters\OffBoardReason', 'obr', 'WITH', 'obr.id = e.user')
                  ->leftJoin('App:Masters\District', 'dt', 'WITH', 'dt.id = e.district')
                  ->leftJoin('App:Masters\State', 'st', 'WITH', 'st.id = e.state')
                  ->leftJoin('App:Masters\Country', 'cy', 'WITH', 'cy.id = e.country')
                  ->where("r.role = 'ROLE_OU_ADMIN'");
        $qryListResult = $em->createQuery($quer);
        $employeesPaginated = $paginator->paginate($qryListResult, $request->query->getInt('page', 1), 20);
        $employeesPaginated->setUsedRoute('portal_emp_oulist');

        return $this->render('portal/employee/_oulist.html.twig', ['pagination' => $employeesPaginated]);
    }

    /**
     * @Route("/portal/emp/csv/", name="portal_emp_csv")
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
        fputcsv($handle, ['Country', 'State', 'District', 'OU', 'Employee Code', 'Employee Name', 'Designation Name', 'Email address', 'Mobile Number', 'Is Active', 'Registered', 'Registered Date'], ';');
        foreach ($qryListResult as $row) {
            fputcsv(
                $handle,
                [$row['countryName'], $row['state'], $row['district'], $row['OUName'], $row['employeeCode'], $row['employeeName'], $row['designationName'], $row['emailAddress'], $row['mobileNumber'], ($row['enabled'] ? 'Yes' : 'No'), $row['isRegistered'], $row['registeredDate'] ? $row['registeredDate']->format('d/m/Y') : ''],
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
     * @Route("/portal/emp/csvm/", name="portal_emp_csvm")
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

        $roleName = 'ROLE_MINISTRY_ADMIN';
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
     * @Route("/portal/emp/csvo/", name="portal_emp_csvo")
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
        $roleName = 'ROLE_O_ADMIN';
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
     * @Route("/portal/emp/csvou/", name="portal_emp_csvou")
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
        $roleName = 'ROLE_OU_ADMIN';
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
     * @Route("/portal/emp/new",name="portal_emp_new")
     */
    public function new(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $regMode = 'O';
        $employee = new Employee();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $employee->setGuId($uuid->toString());
        $loggedUser = $this->getUser();
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } else {
            $oU = $profile->getOrganizationUnit();
        }

        $form = $this->createForm(EmployeeType::class, $employee, ['profile' => $profile, 'action' => $this->generateUrl('portal_emp_ins'), 'attr' => ['id' => 'frmBaseModal'], 'em' => $em, 'regMode' => $regMode])->add('btnInsert', SubmitType::class, ['label' => 'Save']);

        return $this->render('portal/employee/_form_new.html.twig', [
                    'form' => $form->createView(),
                    'profile' => $profile,
        ]);
    }

    /**
     * @Route("/portal/emp/ins",name="portal_emp_ins")
     */
    public function insert(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $regMode = 'O';
        $employee = new Employee();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $employee->setGuId($uuid->toString());
        $loggedUser = $this->getUser();
        $profile = $this->profileWorkspace->getProfile();

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } else {
            $oU = $this->profileWorkspace->getOu();
        }

        $form = $this->createForm(EmployeeType::class, $employee, ['profile' => $profile, 'action' => $this->generateUrl('portal_emp_ins'), 'attr' => ['id' => 'frmBaseModal'], 'em' => $em, 'regMode' => $regMode])->add('btnInsert', SubmitType::class, ['label' => 'Save']);
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->getConnection()->beginTransaction();

                try {
                    if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                        $oU = $em->getRepository("App:Portal\OrganizationUnit")->findOneById($form['organizationUnit']->getData());
                    } else {
                        $oU = $this->profileWorkspace->getOu();
                    }

                    $newUser = new User();

                    if ('M' === $employee->getGender()->getId()) {
                        // Male Photo
                        $blankPhoto = $this->getDoctrine()->getRepository(FileDetail::class)->findOneById(4);
                    } else {
                        // FeMale Photo
                        $blankPhoto = $this->getDoctrine()->getRepository(FileDetail::class)->findOneById(1);
                    }

                    $newUser->setRoles(['ROLE_MEMBER']);
                    $role = $em->getRepository("App:Portal\Roles")->findOneByRole('ROLE_MEMBER');
                    $parts = explode('@', strtolower($employee->getEmailAddress()));
                    $userName = $parts[0];
                    $userNameDomain = $parts[1];
                    if ('nic.in' === $userNameDomain) {
                        $newUser->setUsername($userName);
                    } else {
                        $newUser->setUsername(strtolower($employee->getEmailAddress()));
                    }
                    $the_salt = password_hash(uniqid(null, true), PASSWORD_BCRYPT);
                    $newUser->setSalt($the_salt);
                    $the_password = $this->password_encoder->encodePassword('nic*123', $the_salt);
                    $newUser->setPassword($the_password);
                    $uuid = \Ramsey\Uuid\Uuid::uuid4();
                    $newUser->setGuid($uuid->toString());
                    $newUser->setIsFcp(true);
                    $newUser->setEnabled(true);
                    $newUser->setFullName($employee->getEmployeeName());
                    $newUser->setEmail(strtolower($employee->getEmailAddress()));

                    $employee->setUser($newUser);

                    $empHostname = $oU->getOrganization()->getVhost();
                    $employee->setPhoto($blankPhoto);
                    $jabberName = 'b0'.substr(str_replace('-', '', $employee->getGuId()), 0, 14);
                    $jabberID = $jabberName.'@'.$empHostname;
                    $employee->setJabberName($jabberName);
                    $employee->setHost($empHostname);
                    $employee->setJabberId($jabberID);

                    $locationCountry = $em->getRepository("App:Masters\Country")->findOneById($form['country']->getData());
                    $countryMobileCode = $locationCountry->getPhoneCode();
                    $countryLocation = $locationCountry->getCountryCode();
                    $employee->setPhoneCode($countryMobileCode);
                    $employee->setLocation($countryLocation);
                    $employee->setCountry($locationCountry);

                    $ouStateDistrict = $em->getRepository("App:Portal\OrganizationUnit")->findOneById($oU);
                    $oUStateCode = $ouStateDistrict->getState();
                    $oUDistrictCode = $ouStateDistrict->getDistrict();
                    // $employee->setState($oUStateCode);
                    // $employee->setDistrict($oUDistrictCode);
                    $employee->setState($form['state']->getData());
                    $employee->setDistrict($form['district']->getData());

                    $employee->setAppType('P');
                    $employee->setRegistrationMode('O');
                    $employee->setAccountStatus('V');
                    $employee->setUserType(6);
                    $employee->setAuthPrivilege($this->defaultValue->getDefaultPrivilege());

                    // $employee->setEmployeeLevel($employee->getEmployeeLevelID()->getLevelNumber());
                    $metada = $this->metadata->getPortalMetadata('I');
                    $employee->setInsertMetaData($metada);

                    $employee->setOrganizationUnit($oU);
                    $uuid = \Ramsey\Uuid\Uuid::uuid4();
                    $employee->setBackupKey(password_hash($uuid->toString(), PASSWORD_BCRYPT));

                    $profile = new Profile();
                    $profile->setUser($employee->getUser());
                    $profile->setGuid($uuid);
                    $profile->setFromDate(new \DateTime('now'));
                    $profile->setIsEnabled(true);
                    $profile->setIsDefault(true);
                    $profile->setIsCurrent(true);
                    $profile->setIsAdditional(false);
                    $profile->setInsertMetaData($metada);
                    $profile->setRole($role);
                    $profile->setOrganizationUnit($oU);
                    $profile->setOrganization($oU->getOrganization());
                    $profile->setMinistry($oU->getOrganization()->getMinistry());

                    $em->persist($newUser);
                    $em->persist($profile);
                    $em->persist($employee);
                    $em->flush();
                    $em->getConnection()->commit();
                    $welcomeMessageContent = $this->renderView('emailer/welcome.html.twig');
                    $this->emailer->sendEmail(strtolower($employee->getEmailAddress()), $this->defaultValue->getDefaultValue('EMAILER-WELCOME-MESSAGE-SUBJECT'), $welcomeMessageContent);
                    $this->oneTimeLinker->createOTL($employee->getUser(), strtolower($employee->getEmailAddress()));
                    $this->xmppGeneral->refreshProfileV5($employee->getGuId());

                    return new Response(json_encode(['status' => 'success', 'message' => 'The member has been added successfully !!']));
                } catch (Exception $ex) {
                    $em->getConnection()->rollback();
                    $this->logger_exceptions->info($ex->getMessage());

                    $formView = $this->renderView('portal/employee/_form_new.html.twig', [
                        'form' => $form->createView(),
                        'organizationUnit' => $oU, ]);

                    return new Response(json_encode(['form' => $formView, 'status' => 'danger', 'message' => 'Unable to save [Check E-Mail and Mobile Number, it must be unique]']));
                }
            } else {
                $formView = $this->renderView('portal/employee/_form_new.html.twig', [
                    'form' => $form->createView(),
                    'profile' => $profile, ]);

                return new Response(json_encode(['form' => $formView, 'status' => 'danger', 'message' => 'Unable to save [Check E-Mail and Mobile Number, it must be unique]']));
            }
        }

        return $this->render('portal/employee/_form_new.html.twig', [
                    'form' => $form->createView(),
                    'profile' => $profile,
        ]);
    }

    /**
     * @Route("/portal/emp/edit", name="portal_emp_edit")
     */
    public function edit(Request $request)
    {
        $objid = $request->request->get('objid');
        $regMode = '';
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $profile = $this->profileWorkspace->getProfile();
        $objid = $request->request->get('objid');
        $employee_registered = "NotRegistered";

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } else {
            $oU = $this->profileWorkspace->getOu();
        }
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
        
        // $this->denyAccessUnlessGranted('edit', $employee);
        
        $is_mobile_edit_allowed = false;
        if ($employee->getEmailAddress() != null and $employee->getMobileNumber() == null){
            $is_mobile_edit_allowed = true;
        }
        if ('O' === $employee->getRegistrationMode()) {
            $regMode = 'O';
        }
        if ('Y' == $employee->getIsRegistered()) {
            $employee_registered = 'IsAlreadyRegistered';
        } 

        $form = $this->createForm(EmployeeType::class, $employee, ['profile' => $profile, 'action' => $this->generateUrl('portal_emp_upd'), 'attr' => ['id' => 'frmBaseModal'], 'em' => $em, 'regMode' => $regMode, 'isRegistered' => $employee_registered, 'is_mobile_edit_allowed' => $is_mobile_edit_allowed])->add('btnUpdate', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);

        return $this->render('portal/employee/_form_edit.html.twig', [
                    'form' => $form->createView(),
                    'profile' => $profile, 'employee' => $employee,
                    'objid' => $objid,
        ]);
    }

    /**
     * @Route("/portal/emp/upd",name="portal_emp_upd")
     */
    public function update(Request $request)
    {
        $objid = $request->request->get('objid');
        $regMode = '';
        $regTypeMode = '';
        $employee_registered = "NotRegistered";
        
        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneByGuId($objid);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
            if (!$employee){
                // may be self registered cases
                $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid]);
            } 
        }

        $is_mobile_edit_allowed = false;
        if ($employee->getEmailAddress() != null and $employee->getMobileNumber() == null){
            $is_mobile_edit_allowed = true;
        }


        if ('O' === $employee->getRegistrationMode()) {
            $regMode = 'O';
        }
        if ('Y' == $employee->getIsRegistered()) {
            $employee_registered = 'IsAlreadyRegistered';
        }
        $existingEmail = $employee->getEmailAddress();
        $existingMobileNumber = $employee->getMobileNumber();

        $form = $this->createForm(EmployeeType::class, $employee, ['profile' => $profile, 'action' => $this->generateUrl('portal_emp_upd'), 'attr' => ['id' => 'frmBaseModal'], 'em' => $em, 'regMode' => $regMode, 'isRegistered' => $employee_registered,'is_mobile_edit_allowed' => $is_mobile_edit_allowed])->add('btnUpdate', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->getConnection()->beginTransaction();
                try {
                    if ($this->isGranted('ROLE_SUPER_ADMIN') || $loggedUser->hasRole('ROLE_MINISTRY_ADMIN') || $loggedUser->hasRole('ROLE_O_ADMIN')) {
                        $oU = $em->getRepository("App:Portal\OrganizationUnit")->findOneById($form['organizationUnit']->getData());
                    } else {
                        $oU = $this->profileWorkspace->getOu();
                    }

                    $employeeUser = $employee->getUser();
                    $existingUserName = $employeeUser->getUserName();

                    if ('O' == $employee->getRegistrationMode()) {
                        if (!('Y' == $employee->getIsRegistered())) {
                            if (true == is_numeric($employeeUser->getUserName())) {
                                $employeeUser->setUsername(strtolower($employee->getMobileNumber()));
                            } else {
                                $employeeUser->setUsername(strtolower($employee->getEmailAddress()));
                            }
                            $employeeUser->setEmail(strtolower($employee->getEmailAddress()));
                            $employee->setMobileNumber($form['mobileNumber']->getData());
                            $employee->setEmailAddress($form['emailAddress']->getData());
                            $employee->setAlternateEmailAddress($form['alternateEmailAddress']->getData());

                            $locationCountry = $em->getRepository("App:Masters\Country")->findOneById($form['country']->getData());
                            if ($locationCountry) {
                                $countryMobileCode = $locationCountry->getPhoneCode();
                                $countryLocation = $locationCountry->getCountryCode();
                                $employee->setPhoneCode($countryMobileCode);
                                $employee->setLocation($countryLocation);
                                $employee->setCountry($locationCountry);
                            }
                        }

                        $currentMobileNumber = $employee->getMobileNumber();
                        $currentEmail = $employee->getEmailAddress();
                    }
                    $employee->setOrganizationUnit($oU);
                    $metada = $this->metadata->getPortalMetadata('U');
                    $employee->setUpdateMetaData($metada);
                    $employee->setUserType(5);
                    $employee->setTfa(true);
                    // $locationCountry = $em->getRepository("App:Masters\Country")->findOneById($form['country']->getData());
                    // $countryMobileCode = $locationCountry->getPhoneCode();
                    // $countryLocation = $locationCountry->getCountryCode();
                    // $employee->setPhoneCode($countryMobileCode);
                    // $employee->setLocation($countryLocation);
                    // $employee->setCountry($locationCountry);

                    $profile->setUpdateMetaData($metada);
                    $profile->setOrganizationUnit($oU);
                    $profile->setOrganization($oU->getOrganization());
                    $profile->setMinistry($oU->getOrganization()->getMinistry());

                    $em->persist($employeeUser);
                    $em->persist($employee);
                    $em->persist($profile);
                    $em->flush();
                    $em->getConnection()->commit();

                    if ('O' == $employee->getRegistrationMode()) {
                        if ($existingUserName == $existingEmail) {
                            $regTypeMode = 'E';
                        } elseif ($existingUserName == $existingMobileNumber) {
                            $regTypeMode = 'M';
                        }
                        $message_mobile = 'Your Mobile Number has been updated. You should immediately logout and login again using the new Mobile Number. Please contact your nodal officer, in case this change is suspicious';
                        $message_email = 'Your E-Mail address has been updated. You should immediately logout and login again using the new E-Mail Address. Please contact your nodal officer, in case this change is suspicious';

                        if ('M' == $regTypeMode) {
                            if ($existingMobileNumber != $currentMobileNumber) {
                                $this->gims->sendMulticast('1', $message_mobile, '"'.$currentMobileNumber.'"');
                            }
                        } elseif ('E' == $regTypeMode) {
                            if ($existingEmail != $currentEmail) {
                                $this->gims->sendMulticast('1', $message_email, '"'.$currentEmail.'"');
                            }
                        }
                    }
                    $this->xmppGeneral->refreshProfileV5($employee->getGuId());

                    return new Response(json_encode(['status' => 'success', 'message' => 'The member details updated successfully !!']));
                } catch (\Exception $ex) {
                    $em->getConnection()->rollback();
                    $this->logger_exceptions->info($ex->getMessage());

                    return new Response(json_encode(['status' => 'danger', 'message' => 'An error has been occured, most likely that it is duplication related']));
                }
            } else {
                $formView = $this->renderView('portal/employee/_form_edit.html.twig', [
                    'form' => $form->createView(),
                    'profile' => $profile,
                    'employee' => $employee,
                    'objid' => $objid, ]);

                return new Response(json_encode(['status' => 'danger', 'form' => $formView, 'message' => 'An error has been occured.']));
            }
        }

        return $this->render('portal/employee/_form_edit.html.twig', [
                    'form' => $form->createView(),
                    'profile' => $profile,
                    'employee' => $employee,
                    'objid' => $objid,
        ]);
    }

    /**
     * @Route("/portal/emp/view", name="portal_emp_view")
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

        return $this->render('portal/employee/_view.html.twig', [
                    'employee' => $employee,
                    'photo' => $photo,
                    'emp_os' => $emp_os,
                    'emp_app_version' => $app_version,
        ]);
    }

    /**
     * @Route("/portal/emp/delete", name="portal_emp_delete")
     */
    public function delete(Request $request)
    {
        $objid = $request->request->get('objid');

        $form = $this->createForm(EmployeeDeleteType::class);

        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneByGuId($objid);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }
        if ($employee->getPhoto()){
            $photo = base64_encode(stream_get_contents($employee->getPhoto()->getFileData()));
        } else {
            $photo = null;
        }
        return $this->render('portal/employee/_delete.html.twig', [
                    'employee' => $employee,
                    'form' => $form->createView(),
                    'photo' => $photo,
        ]);
    }

    /**
     * @Route("/portal/emp/deleteconfirm", name="portal_emp_delete_confirm")
     */
    public function deleteConfirm(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $delete_reason = $request->request->get('delreason');
        if (!$delete_reason) {
            return new Response(json_encode(['status' => 'danger', 'message' => 'Please choose a reason for deleting this account']));
        }

        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneByGuId($objid);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);

        $api_call_status = $this->xmppGeneral->deleteAccount($loggedUser->getId(), $employee->getGuId(), $delete_reason);
        return new Response(json_encode($api_call_status));
    }

    /**
     * @Route("/portal/emp/roles", name="portal_emp_roles")
     */
    public function roles(Request $request)
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();

        $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        if (!('V' === $employee->getAccountStatus() && $employee->getOrganizationUnit())) {
            return $this->render('portal/employee/_role_error.html.twig', ['message' => 'The employee must be verified and attached to an Organization Unit before assigning any Roles/Privileges']);
        }
        $response = $this->getRoleDetails($employee);
        $status = 'success';
        $message = 'Successfull';

        return $this->render('portal/employee/_role_change.html.twig', [
                'status' => $status,
                'message' => $message,
                'employee' => $employee,
                'availableRoles' => $response['availableRoles'],
                'assignedRoles' => $response['assignedRoles'],
                'ministries' => $response['ministries'],
                'organizations' => $response['organizations'],
                'organizationUnits' => $response['organizationUnits'],
                'oU' => $response['oU'],
                ]);
    }

    /**
     * @Route("/portal/emp/roles_ouwise", name="portal_emp_roles_ouwise")
     */
    public function rolesOuwise(Request $request)
    {
        $objid = $request->request->get('objid');
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');
        $selectedOu = $request->request->get('ou');
        $em = $this->getDoctrine()->getManager();
        $selectedOuObj = '';
        if ($selectedOu) {
            $selectedOuObj = $em->getRepository('App:Portal\OrganizationUnit')->find($selectedOu);
        }
        $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        $response = $this->getRoleDetails($employee, $selectedOuObj);
        $status = 'success';
        $message = 'Successfull';

        return $this->render('portal/employee/_role_change.html.twig', [
                    'status' => $status,
                    'message' => $message,
                    'employee' => $employee,
                    'availableRoles' => $response['availableRoles'],
                    'assignedRoles' => $response['assignedRoles'],
                    'ministries' => $response['ministries'],
                    'organizations' => $response['organizations'],
                    'organizationUnits' => $response['organizationUnits'],
                    'oU' => $response['oU'],
        ]);
    }

    private function getRoleDetails($employee, $selectedOu = null)
    {
        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $organizationUnits = null;
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $organizations = null;
            $oU = null;
            $availableRolesObjects = $em->getRepository('App:Portal\Roles')->findByGrantedBy('ROLE_SUPER_ADMIN');
        } elseif ($this->isGranted('ROLE_MINISTRY_ADMIN')) {
            $oU = null;
            $availableRolesObjects = $em->getRepository('App:Portal\Roles')->findByGrantedBy('ROLE_O_ADMIN');
            $organizations = $em->getRepository(Organization::class)->findByMinistry($this->profileWorkspace->getMinistry());
        } elseif ($this->isGranted('ROLE_O_ADMIN')) {
            $oU = null;
            $organizations = null;
            $availableRolesObjects = $em->getRepository('App:Portal\Roles')->findByGrantedBy('ROLE_O_ADMIN');
            $organizationUnits = $em->getRepository(OrganizationUnit::class)->findByOrganization($this->profileWorkspace->getOrganization());
        } else {
            $organizations = null;
            $oU = $this->profileWorkspace->getOu();
            $availableRolesObjects = $em->getRepository('App:Portal\Roles')->findByGrantedBy('ROLE_OU_ADMIN');
        }
        if ($selectedOu) {
            $oU = $selectedOu;
        }
        $availableRoles = [];
        foreach ($availableRolesObjects as $availableRolesObject) {
            array_push($availableRoles, $availableRolesObject->getRole());
        }
        $dql = "SELECT m.id,m.ministryName FROM App:Masters\Ministry m ORDER BY m.id DESC";
        $ministries = $em->createQuery($dql)->getResult();
        $response = [];
        $profiles = $em->getRepository('App:Portal\Profile')->findBy(['user' => $employee->getUser(), 'isEnabled' => true]);

        $assignedRolesDiff = [];
        $assignedRoles = [];
        $i = 0;
        foreach ($profiles as $profile) {
            if ($profile->getRole()) {
                $role = $profile->getRole()->getRole();
                if ($oU == $profile->getOrganizationUnit()) {
                    array_push($assignedRolesDiff, $role);
                }
                $assignedRoles[$i]['role'] = $role;
                $assignedRoles[$i]['profile'] = $profile;
                ++$i;
            }
        }
        $response['availableRoles'] = array_diff($availableRoles, $assignedRolesDiff);
        $response['assignedRoles'] = $assignedRoles;
        $response['oU'] = $oU;
        $response['ministries'] = $ministries;
        $response['organizationUnits'] = $organizationUnits;
        $response['organizations'] = $organizations;

        return $response;
    }

    /**
     * @Route("/portal/emp/roles/add", name="portal_emp_roles_add")
     */
    public function rolesAdd(Request $request)
    {
        $submittedToken = $request->request->get('token');
        $objid = $request->request->get('objid');
        $role = $request->request->get('role');
        $selectedOu = $request->request->get('ou');
        $selectedO = $request->request->get('o');
        $selectedM = $request->request->get('m');
        $em = $this->getDoctrine()->getManager();
        $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);

        if ($role) {
            if ($selectedOu) {
                $csrf = $this->get('security.csrf.token_manager');
                $token = $csrf->refreshToken('form_intention');
                $employeeUser = $employee->getUser();
                $em->getConnection()->beginTransaction();
                try {
                    if ('ROLE_OU_ADMIN' === $role) {
                        $employee->setIsOUAdmin(true);
                    }
                    $selectedOuObj = '';
                    $selectedOObj = '';
                    $selectedMObj = '';
                    if ($selectedOu) {
                        $selectedOuObj = $em->getRepository('App:Portal\OrganizationUnit')->find($selectedOu);
                        $selectedOObj = $selectedOuObj->getOrganization();
                        $selectedMObj = $selectedOObj->getMinistry();
                    }

                    $metada = $this->metadata->getPortalMetadata('I');
                    $uuid = \Ramsey\Uuid\Uuid::uuid4();

                    $profile = new Profile();
                    $profile->setUser($employee->getUser());
                    $profile->setGuid($uuid);
                    $profile->setFromDate(new \DateTime('now'));
                    $profile->setIsEnabled(true);
                    $profile->setIsDefault(false);
                    $profile->setIsCurrent(false);
                    $profile->setIsAdditional(true);
                    $profile->setInsertMetaData($metada);
                    $profile->setRole($em->getRepository('App:Portal\Roles')->findOneByRole($role));
                    if ($selectedOuObj) {
                        $oU = $selectedOuObj;
                    } else {
                        $oU = $this->profileWorkspace->getOu();
                    }

                    if ($selectedOObj) {
                        $o = $selectedOObj;
                    } else {
                        $o = $this->profileWorkspace->getOrganization();
                    }

                    if ($selectedMObj) {
                        $m = $selectedMObj;
                    } else {
                        $m = $this->profileWorkspace->getMinistry();
                    }

                    if ('ROLE_MINISTRY_ADMIN' == $role || 'ROLE_O_ADMIN' == $role || 'ROLE_OU_ADMIN' == $role || 'ROLE_GROUP_ADMIN' == $role) {
                        if ($m) {
                            $profile->setOrganizationUnit($selectedOuObj);
                            $profile->setOrganization($selectedOObj);
                            $profile->setMinistry($m);
                        } elseif ($o) {
                            $profile->setOrganizationUnit($selectedOuObj);
                            $profile->setOrganization($o);
                            $profile->setMinistry($o->getMinistry());
                        } elseif ($oU) {
                            $profile->setOrganizationUnit($oU);
                            $profile->setOrganization($oU->getOrganization());
                            $profile->setMinistry($oU->getOrganization()->getMinistry());
                        }
                    } else {
                        $profile->setOrganizationUnit($oU);
                        $profile->setOrganization($oU->getOrganization());
                        $profile->setMinistry($oU->getOrganization()->getMinistry());
                    }
                    $em->persist($profile);
                    $em->persist($employeeUser);
                    $em->persist($employee);
                    $em->flush();
                    $em->getConnection()->commit();
                    $status = 'success';
                    $message = 'Successfull';
                } catch (Exception $ex) {
                    $status = 'danger';
                    $message = 'Exception occurred';
                    $em->getConnection()->rollback();
                    $this->logger_exceptions->info($ex->getMessage());
                }
            } else {
                $status = 'danger';
                $message = 'Role to be selected';
            }
        } else {
            $status = 'danger';
            $message = 'OU to be selected';
        }

        $response = $this->getRoleDetails($employee, $selectedOuObj);

        return $this->render('portal/employee/_role_change.html.twig', [
                    'status' => $status,
                    'message' => $message,
                    'employee' => $employee,
                    'availableRoles' => $response['availableRoles'],
                    'assignedRoles' => $response['assignedRoles'],
                    'ministries' => $response['ministries'],
                    'organizations' => $response['organizations'],
                    'organizationUnits' => $response['organizationUnits'],
                    'oU' => $response['oU'],
        ]);
    }

    /**
     * @Route("/portal/emp/roles/remove", name="portal_emp_roles_remove")
     */
    public function rolesRemove(Request $request)
    {
        $objid = $request->request->get('objid');
        $role = $request->request->get('role');
        $selectedOu = $request->request->get('ou');
        $submittedToken = $request->request->get('token');
        // if (!$this->isCsrfTokenValid('form_intention', $submittedToken)) {
        //     return new JsonResponse(['status' => 'danger', 'message' => 'Outdated request attempt, please try again via proper login']);
        // }
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');
        $em = $this->getDoctrine()->getManager();
        $profile = $em->getRepository('App:Portal\Profile')->findOneBy(['guId' => $objid]);
        $employee = null;
        if ($profile) {
            $employeeUser = $profile->getUser();
            $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['user' => $employeeUser]);
            $em->getConnection()->beginTransaction();
            try {
                if ('ROLE_OU_ADMIN' === $role) {
                    $employee->setIsOUAdmin(false);
                }
//            $employeeUser->removeRole($role);
                $roleObj = $em->getRepository('App:Portal\Roles')->findOneByRole($role);
                $profile->setIsEnabled(false);
                $profile->setToDate(new \DateTime('now'));
                $metada = $this->metadata->getPortalMetadata('U');
                $profile->setUpdateMetaData($metada);
                $em->persist($employeeUser);
                $em->persist($employee);
                $em->persist($profile);
                $em->flush();
                $em->getConnection()->commit();
                $status = 'success';
                $message = 'Successfull';
            } catch (Exception $ex) {
                $status = 'error';
                $message = 'Exception occurred';
                $em->getConnection()->rollback();
                $this->logger_exceptions->info($ex->getMessage());
            }
        } else {
            $status = 'error';
            $message = 'Cannot disable default role';
        }
        $selectedOuObj = '';
        if ($selectedOu) {
            $selectedOuObj = $em->getRepository('App:Portal\OrganizationUnit')->find($selectedOu);
        }
        $response = $this->getRoleDetails($employee, $selectedOuObj);

        return $this->render('portal/employee/_role_change.html.twig', [
                    'status' => $status,
                    'message' => $message,
                    'employee' => $employee,
                    'availableRoles' => $response['availableRoles'],
                    'assignedRoles' => $response['assignedRoles'],
                    'ministries' => $response['ministries'],
                    'organizations' => $response['organizations'],
                    'organizationUnits' => $response['organizationUnits'],
                    'oU' => $response['oU'],
        ]);
    }

    /**
     * @Route("/portal/emp/groups", name="portal_emp_groups")
     */
    public function groups(Request $request)
    {
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        return $this->render('portal/employee/_group_change.html.twig', [
                    'employee' => $employee,
                    'groupStatus' => $this->getGroupStatus($employee),
        ]);
    }

    /**
     * @Route("/portal/emp/groups/add", name="portal_emp_groups_add")
     */
    public function groupsAdd(Request $request)
    {
        $submittedToken = $request->request->get('token');
        $objid = $request->request->get('objid');
        $group = $request->request->get('group');

        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        $groupObj = $em->getRepository('App:Portal\Group')->findOneBy(['guId' => $group]);
        $IsMemberInGroup = $em->getRepository('App:Portal\MemberInGroup')->findBy(['employee' => $employee, 'group' => $groupObj]);
        $GroupMembers = $this->getDoctrine()->getRepository(MemberInGroup::class)->findByGroup($groupObj);
        $MemberCount = count($GroupMembers);
        if ($IsMemberInGroup) {
            $result = json_encode(['status' => 'danger', 'message' => 'The member has been added successfully !!']);
        } else {
            $memberInGroup = new MemberInGroup();
            try {
                if ('PERMANENT' === $groupObj->getGroupType()->getGroupTypeCode()) {
                    $role = 1;
                    $affiliation = 1;
                } else {
                    $role = 3;
                    $affiliation = 1;
                }
                $groupName = $groupObj->getGroupName();
                $members = [['jid' => $employee->getJabberId(), 'role' => $role, 'affiliation' => $affiliation]];
                $gzrResult = $this->xmppGroupV5->subscribeMemberV5($loggedUser->getId(), \json_encode(['member' => $members]), $groupName, $groupObj->getXmppHost());
            } catch (Exception $ex) {
                return new Response(json_encode(['status' => 'danger', 'message' => $ex->getMessage()]));
            }
            $result = json_encode(['status' => 'success', 'message' => 'The member has been added successfully !!']);
        }
        // }

        return new JsonResponse(['form' => $this->renderView('portal/employee/_group_change.html.twig', [
                    'employee' => $employee,
                    'groupStatus' => $this->getGroupStatus($employee), ]),
                    'result' => $gzrResult,
        ]);
    }

    /**
     * @Route("/portal/emp/groups/remove", name="portal_emp_groups_remove")
     */
    public function groupsRemove(Request $request)
    {
        $submittedToken = $request->request->get('token');
        $objid = $request->request->get('objid');
        $group = $request->request->get('group');

        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        $groupObj = $em->getRepository('App:Portal\Group')->findOneBy(['guId' => $group]);
        $IsMemberInGroup = $em->getRepository('App:Portal\MemberInGroup')->findOneBy(['employee' => $employee, 'group' => $groupObj]);
        if ($IsMemberInGroup) {
            try {
                $groupName = $groupObj->getGroupName();
                $gzrResult = $this->xmppGroupV5->unSubscribeMemberV5($loggedUser->getId(), $employee->getJabberId(), $groupName, $groupObj->getXmppHost());
            } catch (Exception $ex) {
                return new Response(json_encode(['status' => 'danger', 'message' => $ex->getMessage()]));
            }
            $result = json_encode(['status' => 'success', 'message' => 'The member has been removed successfully !!']);
        } else {
            $result = json_encode(['status' => 'danger', 'message' => 'The member has been removed successfully !!']);
        }

        return new JsonResponse(['form' => $this->renderView('portal/employee/_group_change.html.twig', [
                    'employee' => $employee,
                    'groupStatus' => $this->getGroupStatus($employee), ]),
                    'result' => $gzrResult,
        ]);
    }

    /**
     * @Route("/portal/emp/groupsadmin", name="portal_emp_groups_admin")
     */
    public function groupsAdmin(Request $request)
    {
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        return $this->render('portal/employee/_group_change_admin.html.twig', [
                    'employee' => $employee,
                    'groupStatus' => $this->getGroupStatusAdmin($employee),
        ]);
    }

    /**
     * @Route("/portal/emp/groups/groupsadminadd", name="portal_emp_groups_admin_add")
     */
    public function groupsAdminAdd(Request $request)
    {
        $submittedToken = $request->request->get('token');
        $objid = $request->request->get('objid');
        $group = $request->request->get('group');

        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        $groupObj = $em->getRepository('App:Portal\Group')->findOneBy(['guId' => $group]);
        $em->getConnection()->beginTransaction();
                try {

                    $metada = $this->metadata->getPortalMetadata('I');
                    $uuid = \Ramsey\Uuid\Uuid::uuid4();

                    $profile = new Profile();
                    $profile->setUser($employee->getUser());
                    $profile->setGuid($uuid);
                    $profile->setFromDate(new \DateTime('now'));
                    $profile->setIsEnabled(true);
                    $profile->setIsDefault(false);
                    $profile->setIsCurrent(false);
                    $profile->setIsAdditional(true);
                    $profile->setInsertMetaData($metada);
                    $profile->setRole($em->getRepository('App:Portal\Roles')->findOneByRole("ROLE_GROUP_ADMIN"));

                    $oU = $employee->getOrganizationUnit();

                    $profile->setOrganizationUnit($oU);
                    $profile->setOrganization($oU->getOrganization());
                    $profile->setMinistry($oU->getOrganization()->getMinistry());

                    $employeeGroupAdmin = new EmployeeGroupAdmin();
                    $employeeGroupAdmin->setEmployee($employee);
                    $employeeGroupAdmin->setGroup($groupObj);
                    $employeeGroupAdmin->setIsEnabled(true);
                    $groupEnableMetadata = $this->metadata->getPortalMetadata('E');
                    $employeeGroupAdmin->setEnableMetadata($groupEnableMetadata);

                    $em->persist($employeeGroupAdmin);
                    $em->persist($profile);
                    $em->flush();
                    $em->getConnection()->commit();
                    $status = 'success';
                    $message = 'Successfull';
                } catch (Exception $ex) {
                    $status = 'danger';
                    $message = 'Exception occurred';
                    $em->getConnection()->rollback();
                    $this->logger_exceptions->info($ex->getMessage());
                }
                
        $view = $this->renderView('portal/employee/_group_change_admin.html.twig', ['employee' => $employee, 'groupStatus' => $this->getGroupStatusAdmin($employee)]);
        $result = json_encode(['status' => 'success', 'message' => 'Updated Successfully']);

        return new JsonResponse(['form' => $view, 'result' => $result]);
    }

    /**
     * @Route("/portal/emp/groups/groupsadminremove", name="portal_emp_groups_admin_remove")
     */
    public function groupsAdminRemove(Request $request)
    {
        $submittedToken = $request->request->get('token');
        $objid = $request->request->get('objid');
        $group = $request->request->get('group');

        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        $groupObj = $em->getRepository('App:Portal\Group')->findOneBy(['guId' => $group]);
        $employeeGroupAdmin = $em->getRepository('App:Portal\EmployeeGroupAdmin')->findOneBy(['group' => $groupObj, 'isEnabled' => true]);
        $employeeGroupAdmin->setIsEnabled(false);
        $groupDisableMetadata = $this->metadata->getPortalMetadata('D');
        $employeeGroupAdmin->setDisableMetadata($groupDisableMetadata);
        $em->persist($employeeGroupAdmin);
        $em->flush();

        $view = $this->renderView('portal/employee/_group_change_admin.html.twig', ['employee' => $employee, 'groupStatus' => $this->getGroupStatusAdmin($employee)]);
        $result = json_encode(['status' => 'success', 'message' => 'Updated Successfully']);

        return new JsonResponse(['form' => $view, 'result' => $result]);
    }

    private function getGroupStatus($employee)
    {
        $em = $this->getDoctrine()->getManager();
        $qryAvGrps = $em->createQuery("SELECT g FROM App:Portal\Group g WHERE g.id not in (SELECT identity(mg.group) FROM App:Portal\MemberInGroup mg WHERE mg.employee = :employee) AND g.organizationUnit = :ou");
        $qryAvGrps->setParameter('ou', $employee->getOrganizationUnit());
        $qryAvGrps->setParameter('employee', $employee);
        $availableGroupsObjects = $qryAvGrps->getResult();
        $assignedGroupsObjects = $em->getRepository('App:Portal\MemberInGroup')->findBy(['employee' => $employee]);

        return ['availableGroups' => $availableGroupsObjects, 'assignedGroups' => $assignedGroupsObjects];
    }

    private function getGroupStatusAdmin($employee)
    {
        $em = $this->getDoctrine()->getManager();

        $qryAvGrps = $em->createQuery("SELECT g FROM App:Portal\Group g WHERE g.id not in (SELECT identity(ega.group) FROM App:Portal\EmployeeGroupAdmin ega WHERE ega.employee = :employee AND ega.isEnabled = :isValue) AND g.organizationUnit = :ou");
        $qryAvGrps->setParameter('ou', $employee->getOrganizationUnit());
        $qryAvGrps->setParameter('employee', $employee);
        $qryAvGrps->setParameter('isValue', 'true');
        $availableGroupsObjects = $qryAvGrps->getResult();
        $assignedGroupsObjects = $em->getRepository('App:Portal\EmployeeGroupAdmin')->findBy(['employee' => $employee, 'isEnabled' => true]);

        return ['availableGroups' => $availableGroupsObjects, 'assignedGroups' => $assignedGroupsObjects];
    }

    /**
     * @Route("/portal/emp/photo/{egId}", name="portal_emp_photo")
     */
    public function employeePhoto($egId): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($egId);
        $employeePhoto = $this->getDoctrine()->getRepository(FileDetail::class)->findOneById($employee->getPhoto());
        $encPhoto = $employeePhoto->getThumbnail();

        if (null == $encPhoto) {
            $encPhoto = $this->imageProcess->generateThumbnail(stream_get_contents($employeePhoto->getFileData()));
            $em = $this->getDoctrine()->getManager();
            $employeePhoto->setThumbnail($encPhoto);
            $em->persist($employeePhoto);
            $em->flush();
            $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($encPhoto) {
                echo $encPhoto;
            });
        } else {
            $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($encPhoto) {
                echo stream_get_contents($encPhoto);
            });
        }
        $response->headers->set('Content-Type', 'application/image');

        return $response;
    }

    /**
     * @Route("/portal/emp/transfer", name="portal_employee_transfer")
     */
    public function transfer(Request $request)
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();

        $loggedUser = $this->getUser();
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }
        $active_profiles = $em->getRepository('App:Portal\Profile')->findBy(['user' => $employee->getUser(), 'isEnabled' => true]);
        $form = $this->createForm(EmployeeTransferType::class);
        if ($employee->getPhoto()){
            $photo = base64_encode(stream_get_contents($employee->getPhoto()->getFileData()));
        } else {
            $photo = null;
        }
        return $this->render('portal/employee/_form_transfer.html.twig', [
                    'form' => $form->createView(),
                    'profile' => $profile,
                    'active_profiles' => $active_profiles,
                    'photo' => $photo,
                    'employee' => $employee,
                    'groupStatus' => $this->getGroupStatus($employee),
        ]);
    }

    /**
     * @Route("/portal/emp/transupd", name="portal_employee_transfer_update")
     */
    public function transferUpdate(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $ouid = (int) $request->request->get('ou');
        $loggedUser = $this->getUser();
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $current_ou = null;
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
        } else {
            $current_ou = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $current_ou]);
        }

        $active_profiles = $em->getRepository('App:Portal\Profile')->findBy(['user' => $employee->getUser(), 'isEnabled' => true]);
        if ($active_profiles) {
            return new JsonResponse(['status' => 'danger', 'message' => 'Please remove assigned roles before transferring']);
        }

        $transferred_to_ou = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneById($ouid);
        $transferred_employee_profile = $em->getRepository("App:Portal\Profile")->findOneBy(['user' => $employee->getUser()]);

        if (!$transferred_to_ou) {
            return new JsonResponse(['status' => 'danger', 'message' => 'Error in getting destination OU']);
        }

        if (!$employee) {
            return new JsonResponse(['status' => 'danger', 'message' => 'Are you eligible for transferring this employee']);
        }

        $em->getConnection()->beginTransaction();
        try {
            $empHostname = $transferred_to_ou->getOrganization()->getVhost();

            $employee->setOrganizationUnit($transferred_to_ou);
            $jabberID = $employee->getJabberId();
            $PRmetadata = $this->metadata->getPortalMetadata('TR');
            $transferred_employee_profile->setUpdateMetaData($PRmetadata);
            $transferred_employee_profile->setOrganizationUnit($transferred_to_ou);
            $transferred_employee_profile->setOrganization($transferred_to_ou->getOrganization());
            $transferred_employee_profile->setMinistry($transferred_to_ou->getOrganization()->getMinistry());
            $em->persist($employee);
            $em->persist($transferred_employee_profile);
            $em->flush();
            $em->getConnection()->commit();
            $this->xmppGeneral->refreshProfileV5($employee->getGuId());

            return new JsonResponse(['status' => 'success', 'message' => 'Transfer Updation Successful']);
        } catch (\Exception $ex) {
            
            $em->getConnection()->rollback();
            $this->logger_exceptions->info($ex->getMessage());

            return new JsonResponse(['status' => 'danger', 'message' => 'An error has been occured ']);
        }
    }

    /**
     * @Route("/portal/emp/offboard", name="portal_employee_offboard")
     */
    public function offboard(Request $request)
    {
        $objid = $request->request->get('objid');

        $offBoardReasons = $this->getDoctrine()->getRepository(OffBoardReason::class)->findAll();
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        $form = $this->createForm(EmployeeOffBoardType::class);
        if ($employee->getPhoto()){
            $photo = base64_encode(stream_get_contents($employee->getPhoto()->getFileData()));
        } else {
            $photo = null;
        }
        return $this->render('portal/employee/_form_offboard.html.twig', [
                    'form' => $form->createView(),
                    'profile' => $profile,
                    'photo' => $photo,
                    'employee' => $employee,
                    'offBoardReasons' => $offBoardReasons,
                    'groupStatus' => $this->getGroupStatus($employee),
        ]);
    }

    /**
     * @Route("/portal/emp/offboardupd", name="portal_employee_offboard_update")
     */
    public function offboardUpdate(Request $request)
    {
        $objid = $request->request->get('objid');
        $reason = $request->request->get('reason');
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
        $offBoardReason = $this->getDoctrine()->getRepository(OffBoardReason::class)->findOneByOffBoardReasonCode($reason);
        $assignedGroupsObjects = $em->getRepository('App:Portal\MemberInGroup')->findBy(['employee' => $employee]);
        
        $api_call_status = $this->xmppGeneral->employeeOffboard($loggedUser->getId(), $employee->getGuId(), $offBoardReason->getId());
        return new Response(json_encode($api_call_status));
        if ($assignedGroupsObjects) {
            return new Response(json_encode(['status' => 'danger', 'message' => 'Employee is a member of group(s), Please remove the groups ']));
        }
        // $em->getConnection()->beginTransaction();
        // try {
        //     $OBmetadata = $this->metadata->getPortalMetadata('OB');
        //     $employee->setOffBoardReason($offBoardReason);
        //     if ($archivalOU) {
        //         $employee->setOrganizationUnit($archivalOU);
        //         $PRmetadata = $this->metadata->getPortalMetadata('OB');
        //         $profile->setUpdateMetaData($PRmetadata);
        //         $profile->setOrganizationUnit($archivalOU);
        //         $profile->setOrganization($archivalOU->getOrganization());
        //         $profile->setMinistry($archivalOU->getOrganization()->getMinistry());
        //         $em->persist($profile);
        //     }
        //     $employee->setOffBoardMetadata($OBmetadata);
        //     $em->persist($employee);
        //     $em->flush();
        //     $em->getConnection()->commit();

        //     return new Response(json_encode(['status' => 'success', 'message' => 'Updation Successful']));
        // } catch (\Exception $ex) {
        //     $em->getConnection()->rollback();

        //     return new Response(json_encode(['status' => 'danger', 'message' => 'An error has been occured ']));
        // }
    }

    /**
     * @Route("/portal/emp/otl", name="portal_employee_otl")
     */
    public function oneTimeLink(Request $request)
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        try {
            $employee = $em->getRepository(Employee::class)->findOneByGuId($objid);
            $status = $this->oneTimeLinker->createOTL($employee->getUser(), strtolower($employee->getEmailAddress()));
            $em->getConnection()->commit();
            $type = 'success';
            if (false !== strpos($status, 'unsuccessful')) {
                $type = 'danger';
            }

            return new JsonResponse(['status' => $type, 'message' => $status]);
        } catch (\Exception $ex) {
            $em->getConnection()->rollback();
            $this->logger_exceptions->info($ex->getMessage());

            return new JsonResponse(['status' => 'danger', 'message' => 'An error has been occured ']);
        }
    }

    /**
     * @Route("/portal/emp/otlBulk", name="portal_employee_otl_bulk")
     */
    public function oneTimeLinkBulk(Request $request)
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        try {
            $loggedUser = $this->getUser();
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $oU = 0;
                $employees = $em->getRepository(Employee::class)->findAll();
            } else {
                $oU = $this->profileWorkspace->getOu()->getId();
                $employees = $em->getRepository(Employee::class)->findByOrganizationUnit($oU);
            }
            $success = 0;
            $failed = 0;
            foreach ($employees as $employee) {
                $status = $this->oneTimeLinker->createOTL($employee->getUser(), strtolower($employee->getEmailAddress()));
                if (false !== strpos($status, 'unsuccessful')) {
                    ++$success;
                } else {
                    ++$failed;
                }
            }
            $em->getConnection()->commit();
            $type = 'danger';
            if ($success) {
                $type = 'success';
            }
//            $success=$success-$failed;
            $status = ($success ? "Successfully send OTL to $success users" : '').($failed ? " failed to send to $failed users" : '');
//            if (strpos($status, 'unsuccessful') !== false) {
//                $type = 'danger';
//            }
            return new JsonResponse(['status' => $type, 'message' => $status]);
        } catch (\Exception $ex) {
            $em->getConnection()->rollback();
            $this->logger_exceptions->info($ex->getMessage());

            return new JsonResponse(['status' => 'danger', 'message' => 'An error has been occured ']);
        }
    }

    /**
     * @Route("/portal/emp/beta", name="portal_emp_beta")
     */
    public function beta(Request $request)
    {
        $objid = $request->request->get('objid');
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
        //$em = $this->getDoctrine()->getManager();
        if ($employee->getPhoto()){
            $photo = base64_encode(stream_get_contents($employee->getPhoto()->getFileData()));
        } else {
            $photo = null;
        }
        return $this->render('portal/employee/_form_beta.html.twig', [
                    'employee' => $employee,
                    'photo' => $photo,
        ]);
    }

    /**
     * @Route("/portal/emp/betaConfirm", name="portal_emp_beta_confirm")
     */
    public function betaConfirm(Request $request)
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        $employeeUser = $employee->getUser();
        $employeeUser->setBetaUser(!$employeeUser->getIsBetaUser());
        $em->getConnection()->beginTransaction();
        try {
            $em->persist($employeeUser);
            $em->flush();
            $em->getConnection()->commit();

            return new Response(json_encode(['status' => 'success', 'message' => 'Updation Successful']));
        } catch (\Exception $ex) {
            $em->getConnection()->rollback();
            $this->logger_exceptions->info($ex->getMessage());

            return new Response(json_encode(['status' => 'danger', 'message' => 'An error has been occured ']));
        }
    }

    /**
     * @Route("/portal/emp/migrate", name="portal_emp_migrate")
     */
    public function migrate(Request $request)
    {
        $objid = $request->request->get('objid');
        $status = null;
        $vhost_current = '';
        $vhost_new = '';
        $api_request_status = null;
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
        $employee_migration_status = $this->getDoctrine()->getRepository(EmployeeMigrationStatus::class)->findOneByEmployee($employee);

        if ($employee_migration_status) {
            $api_request_status = $this->getDoctrine()->getRepository(APIRequestStatus::class)->findOneById($employee_migration_status->getRequestId());
            $status = 'STATUS: Already in progress';
        }
        $vhost_new = $employee->getOrganizationUnit()->getOrganization()->getVhost();
        $vhost_current_jid = $employee->getJabberId();
        $parts = explode('@', strtolower($vhost_current_jid));
        $vhost_current = $parts[1];
        if ($vhost_new === $vhost_current) {
            $status = 'STATUS: No need of migrations as both domains are the same';
        }
        if ($employee->getPhoto()){
            $photo = base64_encode(stream_get_contents($employee->getPhoto()->getFileData()));
        } else {
            $photo = null;
        }
        return $this->render('portal/employee/_migrate.html.twig', [
                    'employee' => $employee,
                    'photo' => $photo,
                    'status' => $status,
                    'vhost_new' => $vhost_new,
                    'vhost_current' => $vhost_current,
                    'apistatus' => $api_request_status,
        ]);
    }

    /**
     * @Route("/portal/emp/migrateConfirm", name="portal_emp_migrate_confirm")
     */
    public function migrateConfirm(Request $request)
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $status = null;
        $vhost_current = '';
        $vhost_new = '';
        $api_request_status = null;
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
        $employee_migration_status = $this->getDoctrine()->getRepository(EmployeeMigrationStatus::class)->findOneByEmployee($employee);

        if ($employee_migration_status) {
            return new Response(json_encode(['status' => 'danger', 'message' => 'Migration already in progress, please check status']));
        }
        $vhost_new = $employee->getOrganizationUnit()->getOrganization()->getVhost();
        $vhost_current_jid = $employee->getJabberId();
        $parts = explode('@', strtolower($vhost_current_jid));
        $vhost_current = $parts[1];
        if ($vhost_new === $vhost_current) {
            return new Response(json_encode(['status' => 'danger', 'message' => 'Both domains are same, migration not required']));
        }

        $loggedUser = $this->getUser();
        try {
            $api_return = $this->xmppGeneral->migrateUser($loggedUser->getId(), $employee->getGuId(), $employee->getOrganizationUnit()->getOrganization()->getVhost());
            $api_return_status = json_decode($api_return);
            if ('success' === $api_return_status->status) {
                $employee_migration_status = new EmployeeMigrationStatus();
                $employee_migration_status->setEmployee($employee);
                $employee_migration_status->setRequestId($api_return_status->data->reqid);
                $em->persist($employee_migration_status);
                $em->flush();

                return new Response(json_encode(['status' => 'success', 'message' => 'Migration scheduled, please check for status here later']));
            } else {
                return new Response(json_encode(['status' => 'danger', 'message' => 'An internal error has been occured ']));
            }
        } catch (\Exception $ex) {
            $this->logger_exceptions->info($ex->getMessage());

            return new Response(json_encode(['status' => 'danger', 'message' => 'An error has been occured ']));
        }
    }

    /**
     * @Route("/portal/emp/emp-byou", name="portal_employee_byou")
     */
    public function getEmployeeByOu(Request $request, $param)
    {
        $em = $this->getDoctrine()->getManager();
        $oU = $this->profileWorkspace->getOu()->getId();
        $employee = $em->getRepository('App:Portal\Employee')->findByOrganizationUnit($oU);

        return $this->render('CodeFinder/parent1.html.twig', ['masterEntity' => $employee, 'param' => $param]);
    }

    /**
     * @Route("/portal/emp/apps", name="portal_emp_apps")
     */
    public function apps(Request $request)
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        $addedApps = $em->getRepository('App:Portal\EmployeeApps')->findBy(['employee' => $employee, 'isDeleted' => false]);

        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $profile = $this->profileWorkspace->getProfile();

        $form = $this->createForm(EmployeeAppsType::class);

        return $this->render('portal/employee/_form_apps.html.twig', [
                    'form' => $form->createView(),
                    'employee' => $employee,
                    'addedApps' => $addedApps,
        ]);
    }

    /**
     * @Route("/portalemp/apps/update",name="portal_employee_apps_update")
     */
    public function updateApps(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $externenAppId = $request->request->get('ou');
        if (!$externenAppId) {
            return new Response(json_encode(['status' => 'danger', 'message' => 'An error has been occured ']));
        }
        $employee = $em->getRepository('App:Portal\Employee')->findOneBy(['guId' => $objid]);
        $externenAppEntity = $em->getRepository('App:Portal\ExternalApps')->findOneBy(['id' => $externenAppId]);
        $EmployeeApps = $em->getRepository('App:Portal\EmployeeApps')->findBy(['employee' => $employee, 'ExternalApps' => $externenAppEntity, 'isDeleted' => false]);
        if ($EmployeeApps) {
            return new Response(json_encode(['status' => 'danger', 'message' => 'Already Added']));
        }
        if ($employee && $externenAppEntity) {
            $employeeExternalApp = new EmployeeApps();
            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $employeeExternalApp->setGuId($uuid->toString());
            $employeeExternalApp->setEmployee($employee);
            $employeeExternalApp->setExternalApps($externenAppEntity);
            $employeeExternalApp->setIsDeleted(false);
            $metada = $this->metadata->getPortalMetadata('I');
            $employeeExternalApp->setInsertMetaData($metada->getId());
            $em->persist($employeeExternalApp);
            $em->flush();

            return new Response(json_encode(['status' => 'success', 'message' => 'Apps Updation Successful']));
        } else {
            return new Response(json_encode(['status' => 'danger', 'message' => 'An error has been occured ']));
        }
    }

    /**
     * @Route("/portal/emp/apps/remove", name="portal_emp_apps_remove")
     */
    public function appsRemove(Request $request)
    {
        $objid = $request->request->get('objid');
        $submittedToken = $request->request->get('token');
        // if (!$this->isCsrfTokenValid('form_intention', $submittedToken)) {
        //     return new JsonResponse(['status' => 'danger', 'message' => 'Outdated request attempt, please try again via proper login']);
        // }
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');
        $em = $this->getDoctrine()->getManager();
        $EmployeeApps = $em->getRepository('App:Portal\EmployeeApps')->findOneBy(['guId' => $objid]);
        $employee = $EmployeeApps->getEmployee();

        if ($EmployeeApps) {
            $EmployeeApps->setIsDeleted(true);
            $metada = $this->metadata->getPortalMetadata('U');
            $EmployeeApps->setUpdateMetaData($metada);
            $em->persist($EmployeeApps);
            $em->flush();

            $result = json_encode(['status' => 'success', 'message' => 'The member has been removed successfully !!']);
        }
        $form = $this->createForm(EmployeeAppsType::class);
        $EmployeeAppsEntity = $em->getRepository('App:Portal\EmployeeApps')->findBy(['employee' => $employee, 'isDeleted' => false]);

        return $this->render('portal/employee/_form_apps.html.twig', [
                    'form' => $form->createView(),
                    'employee' => $employee,
                    'addedApps' => $EmployeeAppsEntity,
        ]);
    }

    /**
     * @Route("/portal/emp/gimsMsg", name="portal_emp_gims_messages")
     */
    public function gimsMessages(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);
        $query = $this->processQry($dynamicFilters)->getResult();
        $queryCount = count($query);
        $data = [];
        if ($queryCount > 1000) {
            return new Response(json_encode(['status' => 'danger', 'message' => ' Allows Up To 1000 Members']));
        }
        if (!$query) {
            return new Response(json_encode(['status' => 'danger', 'message' => ' No Result Found!']));
        } else {
            foreach ($query as $value) {
                $data[$value['id']] = $value['emailAddress'];
            }
            $EmployeeMessages = new EmployeeMessages();
            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $EmployeeMessages->setGuId($uuid->toString());
            $EmployeeMessages->setUser($loggedUser);
            $EmployeeMessages->setMembers($data);
            $metada = $this->metadata->getPortalMetadata('I');
            $EmployeeMessages->setInsertMetaData($metada->getId());
            $em->persist($EmployeeMessages);
            $em->flush();
            $objid = $EmployeeMessages->getGuId();
            $msgId = $EmployeeMessages->getId() . ' [Total Members '.$queryCount. ']';

            return new Response(json_encode(['status' => 'success', 'objid' => $objid, 'msgId' => $msgId]));
        }
    }

    /**
     * @Route("/portal/emp/gimsMsgRolewise_o", name="portal_emp_gims_messages_rolewise_o")
     */
    public function gimsMessagesRolewise_o(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        // Query for role_o_admin
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
        // Query for role_o_admin ends
        // $query = $this->processQry($dynamicFilters)->getResult();
        $query = $qryListResult->getResult();
        $queryCount = count($query);

        $data = [];
        if ($queryCount > 1000) {
            return new Response(json_encode(['status' => 'danger', 'message' => ' Allows Up To 1000 Members']));
        }
        if (!$query) {
            return new Response(json_encode(['status' => 'danger', 'message' => ' No Result Found!']));
        } else {
            foreach ($query as $value) {
                $data[$value['id']] = $value['emailAddress'];
            }
            $EmployeeMessages = new EmployeeMessages();
            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $EmployeeMessages->setGuId($uuid->toString());
            $EmployeeMessages->setUser($loggedUser);
            $EmployeeMessages->setMembers($data);
            $metada = $this->metadata->getPortalMetadata('I');
            $EmployeeMessages->setInsertMetaData($metada->getId());
            $em->persist($EmployeeMessages);
            $em->flush();
            $objid = $EmployeeMessages->getGuId();
            $msgId = $EmployeeMessages->getId();

            return new Response(json_encode(['status' => 'success', 'objid' => $objid, 'msgId' => $msgId]));
        }
    }

    /**
     * @Route("/portal/emp/gimsMsgRolewise_ou", name="portal_emp_gims_messages_rolewise_ou")
     */
    public function gimsMessagesRolewise_ou(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        // Query for role_ouadmin
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
        // Query for role_ouadmin ends
        // $query = $this->processQry($dynamicFilters)->getResult();
        $query = $qryListResult->getResult();
        $queryCount = count($query);

        $data = [];
        if ($queryCount > 1000) {
            return new Response(json_encode(['status' => 'danger', 'message' => ' Allows Up To 1000 Members']));
        }
        if (!$query) {
            return new Response(json_encode(['status' => 'danger', 'message' => ' No Result Found!']));
        } else {
            foreach ($query as $value) {
                $data[$value['id']] = $value['emailAddress'];
            }
            $EmployeeMessages = new EmployeeMessages();
            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $EmployeeMessages->setGuId($uuid->toString());
            $EmployeeMessages->setUser($loggedUser);
            $EmployeeMessages->setMembers($data);
            $metada = $this->metadata->getPortalMetadata('I');
            $EmployeeMessages->setInsertMetaData($metada->getId());
            $em->persist($EmployeeMessages);
            $em->flush();
            $objid = $EmployeeMessages->getGuId();
            $msgId = $EmployeeMessages->getId();

            return new Response(json_encode(['status' => 'success', 'objid' => $objid, 'msgId' => $msgId]));
        }
    }

    /**
     * @Route("/portal/emp/gimsMsgRolewise_m", name="portal_emp_gims_messages_rolewise_m")
     */
    public function gimsMessagesRolewise_m(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        // Query for role_m_admin
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
        // Query for role_m_admin ends
        // $query = $this->processQry($dynamicFilters)->getResult();
        $query = $qryListResult->getResult();
        $queryCount = count($query);

        $data = [];
        if ($queryCount > 1000) {
            return new Response(json_encode(['status' => 'danger', 'message' => ' Allows Up To 1000 Members']));
        }
        if (!$query) {
            return new Response(json_encode(['status' => 'danger', 'message' => ' No Result Found!']));
        } else {
            foreach ($query as $value) {
                $data[$value['id']] = $value['mobileNumber'];
            }
            $EmployeeMessages = new EmployeeMessages();
            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            $EmployeeMessages->setGuId($uuid->toString());
            $EmployeeMessages->setUser($loggedUser);
            $EmployeeMessages->setMembers($data);
            $metada = $this->metadata->getPortalMetadata('I');
            $EmployeeMessages->setInsertMetaData($metada->getId());
            $em->persist($EmployeeMessages);
            $em->flush();
            $objid = $EmployeeMessages->getGuId();
            $msgId = $EmployeeMessages->getId();

            return new Response(json_encode(['status' => 'success', 'objid' => $objid, 'msgId' => $msgId]));
        }
    }

    /**
     * @Route("/sadmin/rwov", name="superadmin_remote_wipeout_view")
     */
    public function superAdminRemoteWipeoutView(Request $request)
    {
        $objid = $request->request->get('objid');

        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneByGuId($objid);
        }

        if ($employee->getPhoto()){
            $photo = base64_encode(stream_get_contents($employee->getPhoto()->getFileData()));
        } else {
            $photo = null;
        }
        return $this->render('portal/employee/_form_remote_wipeout_view.html.twig', [
                    'employee' => $employee,
                    'photo' => $photo,
        ]);
    }

    /**
     * @Route("/sadmin/rwoc", name="superadmin_remote_wipeout_confirm")
     */
    public function superAdminRemoteWipeoutConfirm(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');

        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $employee = $em->getRepository('App:Portal\Employee')->findOneByGuId($objid);
        } 

        $api_call_status = $this->xmppGeneral->remoteWipeout($loggedUser->getId(), $employee->getGuId(), 1);
        return new Response(json_encode($api_call_status));
    }

}
