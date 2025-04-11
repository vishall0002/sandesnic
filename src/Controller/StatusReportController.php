<?php

namespace App\Controller;

use App\Entity\Masters\Ministry;
use App\Entity\Portal\Organization;
use App\Entity\Portal\OrganizationUnit;
use App\Services\ProfileWorkspace;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatusReportController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;

    public function __construct(ProfileWorkspace $profileWorkspace)
    {
        $this->profileWorkspace = $profileWorkspace;
    }

    /**
     * @Route("/status/report/", name="status_report_index")
     */
    public function index(): Response
    {
        return $this->render('StatusReport/index.html.twig');
    }

    /**
     * @Route("/status/report/list", name="status_report_list")
     */
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $ffield = $request->query->get('filterField');
        $fvalue = $request->query->get('filterValue');

        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();

        $appSql = <<<SQLMS
        SELECT
        apps.id,
        app_name,
        app_title
        FROM gim.apps as apps
        WHERE apps.allow_portal_messaging=TRUE
        ORDER BY apps.app_name
SQLMS;

        $appQry = $myCon->prepare($appSql);
        $appQry->execute();

        $apps = $appQry->fetchAll();

        $choicesArray = [];
        foreach ($apps as $key => $app) {
            $choicesArray[$app['app_title']] = $app['id'];
        }

        $form = $this->createFormBuilder()
            ->add('filters', ChoiceType::class, [
                'choices' => $choicesArray,
                'placeholder' => 'Select Apps ',
                'label' => 'Filters',
                'required' => false,
                'attr' => [
                    'class' => 'listfilterApp searchable form-control-sm  mb-2 mr-sm-2',
                    'data-list-filter-path' => $this->generateUrl('status_report_list'),
                    'data-main-path' => $this->generateUrl('status_report_index'),
                ],
            ])
            ->getForm();

        $custom_filter_param = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');

        if ($custom_filter_param && 'undefined' != $custom_filter_param) {
            $qryListResult = $this->processQry($ffield, $fvalue, $custom_filter_param);
        } else {
            $qryListResult = [];
        }
        $Paginated = $paginator->paginate($qryListResult, $request->query->getInt('page', 1), 20);
        $Paginated->setUsedRoute('status_report_list');

        return $this->render('StatusReport/_list.html.twig', ['pagination' => $Paginated, 'filter' => $custom_filter_param, 'form' => $form->createView()]);
    }

    private function processQry($ffield, $fvalue, $custom_field)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $filterStr = '';
        if (!empty($ffield) and ('' != $fvalue)) {
            if ('applog.req_date' == $ffield) {
                $fvalue = strtotime($fvalue);
                $fvalue = date('Y-m-d', $fvalue);
                $filterStr = 'WHERE  to_char('.$ffield.", 'YYYY-MM-DD') LIKE '%".$fvalue."%'";
            } else {
                $filterStr = 'WHERE '.$ffield.' = '.$fvalue;
            }
        }
        if ($custom_field) {
            if (!$filterStr) {
                $filterStr .= ' WHERE ';
            } else {
                $filterStr .= ' AND ';
            }
            $filterStr .= ' applog.app_id = '.$custom_field;
        }

        $sqlMS = <<<SQLMS
        SELECT
            applog.id,
            applog.app_id, 	
            applog.req_type,
            regexp_replace(req_body, $$\u0000$$, '', 'g')::json->>'message' as message,
            regexp_replace(req_body, $$\u0000$$, '', 'g')::json->>'title' as title,
            to_char(applog.req_date, 'DD-MM-YYYY') as req_date,
            applog.dispatched_count, 
            applog.delivered_count,
            applog.read_count
        FROM gim.app_message_log as applog 
        $filterStr 
        ORDER BY applog.id DESC LIMIT 50
SQLMS;

        $qryList = $myCon->prepare($sqlMS);
        $qryList->execute();

        return $qryList->fetchAll();
    }

    /**
     * @Route("/dash/sdol/direct", name="status_device_os_list_direct")
     */
    public function SDOLDirect(Request $request)
    {
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $OU = null;
            $Organization = null;
            $Ministry = null;
        } elseif ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $OU = null;
            $Organization = null;
            $Ministry = $this->profileWorkspace->getMinistry();
        } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $OU = null;
            $Organization = $this->profileWorkspace->getOrganization();
            $Ministry = $this->profileWorkspace->getMinistry();
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $OU = $this->profileWorkspace->getOU();
            $Organization = $this->profileWorkspace->getOrganization();
            $Ministry = $this->profileWorkspace->getMinistry();
        }

        return $this->deviceOsList($Ministry, $Organization, $OU);
    }

    /**
     * @Route("/dash/sdol/min", name="status_device_os_list_ministry")
     */
    public function SDOLMinistry(Request $request)
    {
        $OU = null;
        $Organization = null;
        $Ministry = null;
        $objid = $request->request->get('objid');
        $Ministry = $this->getDoctrine()->getRepository(Ministry::class)->findOneByGuId($objid);
        if ($Ministry) {
            return $this->deviceOsList($Ministry, $Organization, $OU);
        } else {
            return null;
        }
    }

    /**
     * @Route("/dash/sdol/org", name="status_device_os_list_organization")
     */
    public function SDOLOrganization(Request $request)
    {
        $OU = null;
        $Organization = null;
        $Ministry = null;
        $objid = $request->request->get('objid');
        $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($objid);
        if ($Organization) {
            return $this->deviceOsList($Ministry, $Organization, $OU);
        } else {
            return null;
        }
    }

    /**
     * @Route("/dash/sdol/ou", name="status_device_os_list_ou")
     */
    public function SDOLOU(Request $request)
    {
        $OU = null;
        $Organization = null;
        $Ministry = null;
        $objid = $request->request->get('objid');
        $OU = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneByGuId($objid);
        if ($OU) {
            return $this->deviceOsList($Ministry, $Organization, $OU);
        } else {
            return null;
        }
    }

    /**
     * @Route("/dash/sdol", name="status_device_os_list")
     */
    public function deviceOsList($Ministry, $Organization, $OU): Response
    {
        $em = $this->getDoctrine()->getManager();
        $curIosUserCount = 0;
        $curAndroidUserCount = 0;
        $countIos = 0;
        $countAndroid = 0;
        $myCon = $em->getConnection();

        if ($Ministry) {
            $MinistryID = $Ministry->getId();
        } else {
            $MinistryID = 0;
        }
        if ($Organization) {
            $OrganizationID = $Organization->getId();
        } else {
            $OrganizationID = 0;
        }
        if ($OU) {
            $OUID = $OU->getId();
        } else {
            $OUID = 0;
        }

        //get current version  - iOS and Android
        $sqlMS = <<<SQLMS
            SELECT device_os, app_version
            FROM gim.app_version
            WHERE is_current = true AND device_os = 'iOS'
SQLMS;
        $currentIOSVersion = $myCon->prepare($sqlMS);
        $currentIOSVersion->execute();
        $currentIOSVersion = $currentIOSVersion->fetchAll();
        $curIosVersion = $currentIOSVersion[0]['app_version'];

        $sqlMS = <<<SQLMS
            SELECT device_os, app_version
            FROM gim.app_version
            WHERE is_current = true AND device_os = 'Android'
SQLMS;
        $currentAndroidVersion = $myCon->prepare($sqlMS);
        $currentAndroidVersion->execute();
        $currentAndroidVersion = $currentAndroidVersion->fetchAll();
        $curAndroidVersion = $currentAndroidVersion[0]['app_version'];

        //total count - ios and android
        $sqlMS = <<<SQLMS
            SELECT count(emp_id) as thecount
            FROM gim.user_app_device
            LEFT JOIN gim.employee e ON e.id = emp_id
            LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
            LEFT JOIN gim.organization o ON o.id = ou.organization_id
            LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id
            WHERE os= 'iOS' 
            AND (m.id = :min or :min = 0)
            AND (o.id = :org or :org = 0)
            AND (ou.ou_id = :ou or :ou = 0)
SQLMS;
        $TotalCount = $myCon->prepare($sqlMS);
        $TotalCount->bindValue('min', $MinistryID);
        $TotalCount->bindValue('org', $OrganizationID);
        $TotalCount->bindValue('ou', $OUID);
        $TotalCount->execute();
        $TotalCount = $TotalCount->fetchAll();
        if ([] != $TotalCount) {
            $countIos = $TotalCount[0]['thecount'];
        }

        //total count - ios and android
        $sqlMS = <<<SQLMS
                SELECT count(emp_id) as thecount
                FROM gim.user_app_device
                LEFT JOIN gim.employee e ON e.id = emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id
                WHERE os= 'Android' 
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
SQLMS;
        $TotalCount = $myCon->prepare($sqlMS);
        $TotalCount->bindValue('min', $MinistryID);
        $TotalCount->bindValue('org', $OrganizationID);
        $TotalCount->bindValue('ou', $OUID);
        $TotalCount->execute();
        $TotalCount = $TotalCount->fetchAll();
        if ([] != $TotalCount) {
            $countAndroid = $TotalCount[0]['thecount'];
        }

        //Get Count of updated iOS and Android users
        $sqlMS = <<<SQLMS
                SELECT app_version,os, count(emp_id) as thecount
                FROM gim.user_app_device
                LEFT JOIN gim.employee e ON e.id = emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id 
                WHERE os= 'iOS' AND app_version = '$curIosVersion' 
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                GROUP BY app_version,os
                ORDER BY app_version DESC
SQLMS;
        $currentIOSUsersCount = $myCon->prepare($sqlMS);
        $currentIOSUsersCount->bindValue('min', $MinistryID);
        $currentIOSUsersCount->bindValue('org', $OrganizationID);
        $currentIOSUsersCount->bindValue('ou', $OUID);
        $currentIOSUsersCount->execute();
        $currentIOSUsersCount = $currentIOSUsersCount->fetchAll();
        if ([] != $currentIOSUsersCount) {
            $curIosUserCount = $currentIOSUsersCount[0]['thecount'];
        }

        $sqlMS = <<<SQLMS
                SELECT app_version,os, count(emp_id) as thecount
                FROM gim.user_app_device
                LEFT JOIN gim.employee e ON e.id = emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id 
                WHERE os = 'Android' AND app_version = '$curAndroidVersion' 
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                GROUP BY app_version,os
                ORDER BY app_version DESC
SQLMS;
        $currentAndroidUsersCount = $myCon->prepare($sqlMS);
        $currentAndroidUsersCount->bindValue('min', $MinistryID);
        $currentAndroidUsersCount->bindValue('org', $OrganizationID);
        $currentAndroidUsersCount->bindValue('ou', $OUID);
        $currentAndroidUsersCount->execute();
        $currentAndroidUsersCount = $currentAndroidUsersCount->fetchAll();
        if ([] != $currentAndroidUsersCount) {
            $curAndroidUserCount = $currentAndroidUsersCount[0]['thecount'];
        }

        //total count - os
        $sqlMS = <<<SQLMS
            SELECT os,os_version,count(os)
            FROM gim.user_app_device
            LEFT JOIN gim.employee e ON e.id = emp_id
            LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
            LEFT JOIN gim.organization o ON o.id = ou.organization_id
            LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id
            WHERE (m.id = :min or :min = 0)
            AND (o.id = :org or :org = 0)
            AND (ou.ou_id = :ou or :ou = 0)
            GROUP BY (os, os_version)
            ORDER BY os, count(os) DESC
SQLMS;
        $OsCount = $myCon->prepare($sqlMS);
        $OsCount->bindValue('min', $MinistryID);
        $OsCount->bindValue('org', $OrganizationID);
        $OsCount->bindValue('ou', $OUID);
        $OsCount->execute();
        $OSList = $OsCount->fetchAll();
        if ([] == $OSList) {
            $OSList = 0;
        }

        //total count - app version
        $sqlMS = <<<SQLMS
            SELECT os, app_version, count(app_version)
            FROM gim.user_app_device
            LEFT JOIN gim.employee e ON e.id = emp_id
            LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
            LEFT JOIN gim.organization o ON o.id = ou.organization_id
            LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id
            WHERE (m.id = :min or :min = 0)
            AND (o.id = :org or :org = 0)
            AND (ou.ou_id = :ou or :ou = 0)
            GROUP BY app_version, os
            ORDER BY os,count(app_version) DESC
SQLMS;
        $appVersionCount = $myCon->prepare($sqlMS);
        $appVersionCount->bindValue('min', $MinistryID);
        $appVersionCount->bindValue('org', $OrganizationID);
        $appVersionCount->bindValue('ou', $OUID);
        $appVersionCount->execute();
        $VersionList = $appVersionCount->fetchAll();
        if ([] == $VersionList) {
            $VersionList = 0;
        }

        //total count - manufacturer
        $sqlMS = <<<SQLMS
            SELECT manufacturer, count(manufacturer)
            FROM gim.user_app_device
            LEFT JOIN gim.employee e ON e.id = emp_id
            LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
            LEFT JOIN gim.organization o ON o.id = ou.organization_id
            LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id
            WHERE (m.id = :min or :min = 0)
            AND (o.id = :org or :org = 0)
            AND (ou.ou_id = :ou or :ou = 0)
            GROUP BY manufacturer
            ORDER BY count(manufacturer) DESC
SQLMS;
        $manufactureCount = $myCon->prepare($sqlMS);
        $manufactureCount->bindValue('min', $MinistryID);
        $manufactureCount->bindValue('org', $OrganizationID);
        $manufactureCount->bindValue('ou', $OUID);
        $manufactureCount->execute();
        $BrandList = $manufactureCount->fetchAll();
        if ([] == $BrandList) {
            $BrandList = 0;
        }

        return $this->render('StatusReport/_device_os_list.html.twig', [
            'iOSTotalCount' => $countIos,
            'androidTotalCount' => $countAndroid,
            'OSList' => $OSList,
            'VersionList' => $VersionList,
            'BrandList' => $BrandList,
            'currentIosUsersCount' => $curIosUserCount,
            'currentAndroidUsersCount' => $curAndroidUserCount,
            'curVersionIOS' => $curIosVersion,
            'curVersionAndroid' => $curAndroidVersion,
            'Ministry' => $Ministry,
            'Organization' => $Organization,
            'OU' => $OU,
        ]);
    }

    /**
     * @Route("/dash/ul/{type}/{ver}", name="user_list")
     */
    public function userList(Request $request, $type, $ver = null): Response
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->get('objid');
        $OU = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneByGuId($objid);
        $OUID = $OU->getId();
        $OrganizationID = 0;
        $MinistryID = 0;
        $TotalUsersList = 0;
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        // if ($loggedUser->hasRole('ROLE_OU_ADMIN'))
        // {
        //     $OUID = $this->profileWorkspace->getOU()->getId();
        //     $OrganizationID = $this->profileWorkspace->getOrganization()->getId();
        //     $MinistryID = $this->profileWorkspace->getMinistry()->getId();
        // }
        $myCon = $em->getConnection();
        if ('iosUsers' == $type) {
            $sqlMS = <<<SQLMS
                SELECT e.id, e.gu_id, e.name, mv.app_version, mv.manufacturer, 
                mv.os, mv.os_version, e.email, e.mobile_no, ou.ou_name, o.o_name, m.ministry_name
                FROM gim.user_app_device mv
                LEFT JOIN gim.employee e ON e.id = mv.emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id
                WHERE os= 'iOS' 
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                ORDER BY e.name
SQLMS;
            $iosUserList = $myCon->prepare($sqlMS);
            $iosUserList->bindValue('min', $MinistryID);
            $iosUserList->bindValue('org', $OrganizationID);
            $iosUserList->bindValue('ou', $OUID);
            $iosUserList->execute();
            $TotalUsersList = $iosUserList->fetchAll();
            $label = 'Total iOS Users';
        }

        if ('androidUsers' == $type) {
            $sqlMS = <<<SQLMS
                SELECT e.id, e.gu_id, e.name, mv.app_version, mv.manufacturer, 
                mv.os, mv.os_version, e.email, e.mobile_no, ou.ou_name, o.o_name, m.ministry_name
                FROM gim.user_app_device mv
                LEFT JOIN gim.employee e ON e.id = mv.emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id
                WHERE os= 'Android' 
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                ORDER BY e.name
SQLMS;
            $androidUserList = $myCon->prepare($sqlMS);
            $androidUserList->bindValue('min', $MinistryID);
            $androidUserList->bindValue('org', $OrganizationID);
            $androidUserList->bindValue('ou', $OUID);
            $androidUserList->execute();
            $TotalUsersList = $androidUserList->fetchAll();
            $label = 'Total Android Users';
        } elseif ('iosCurUsers' == $type) {
            $sqlMS = <<<SQLMS
                SELECT e.id, e.gu_id, e.name, mv.app_version, mv.manufacturer, 
                mv.os, mv.os_version, e.email, e.mobile_no, ou.ou_name, o.o_name, m.ministry_name
                FROM gim.user_app_device mv
                LEFT JOIN gim.employee e ON e.id = mv.emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id 
                WHERE os = 'iOS' AND app_version = '$ver' 
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                ORDER BY e.name
SQLMS;
            $currentIosUsers = $myCon->prepare($sqlMS);
            $currentIosUsers->bindValue('min', $MinistryID);
            $currentIosUsers->bindValue('org', $OrganizationID);
            $currentIosUsers->bindValue('ou', $OUID);
            $currentIosUsers->execute();
            $TotalUsersList = $currentIosUsers->fetchAll();
            $label = 'Current iOS Users - iOS-'.$ver;
        } elseif ('androidCurUsers' == $type) {
            $sqlMS = <<<SQLMS
                SELECT e.id, e.gu_id, e.name, mv.app_version, mv.manufacturer, 
                mv.os, mv.os_version, e.email, e.mobile_no, ou.ou_name, o.o_name, m.ministry_name
                FROM gim.user_app_device mv
                LEFT JOIN gim.employee e ON e.id = mv.emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id 
                WHERE os = 'Android' AND app_version = '$ver' 
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                ORDER BY e.name
SQLMS;
            $currentAndroidUsers = $myCon->prepare($sqlMS);
            $currentAndroidUsers->bindValue('min', $MinistryID);
            $currentAndroidUsers->bindValue('org', $OrganizationID);
            $currentAndroidUsers->bindValue('ou', $OUID);
            $currentAndroidUsers->execute();
            $TotalUsersList = $currentAndroidUsers->fetchAll();
            $label = 'Current Android Users - Android-'.$ver;
        } elseif ('iosOldVerUsers' == $type) {
            $sqlMS = <<<SQLMS
                SELECT e.id, e.gu_id, e.name, mv.app_version, mv.manufacturer, 
                mv.os, mv.os_version, e.email, e.mobile_no, ou.ou_name, o.o_name, m.ministry_name
                FROM gim.user_app_device mv
                LEFT JOIN gim.employee e ON e.id = mv.emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id 
                WHERE os = 'iOS' AND app_version != '$ver' 
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                ORDER BY e.name
SQLMS;
            $oldIosUsers = $myCon->prepare($sqlMS);
            $oldIosUsers->bindValue('min', $MinistryID);
            $oldIosUsers->bindValue('org', $OrganizationID);
            $oldIosUsers->bindValue('ou', $OUID);
            $oldIosUsers->execute();
            $TotalUsersList = $oldIosUsers->fetchAll();
            $label = 'Not Updated iOS Users';
        } elseif ('androidOldVerUsers' == $type) {
            $sqlMS = <<<SQLMS
                SELECT e.id, e.gu_id, e.name, mv.app_version, mv.manufacturer, 
                mv.os, mv.os_version, e.email, e.mobile_no,ou.ou_name, o.o_name, m.ministry_name
                FROM gim.user_app_device mv
                LEFT JOIN gim.employee e ON e.id = mv.emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id 
                WHERE os = 'Android' AND app_version != '$ver' 
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                ORDER BY e.name
SQLMS;
            $oldAndroidUsers = $myCon->prepare($sqlMS);
            $oldAndroidUsers->bindValue('min', $MinistryID);
            $oldAndroidUsers->bindValue('org', $OrganizationID);
            $oldAndroidUsers->bindValue('ou', $OUID);
            $oldAndroidUsers->execute();
            $TotalUsersList = $oldAndroidUsers->fetchAll();
            $label = 'Not Updated Android Users';
        } elseif ('os' == $type) {
            $version = explode('-', $ver);
            $sqlMS = <<<SQLMS
                SELECT e.id, e.gu_id, e.name, mv.app_version, mv.manufacturer, 
                mv.os, mv.os_version, e.email, e.mobile_no, ou.ou_name, o.o_name, m.ministry_name
                FROM gim.user_app_device mv
                LEFT JOIN gim.employee e ON e.id = emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id
                WHERE mv.os = :os AND mv.os_version = :ver
                AND(m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                ORDER BY e.name
SQLMS;
            $os = $myCon->prepare($sqlMS);
            $os->bindValue('os', $version[0]);
            $os->bindValue('ver', $version[1]);
            $os->bindValue('min', $MinistryID);
            $os->bindValue('org', $OrganizationID);
            $os->bindValue('ou', $OUID);
            $os->execute();
            $TotalUsersList = $os->fetchAll();
            $label = 'Users List-Os Version - '.$version[0].'-'.$version[1];
        } elseif ('appVersion' == $type) {
            $version = explode('-', $ver);
            $sqlMS = <<<SQLMS
                SELECT e.id, e.gu_id, e.name, mv.app_version, mv.manufacturer, 
                mv.os, mv.os_version, e.email, e.mobile_no, ou.ou_name, o.o_name, m.ministry_name
                FROM gim.user_app_device mv
                LEFT JOIN gim.employee e ON e.id = emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id
                WHERE mv.os = :os AND mv.app_version = :app_ver
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                ORDER BY e.name
SQLMS;
            $appVersion = $myCon->prepare($sqlMS);
            $appVersion->bindValue('os', $version[0]);
            $appVersion->bindValue('app_ver', $version[1]);
            $appVersion->bindValue('min', $MinistryID);
            $appVersion->bindValue('org', $OrganizationID);
            $appVersion->bindValue('ou', $OUID);
            $appVersion->execute();
            $TotalUsersList = $appVersion->fetchAll();
            $label = 'Users List-App Version - '.$version[1];
        } elseif ('deviceBrand' == $type) {
            $sqlMS = <<<SQLMS
                SELECT e.id, e.gu_id, e.name, mv.app_version, mv.manufacturer, 
                mv.os, mv.os_version, e.email, e.mobile_no, ou.ou_name, o.o_name, m.ministry_name
                FROM gim.user_app_device mv
                LEFT JOIN gim.employee e ON e.id = emp_id
                LEFT JOIN gim.organization_unit ou ON ou.ou_id = e.ou_id
                LEFT JOIN gim.organization o ON o.id = ou.organization_id
                LEFT JOIN gim.masters_ministries m ON m.id = o.ministry_id
                WHERE mv.manufacturer = :manufacturer
                AND (m.id = :min or :min = 0)
                AND (o.id = :org or :org = 0)
                AND (ou.ou_id = :ou or :ou = 0)
                ORDER BY e.name
SQLMS;
            $deviceBrand = $myCon->prepare($sqlMS);
            $deviceBrand->bindValue('manufacturer', $ver);
            $deviceBrand->bindValue('min', $MinistryID);
            $deviceBrand->bindValue('org', $OrganizationID);
            $deviceBrand->bindValue('ou', $OUID);
            $deviceBrand->execute();
            $TotalUsersList = $deviceBrand->fetchAll();
            $label = 'Users List-Device Brand - '.$ver;
        }
        if ([] == $TotalUsersList) {
            $TotalUsersList = 0;
        }

        return $this->render('StatusReport/_users_list.html.twig', [
            'TotalUsersList' => $TotalUsersList,
            'label' => $label,
        ]);
    }
}
