<?php

namespace App\Controller;

use App\Entity\Portal\Employee;
use App\Entity\Portal\FileDetail;
use App\Entity\Portal\Organization;
use App\Entity\Masters\Ministry;
use App\Entity\Portal\OrganizationUnit;
use App\Services\DefaultValue;
use App\Services\ImageProcess;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;
use App\Services\XMPPGeneral;
use Doctrine\DBAL\FetchMode;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Predis\Client;

class DashboardController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $xmppGeneral;
    private $defaultValue;
    private $imageProcess;
    private $request_stack;
    private $usession;
    private $authlogger;

    public function __construct(RequestStack $requestStack, DefaultValue $defVal, XMPPGeneral $xmpp, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, ImageProcess $imageProcess, SessionInterface $usession, LoggerInterface $authenticationLogger,  Client $redis)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->xmppGeneral = $xmpp;
        $this->defaultValue = $defVal;
        $this->imageProcess = $imageProcess;
        $this->usession = $usession;
        $this->request_stack = $requestStack;
        $this->authlogger = $authenticationLogger;
        $this->redis = $redis;
    }

    /**
     * @Route("/dash/dashboard", name="app_dashboard")
     */
    public function dashboard(Request $request): Response
    {
        $swpd_session_val = $this->usession->get('swpd');
        $this->authlogger->info('SWPD Status ' . $swpd_session_val);
        if ($swpd_session_val == "SANDES_WEB_PARICHAY_DIRECT") {
            return $this->redirectToRoute('app_gims_web_start');
        }
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $loggedUser = $this->getUser();
        $userOU = 0;
        $targetpath = substr($request->getSession()->get('_security.main.target_path'), -8);

        // Syncing stale roles
        if (!in_array($this->profileWorkspace->getProfile()->getRole()->getRole(), $loggedUser->getRoles()) || !$loggedUser->getRoles()) {
            $em = $this->getDoctrine()->getManager();
            $loggedUser->setRoles([$this->profileWorkspace->getProfile()->getRole()->getRole()]);
            // $loggedUser->isEqualto($loggedUser);
            $em->persist($loggedUser);
            $em->flush();
        }
        // Syncing stale roles ends 
        if ($loggedUser) {
            if ($loggedUser->hasRole('ROLE_SUPER_ADMIN') or $loggedUser->hasRole('ROLE_SUPERVISOR') or $loggedUser->hasRole('ROLE_OU_ADMIN') or $loggedUser->hasRole('ROLE_O_ADMIN') or $loggedUser->hasRole('ROLE_MINISTRY_ADMIN') or $loggedUser->hasRole('ROLE_OU_SUPERVISOR') or $loggedUser->hasRole('ROLE_SYSTEM_ADMIN')) {
                $organization = null;
                $ou = null;
                $ministry = null;
                if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
                    $ministry = $this->profileWorkspace->getMinistry();
                }
                if ($loggedUser->hasRole('ROLE_O_ADMIN')) {
                    $organization = $this->profileWorkspace->getOrganization();
                }
                if ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
                    $ou = $this->profileWorkspace->getOU();
                }
                if ($loggedUser->hasRole('ROLE_GROUP_ADMIN')) {
                    $ou = $this->profileWorkspace->getOU();
                }
                if ($targetpath === "startweb") {
                    return $this->redirectToRoute('app_gims_web_start');
                }
                return $this->render('dashboard/dashboard_su.html.twig', ['organization' => $organization, 'ou' => $ou, 'ministry' => $ministry]);
            } elseif ($loggedUser->hasRole('ROLE_MEMBER')) {
                if ($targetpath === "startweb") {
                    return $this->redirectToRoute('app_gims_web_start');
                }
                return $this->redirectToRoute('app_dashboard_dlink');
            }
        }

        return $this->render('dashboard/dashboard.html.twig');
    }

    /**
     * @Route("/dash/emailer", name="app_dashboard_emailer")
     */
    public function dashboardMailer(): Response
    {
        return $this->render('dashboard/dashboard_mailer.html.twig');
    }

    /**
     * @Route("/dash/uphoto", name="app_dash_uphoto")
     */
    public function userPhoto(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $loggedUser = $this->getUser();
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($loggedUser);
        $employeePhoto = $this->getDoctrine()->getRepository(FileDetail::class)->findOneById($employee->getPhoto());
        // $encPhoto = \base64_encode(stream_get_contents($employeePhoto->getFileData()));
        $encPhoto = $employeePhoto->getThumbnail();
        if (null == $encPhoto) {
            $encPhoto = $this->imageProcess->generateThumbnail(stream_get_contents($employeePhoto->getFileData()));
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
     * @Route("/dash/dbdatachats", name="app_dashboard_data_chats")
     */
    public function dashboardDataChats(): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $dbCon = $em->getConnection();
        $userOU = 0;
        $ministry = 0;
        $organization = 0;
        $qryMinistryCount = 0;
        $qryMA = $em->createQuery("SELECT SUM(ma.messageCount),  MAX(ma.dateHour) FROM App:Dashboard\MessageActivity ma");
        $qryMACount = $qryMA->getResult();

        if ($loggedUser) {
            // Message Activity Count
            if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
                $ministry = $this->profileWorkspace->getMinistry()->getId();
                $qryMA = $dbCon->prepare("SELECT SUM(ma.message_count),  MAX(ma.date_hour) FROM report.message_activity_org as ma INNER JOIN gim.organization as o ON ma.organization_id = o.id WHERE o.ministry_id = :ministry OR :ministry = 0");
                $qryMA->bindValue('ministry', $ministry);
                $qryMA->execute();
                $qryMACounts = $qryMA->fetchAll(FetchMode::NUMERIC);
                $qryMACount = $qryMACounts[0][0];
                $updateTime = $qryMACounts[0][1];
            }
            if ($loggedUser->hasRole('ROLE_O_ADMIN')) {
                $userOU = $this->profileWorkspace->getOrganization()->getId();
                $qryMA = $em->createQuery("SELECT SUM(ma.messageCount),  MAX(ma.dateHour) FROM App:Dashboard\MessageActivityOrganization ma WHERE ma.organization = :ou OR :ou = 0");
                $qryMA->setParameter('ou', $userOU);
                $qryMACounts = $qryMA->getResult();
                $qryMACount = $qryMACounts[0][1];
                $updateTime = $qryMACounts[0][2];
            }
            if ($loggedUser->hasRole('ROLE_OU_ADMIN') or $loggedUser->hasRole('ROLE_OU_SUPERVISOR')) {
                $userOU = $this->profileWorkspace->getOu()->getId();
                $qryMA = $em->createQuery("SELECT SUM(ma.messageCount),  MAX(ma.dateHour) FROM App:Dashboard\MessageActivityOrganizationUnit ma WHERE ma.organizationUnit = :ou OR :ou = 0");
                $qryMA->setParameter('ou', $userOU);
                $qryMACounts = $qryMA->getResult();
                $qryMACount = $qryMACounts[0][1];
                $updateTime = $qryMACounts[0][2];
            }
            if ($loggedUser->hasRole('ROLE_SUPER_ADMIN') or $loggedUser->hasRole('ROLE_SUPERVISOR')) {
                $qryMA = $em->createQuery("SELECT SUM(ma.messageCount),  MAX(ma.dateHour) FROM App:Dashboard\MessageActivityOrganizationUnit ma WHERE ma.organizationUnit = :ou OR :ou = 0");
                $qryMA->setParameter('ou', $userOU);
                $qryMACounts = $qryMA->getResult();
                $qryMACount = $qryMACounts[0][1];
                $updateTime = $qryMACounts[0][2];
            }
            // Onboard Count
            if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
                $ministry = $this->profileWorkspace->getMinistry()->getId();
                $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.employee as e INNER JOIN gim.organization_unit as ou  ON e.ou_id = ou.ou_id INNER JOIN gim.organization as o  ON ou.organization_id = o.id WHERE o.ministry_id = :ministry OR :ministry = 0");
                $qrychat->bindValue(':ministry', $ministry);
                $qrychat->execute();
                $qryOnboardCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];
            } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
                $userOU = $this->profileWorkspace->getOrganization()->getId();
                $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.employee as e INNER JOIN gim.organization_unit as ou  ON e.ou_id = ou.ou_id WHERE ou.organization_id = :organization OR :organization = 0");
                $qrychat->bindValue(':organization', $userOU);
                $qrychat->execute();
                $qryOnboardCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];
            } else {
                $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.employee as e  WHERE e.ou_id = :ou OR :ou = 0");
                $qrychat->bindValue(':ou', $userOU);
                $qrychat->execute();
                $qryOnboardCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];
            }

            // Registration Count
            if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
                $ministry = $this->profileWorkspace->getMinistry()->getId();
                $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.employee as e INNER JOIN gim.organization_unit as ou  ON e.ou_id = ou.ou_id INNER JOIN gim.organization as o  ON ou.organization_id = o.id WHERE e.registered = 'Y' AND o.ministry_id = :ministry OR :ministry = 0");
                $qrychat->bindValue(':ministry', $ministry);
                $qrychat->execute();
                $qryRegistrationCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];
            } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
                $userOU = $this->profileWorkspace->getOrganization()->getId();
                $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.employee as e INNER JOIN gim.organization_unit as ou  ON e.ou_id = ou.ou_id WHERE e.registered = 'Y' AND ou.organization_id = :organization OR :organization = 0");
                $qrychat->bindValue(':organization', $userOU);
                $qrychat->execute();
                $qryRegistrationCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];
            } else {
                $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.employee as e WHERE e.registered = 'Y' AND (e.ou_id = :ou OR :ou = 0)");
                $qrychat->bindValue(':ou', $userOU);
                $qrychat->execute();
                $qryRegistrationCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];
            }
            // Ministries Count
            if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
                $qryMinistryCount = 0;
            } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
                $qryMinistryCount = 0;
            } elseif ($loggedUser->hasRole('ROLE_SUPER_ADMIN') or $loggedUser->hasRole('ROLE_SUPERVISOR')) {
                $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.masters_ministries as min");
                $qrychat->execute();
                $qryMinistryCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];
            }
            // Organization Count
            $ministry = -1;
            if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
                $ministry = $this->profileWorkspace->getMinistry()->getId();
            } elseif ($loggedUser->hasRole('ROLE_SUPER_ADMIN') or $loggedUser->hasRole('ROLE_SUPERVISOR')) {
                $ministry = 0;
            }
            $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.organization as o  WHERE o.ministry_id = :ministry OR :ministry = 0");
            $qrychat->bindValue(':ministry', $ministry);
            $qrychat->execute();
            $qryOrganizationCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];

            // Organization Unit Count
            if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
                $ministry = $this->profileWorkspace->getMinistry()->getId();
                $organization = 0;
            } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
                $ministry = $this->profileWorkspace->getMinistry()->getId();
                $organization = $this->profileWorkspace->getOrganization()->getId();
            } elseif ($loggedUser->hasRole('ROLE_SUPER_ADMIN') or $loggedUser->hasRole('ROLE_SUPERVISOR')) {
                $ministry = 0;
                $organization = 0;
            }

            $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.organization_unit as ou  INNER JOIN gim.organization as o ON ou.organization_id = o.id WHERE (o.id = :organization OR :organization = 0) AND (o.ministry_id = :ministry OR :ministry = 0)");
            $qrychat->bindValue(':organization', $organization);
            $qrychat->bindValue(':ministry', $ministry);
            $qrychat->execute();
            $qryOUCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];

            $onlineUserCount = $this->xmppGeneral->getOnlineUsers();
            if (!$onlineUserCount) {
                $onlineUserCount = '';
            }
        } else {
            // Message Activity Count
            $qryMA = $em->createQuery("SELECT SUM(ma.messageCount),  MAX(ma.dateHour) FROM App:Dashboard\MessageActivityOrganizationUnit ma WHERE ma.organizationUnit = :ou OR :ou = 0");
            $qryMA->setParameter('ou', $userOU);
            $qryMACounts = $qryMA->getResult();
            $qryMACount = $qryMACounts[0][1];
            $updateTime = $qryMACounts[0][2];

            // Onboard Count
            $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.employee as e  WHERE e.ou_id = :ou OR :ou = 0");
            $qrychat->bindValue(':ou', $userOU);
            $qrychat->execute();
            $qryOnboardCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];

            // Registration Count
            $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.employee as e WHERE e.registered = 'Y' AND (e.ou_id = :ou OR :ou = 0)");
            $qrychat->bindValue(':ou', $userOU);
            $qrychat->execute();
            $qryRegistrationCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];

            // Ministries Count
            $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.masters_ministries as min");
            $qrychat->execute();
            $qryMinistryCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];

            // Organization Count
            $ministry = 0;
            $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.organization as o  WHERE o.ministry_id = :ministry OR :ministry = 0");
            $qrychat->bindValue(':ministry', $ministry);
            $qrychat->execute();
            $qryOrganizationCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];

            // Organization Unit Count
            $ministry = 0;
            $organization = 0;

            $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.organization_unit as ou  INNER JOIN gim.organization as o ON ou.organization_id = o.id WHERE (o.id = :organization OR :organization = 0) AND (o.ministry_id = :ministry OR :ministry = 0)");
            $qrychat->bindValue(':organization', $organization);
            $qrychat->bindValue(':ministry', $ministry);
            $qrychat->execute();
            $qryOUCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];

            // Online User Count
            $qrychat = $dbCon->prepare("SELECT COUNT(1) FROM gim.organization_unit as ou  INNER JOIN gim.organization as o ON ou.organization_id = o.id WHERE (o.id = :organization OR :organization = 0) AND (o.ministry_id = :ministry OR :ministry = 0)");
            $qrychat->bindValue(':organization', $organization);
            $qrychat->bindValue(':ministry', $ministry);
            $qrychat->execute();
            $qryOUCount = $qrychat->fetchAll(FetchMode::NUMERIC)[0];

            $onlineUserCount = $this->xmppGeneral->getOnlineUsers();
            if (!$onlineUserCount) {
                $onlineUserCount = '';
            }
        }

        return new JsonResponse(['OLUCount' => $onlineUserCount, 'OCount' => $qryOrganizationCount, 'LAU' => $updateTime, 'OUCount' => $qryOUCount, 'ECount' => $qryOnboardCount, 'ERCount' => $qryRegistrationCount, 'MCount' => $qryMACount,  'MinCount' => $qryMinistryCount,]);
    }

    
    /**
     * @Route("/dash/mview", name="app_dashboard_ministry_wise")
     */
    public function ministry(Request $request): Response
    {
        return $this->render('dashboard/dashboard_ministry.html.twig');
    }
    /**
     * @Route("/dash/mlist", name="app_dashboard_ministry_list")
     */
    public function ministryList(Request $request, PaginatorInterface $paginator): Response
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        //         $dql = <<<SQL
        //         SELECT t.*,gm.gu_id as mgu_id, gm.ministry_name FROM gim.masters_ministries as gm LEFT JOIN 
        //         (select  m.gu_id,               
        //                 max(oc) as onboarded_count,
        //                 max(rc) as registered_count,
        //                 sum(d.group_count) as group_count, 
        //                 max(d.active_users) as active_users, 
        //                 sum(d.total_messages) as total_messages, 
        //                 to_char(max(d.update_time),'DD-MM-YYYY HH24:MI:SS') as update_time
        //         from report.drill_throughs_test as d join gim.organization_unit as ou on d.ou_id = ou.ou_id 
        //                 right join gim.organization as o ON o.id = ou.organization_id 
        //                 join gim.masters_ministries m on m.id = o.ministry_id
        //                 JOIN (SELECT ministry_id,count(1) as oc, count(CASE registered WHEN  'Y' THEN 1 ELSE NULL END) as rc FROM gim.employee as e INNER JOIN gim.organization_unit as ou on e.ou_id = ou.ou_id INNER JOIN gim.organization as o ON ou.organization_id = o.id GROUP BY ministry_id) as etc ON etc.ministry_id = m.id 
        //         group by m.gu_id, m.ministry_name) as t ON gm.gu_id = t.gu_id
        //         order by COALESCE(total_messages,0) DESC
        // SQL;
        ##### New sql by paras #####
        $dql = <<<SQL
            SELECT
            m_gu_id AS mgu_id,
            ministry_name,
            SUM(onboarded_count) AS onboarded_count,
            SUM(registered_count) AS registered_count,
            SUM(group_count) AS group_count,
            MAX(active_users) AS active_users,
            SUM(total_messages) AS total_messages,
            TO_CHAR(MAX(update_time), 'DD-MM-YYYY HH24:MI:SS') AS update_time
        FROM 
            report.ou_drillthrough
        GROUP BY
            m_gu_id,
            ministry_name
        ORDER BY
            COALESCE(SUM(total_messages), 0) DESC;
        SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll();

        $pagination = $paginator->paginate($qrychatResult, $request->query->getInt('page', 1), 20);
        $pagination->setUsedRoute('app_dashboard_ministry_list');

        return $this->render('dashboard/_ministry_list.html.twig', ['paged_records' => $pagination]);
    }

    /**
     * @Route("/dash/oview", name="app_dashboard_owise")
     */
    public function organization(Request $request): Response
    {
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $user_ministry = $request->request->get('objid');
        } else {
            $user_ministry = $this->profileWorkspace->getMinistry()->getGuId();
        }
        return $this->render('dashboard/dashboard_organization.html.twig', ['ministry' => $user_ministry]);
    }

    /**
     * @Route("/dash/olist", name="app_dashboard_organization_list")
     */
    public function organizationList(Request $request, PaginatorInterface $paginator): Response
    {
        $mgid = $request->request->get('objid');
        if (!$mgid) {
            $mreq = $this->request_stack->getMasterRequest();
            $mgid = $mreq->request->get('objid');
            if (!$mgid) {
                $mgid = $this->profileWorkspace->getMinistry()->getGuId();
            }
        }
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        //         $dql = <<<SQL
        //         SELECT rpt.*,gmo.gu_id as ogu_id,gmo.organization_code,gmo.o_name,gm.ministry_code,gm.ministry_name FROM gim.organization as gmo INNER JOIN gim.masters_ministries as gm ON gmo.ministry_id = gm.id
        //         LEFT JOIN(
        //         select  o.gu_id,
        //                 max(oc) as onboarded_count,
        //                 max(rc) as registered_count,
        //                 sum(d.group_count) as group_count, 
        //                 max(d.active_users) as active_users, 
        //                 sum(d.total_messages) as total_messages, 
        //                 to_char(max(d.update_time),'DD-MM-YYYY HH24:MI:SS') as update_time
        //         from report.drill_throughs_test as d join gim.organization_unit as ou on d.ou_id = ou.ou_id 
        //                 right join gim.organization as o ON o.id = ou.organization_id 
        //                 join gim.masters_ministries m on m.id = o.ministry_id
        //                 JOIN (SELECT organization_id,count(1) as oc, count(CASE registered WHEN  'Y' THEN 1 ELSE NULL END) as rc FROM gim.employee as e INNER JOIN gim.organization_unit as ou on e.ou_id = ou.ou_id GROUP BY organization_id) as etc ON etc.organization_id = o.id 
        //         where m.gu_id = :mgid 
        //         group by o.o_name, o.gu_id, m.ministry_name
        //         ) as rpt ON rpt.gu_id = gmo.gu_id
        //         WHERE gm.gu_id = :mgid
        //         order by COALESCE(total_messages,0) DESC
        // SQL;
        ##### New sql by paras #####
        $dql = <<<SQL
            SELECT 
            o_gu_id AS ogu_id,
            o_name,
            o_code AS organization_code,
            ministry_code,
            ministry_name,
            SUM(onboarded_count) AS onboarded_count,
            SUM(registered_count) AS registered_count,
            SUM(group_count) AS group_count,
            MAX(active_users) AS active_users,
            SUM(total_messages) AS total_messages,
            TO_CHAR(MAX(update_time), 'DD-MM-YYYY HH24:MI:SS') AS update_time
        FROM 
            report.ou_drillthrough
        WHERE
            m_gu_id = :mgid
        GROUP BY
            o_gu_id,
            o_name,
            o_code,
            ministry_code,
            ministry_name
        ORDER BY
            COALESCE(SUM(total_messages), 0) DESC;
        SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':mgid', $mgid);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll();

        $pagination = $paginator->paginate($qrychatResult, $request->query->getInt('page', 1), 100);
        $pagination->setUsedRoute('app_dashboard_organization_list');

        return $this->render('dashboard/_organization_list.html.twig', ['paged_records' => $pagination]);
    }

    /**
     * @Route("/dash/ouview", name="app_dashboard_ou_wise")
     */
    public function organizationUnit(Request $request): Response
    {
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $organization = $request->request->get('objid');
        } else {
            $organization = $this->profileWorkspace->getOrganization()->getGuId();
        }

        return $this->render('dashboard/dashboard_oua.html.twig', ['organization' => $organization]);
    }
    /**
     * @Route("/dash/oulist", name="app_dashboard_ou_list")
     */
    public function organizationUnitList(Request $request, PaginatorInterface $paginator): Response
    {
        $ogid = $request->request->get('objid');
        if (!$ogid) {
            $mreq = $this->request_stack->getMasterRequest();
            $ogid = $mreq->request->get('objid');
            if (!$ogid) {
                $ogid = $this->profileWorkspace->getOrganization()->getGuId();
            }
        }

        $em = $this->getDoctrine()->getManager();
        $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($ogid);
        $myCon = $em->getConnection();
        //         $dql = <<<SQL
        //         SELECT rpt.*,gmo.o_name,gm.ministry_name, gmou.ou_name  
        //         FROM gim.organization_unit as gmou 
        //         INNER JOIN gim.organization as gmo ON gmo.id = gmou.organization_id
        //         INNER JOIN gim.masters_ministries as gm ON gmo.ministry_id = gm.id
        //         LEFT JOIN(
        //         SELECT
        //             ou.gu_id,
        //             ou.ou_name AS ou_name,
        //             ou.ou_code AS ou_code,
        //             max(oc) as onboarded_count,
        //             max(rc) as registered_count,
        //             sum(d.group_count) AS group_count,
        //             max(d.active_users) AS active_users,
        //             sum(d.total_messages) AS total_messages,
        //             to_char(max(d.update_time), 'DD-MM-YYYY HH24:MI:SS') AS update_time
        //         FROM
        //             report.drill_throughs_test AS d
        //             JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
        //             JOIN gim.organization AS o ON o.id = ou.organization_id
        //             JOIN (SELECT ou_id,count(1) as oc, count(CASE registered WHEN  'Y' THEN 1 ELSE NULL END) as rc FROM gim.employee as e GROUP BY ou_id) as etc ON etc.ou_id = ou.ou_id 
        //         where o.gu_id = :ogid 
        //         GROUP BY
        //             ou.ou_code,
        //             ou.ou_name,
        //             ou.gu_id
        //         ) as rpt ON rpt.gu_id = gmou.gu_id
        //         WHERE gmo.gu_id = :ogid
        //         order by COALESCE(total_messages,0) DESC
        // SQL;
        ##### New sql by paras #####
        $dql = <<<SQL
            SELECT 
            ou_gu_id AS gu_id,
            ou_name,
            SUM(onboarded_count) AS onboarded_count,
            SUM(registered_count) AS registered_count,
            SUM(group_count) AS group_count,
            MAX(active_users) AS active_users,
            SUM(total_messages) AS total_messages,
            TO_CHAR(MAX(update_time), 'DD-MM-YYYY HH24:MI:SS') AS update_time
        FROM 
            report.ou_drillthrough
        WHERE
            o_gu_id = :ogid
        GROUP BY
            ou_gu_id,
            ou_name
        ORDER BY
            COALESCE(SUM(total_messages), 0) DESC;
        SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll();

        $pagination = $paginator->paginate($qrychatResult, $request->query->getInt('page', 1), 100);
        $pagination->setUsedRoute('app_dashboard_ou_list');

        return $this->render('dashboard/_organization_unit_list.html.twig', ['paged_records' => $pagination, 'ogid' => $ogid, 'organization' => $Organization->getOrganizationName()]);
    }

    /**
     * @Route("/dash/emailtm/{objid}", name="app_dashboard_email_total_messages")
     */
    public function emailTotalMessages(Request $request, $objid): Response
    {
        $ogid = $objid;
        if (!$ogid) {
            // This would handle Sortlink click on List pages
            // As part of security audit clearance, we are not supposed to store DB keys
            // in URLs.. so we just store lastreceived objid into session
            // Session store process is happening inside Request Listener
            $session = new Session();
            $ogid = $session->get('lastobjid');
        }
        $em = $this->getDoctrine()->getManager();
        $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($ogid);
        $myCon = $em->getConnection();
        $dql = <<<SQL
                
                select  ou.gu_id,
                ou.ou_name as ou_name,
                max(oc) as onboarded_count,
                max(rc) as registered_count,
                sum(d.group_count) as group_count,
                max(d.active_users) as active_users,
                sum(COALESCE(d.total_messages,0)) as total_messages,
                max(COALESCE(td.today_messages,0)) as today_messages,
                to_char(max(d.update_time),'DD-MM-YYYY HH24:MI:SS') as update_time
        from report.drill_throughs_test as d join gim.organization_unit as ou on d.ou_id = ou.ou_id
        join gim.organization as o ON o.id = ou.organization_id
        left join (select dt.ou_id, total_messages as today_messages from report.drill_throughs_test as dt inner join gim.organization_unit as tdou on dt.ou_id = tdou.ou_id
        join gim.organization as tdo ON tdo.id = tdou.organization_id where tdo.gu_id = :ogid and dt.report_date = DATE 'yesterday') as td on td.ou_id = d.ou_id
        JOIN (
        SELECT e.ou_id,count(1) as oc, count(CASE registered WHEN  'Y' THEN 1 ELSE NULL END) as rc FROM gim.employee as e 
        INNER JOIN gim.organization_unit AS ou ON e.ou_id = ou.ou_id join gim.organization org on org.id=ou.organization_id where org.gu_id =:ogid and organization_id<>1 and e.account_status='V' and account_type='U' GROUP BY e.ou_id
        union 
        SELECT e.ou_id,count(1) as oc, count(CASE registered WHEN  'Y' THEN 1 ELSE NULL END) as rc FROM gim.employee as e INNER JOIN gim.organization_unit AS ou ON e.ou_id = ou.ou_id 
        			join gim.organization org on org.id=ou.organization_id
                    inner join stage.employee_detail ed on ed.mobile_number =e.mobile_no where org.gu_id =:ogid and organization_id=1 and e.account_status='V' and account_type='U' and e.ou_id<>45 GROUP BY e.ou_id
        ) as etc ON etc.ou_id = ou.ou_id
        where o.gu_id = :ogid AND ou.is_published = true
        group by ou.ou_name,ou.gu_id
        order by today_messages desc
SQL;
        //         $dql = <<<SQL

        //         select  ou.gu_id,
        //                 ou.ou_name as ou_name,
        //                 max(oc) as onboarded_count,
        //                 max(rc) as registered_count,
        //                 sum(d.group_count) as group_count, 
        //                 max(d.active_users) as active_users, 
        //                 sum(COALESCE(d.total_messages,0)) as total_messages, 
        //                 max(COALESCE(td.today_messages,0)) as today_messages, 
        //                 to_char(max(d.update_time),'DD-MM-YYYY HH24:MI:SS') as update_time
        //         from report.drill_throughs_test as d join gim.organization_unit as ou on d.ou_id = ou.ou_id 
        //         join gim.organization as o ON o.id = ou.organization_id
        //         left join (select dt.ou_id, total_messages as today_messages from report.drill_throughs_test as dt inner join gim.organization_unit as tdou on dt.ou_id = tdou.ou_id 
        //         join gim.organization as tdo ON tdo.id = tdou.organization_id where tdo.gu_id = :ogid and dt.report_date = DATE 'yesterday') as td on td.ou_id = d.ou_id
        //         JOIN (SELECT ou_id,count(1) as oc, count(CASE registered WHEN  'Y' THEN 1 ELSE NULL END) as rc FROM gim.employee as e GROUP BY ou_id) as etc ON etc.ou_id = ou.ou_id 
        //         where o.gu_id = :ogid AND ou.is_published = true
        //         group by ou.ou_name,ou.gu_id
        //         order by today_messages DESC
        // SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll();

        return $this->render('dashboard/_email_total_messages.html.twig', ['records' => $qrychatResult, 'organization' => $Organization->getOrganizationName()]);
    }

    /**
     * @Route("/dash/emailrgc/{objid}", name="app_dashboard_email_rgc")
     */
    public function emailRegistrations(Request $request, $objid): Response
    {
        $ogid = $objid;
        if (!$ogid) {
            // This would handle Sortlink click on List pages
            // As part of security audit clearance, we are not supposed to store DB keys
            // in URLs.. so we just store lastreceived objid into session
            // Session store process is happening inside Request Listener
            $session = new Session();
            $ogid = $session->get('lastobjid');
        }
        $em = $this->getDoctrine()->getManager();
        $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($ogid);
        $myCon = $em->getConnection();
        $dql = <<<SQL
        select  ou.gu_id,
                ou.ou_name as ou_name,
                max(COALESCE(oc,0)) as onboarded_count,
                max(COALESCE(rc,0)) as registered_count,
                sum(d.group_count) as group_count, 
                max(d.active_users) as active_users, 
                sum(COALESCE(d.total_messages,0)) as total_messages, 
                max(COALESCE(td.today_messages,0)) as today_messages, 
                CASE WHEN sum(COALESCE(d.registered_count,0)) > 0 THEN sum(COALESCE(d.total_messages,0))/sum(COALESCE(d.registered_count,0))  ELSE 0 END as perc,
                to_char(max(d.update_time),'DD-MM-YYYY HH24:MI:SS') as update_time
        from report.drill_throughs_test as d join gim.organization_unit as ou on d.ou_id = ou.ou_id 
        join gim.organization as o ON o.id = ou.organization_id
        left join (select dt.ou_id, total_messages as today_messages from report.drill_throughs_test as dt inner join gim.organization_unit as tdou on dt.ou_id = tdou.ou_id 
        join gim.organization as tdo ON tdo.id = tdou.organization_id where tdo.gu_id = :ogid and dt.report_date = DATE 'yesterday') as td on td.ou_id = d.ou_id
        JOIN (SELECT ou_id,count(1) as oc, count(CASE registered WHEN  'Y' THEN 1 ELSE NULL END) as rc FROM gim.employee as e GROUP BY ou_id) as etc ON etc.ou_id = ou.ou_id 
        where o.gu_id = :ogid 
        group by ou.ou_name,ou.gu_id
        order by perc DESC
SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll();

        return $this->render('dashboard/_email_rgc.html.twig', ['records' => $qrychatResult, 'organization' => $Organization->getOrganizationName()]);
    }

    /**
     * @Route("/dash/daytm", name="app_dashboard_day_total_messages")
     */
    public function dayTotalMessages(Request $request): Response
    {
        $ogid = $request->request->get('objid');
        if (!$ogid) {
            // This would handle Sortlink click on List pages
            // As part of security audit clearance, we are not supposed to store DB keys
            // in URLs.. so we just store lastreceived objid into session
            // Session store process is happening inside Request Listener
            $session = new Session();
            $ogid = $session->get('lastobjid');
        }
        $em = $this->getDoctrine()->getManager();
        $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($ogid);
        $myCon = $em->getConnection();
        $dql = <<<SQL
                
        select  ou.gu_id,
                ou.ou_name as ou_name,
                max(oc) as onboarded_count,
                max(rc) as registered_count,
                sum(d.group_count) as group_count, 
                max(d.active_users) as active_users, 
                sum(COALESCE(d.total_messages,0)) as total_messages, 
                max(COALESCE(td.today_messages,0)) as today_messages, 
                to_char(max(d.update_time),'DD-MM-YYYY HH24:MI:SS') as update_time
        from report.drill_throughs_test as d join gim.organization_unit as ou on d.ou_id = ou.ou_id 
        join gim.organization as o ON o.id = ou.organization_id
        left join (select dt.ou_id, total_messages as today_messages from report.drill_throughs_test as dt inner join gim.organization_unit as tdou on dt.ou_id = tdou.ou_id 
        join gim.organization as tdo ON tdo.id = tdou.organization_id where tdo.gu_id = :ogid and dt.report_date = DATE 'today') as td on td.ou_id = d.ou_id
        JOIN (SELECT ou_id,count(1) as oc, count(CASE registered WHEN  'Y' THEN 1 ELSE NULL END) as rc FROM gim.employee as e GROUP BY ou_id) as etc ON etc.ou_id = ou.ou_id 
        where o.gu_id = :ogid
        group by ou.ou_name,ou.gu_id
        order by today_messages DESC
SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);

        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll();

        return $this->render('dashboard/_web_total_messages.html.twig', ['records' => $qrychatResult, 'organization' => $Organization->getOrganizationName()]);
    }

    /**
     * @Route("/dash/dayrgc", name="app_dashboard_day_rgc")
     */
    public function dayRegistrations(Request $request): Response
    {
        $ogid = $request->request->get('objid');
        $date_range = $request->request->get('input-daterange');
        if ($date_range) {
            $dates = explode(' - ', $date_range);
            $date_from = \DateTimeImmutable::createFromFormat('d/m/Y', $dates[0]);
            $date_to = \DateTimeImmutable::createFromFormat('d/m/Y', $dates[1]);
        } else {
            $date_from = new \DateTimeImmutable('2020-01-10');
            $date_to = new \DateTimeImmutable('now');
        }
        if (!$ogid) {
            // This would handle Sortlink click on List pages
            // As part of security audit clearance, we are not supposed to store DB keys
            // in URLs.. so we just store lastreceived objid into session
            // Session store process is happening inside Request Listener
            $session = new Session();
            $ogid = $session->get('lastobjid');
        }
        $em = $this->getDoctrine()->getManager();
        $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($ogid);
        $myCon = $em->getConnection();
        $dql = <<<SQL
        select  ou.gu_id,
                ou.ou_name as ou_name,
                max(COALESCE(oc,0)) as onboarded_count,
                max(COALESCE(rc,0)) as registered_count,
                sum(d.group_count) as group_count, 
                max(d.active_users) as active_users, 
                sum(COALESCE(d.total_messages,0)) as total_messages, 
                max(COALESCE(td.today_messages,0)) as today_messages, 
                CASE WHEN sum(COALESCE(d.registered_count,0)) > 0 THEN sum(COALESCE(d.total_messages,0))/sum(COALESCE(d.registered_count,0))  ELSE 0 END as perc,
                to_char(max(d.update_time),'DD-MM-YYYY HH24:MI:SS') as update_time
        from report.drill_throughs_test as d join gim.organization_unit as ou on d.ou_id = ou.ou_id 
        join gim.organization as o ON o.id = ou.organization_id
        left join (select dt.ou_id, total_messages as today_messages from report.drill_throughs_test as dt inner join gim.organization_unit as tdou on dt.ou_id = tdou.ou_id 
        join gim.organization as tdo ON tdo.id = tdou.organization_id where tdo.gu_id = :ogid and dt.report_date = DATE 'today') as td on td.ou_id = d.ou_id
        JOIN (SELECT ou_id,count(1) as oc, count(CASE registered WHEN  'Y' THEN 1 ELSE NULL END) as rc FROM gim.employee as e GROUP BY ou_id) as etc ON etc.ou_id = ou.ou_id 
        where o.gu_id = :ogid AND d.update_time >= :fromdate AND d.update_time < :todate
        group by ou.ou_name,ou.gu_id
        order by perc DESC
SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll();

        return $this->render('dashboard/_web_rgc.html.twig', ['records' => $qrychatResult, 'organization' => $Organization]);
    }

    /**
     * @Route("/dash/memberised", name="app_dashboard_memberised")
     */
    public function memberised(Request $request): Response
    {
        $objid = $request->request->get('objid');
        return $this->render('dashboard/dashboard_memberised.html.twig', ['objid' => $objid]);
    }

    /**
     * @Route("/dash/memberisedlist", name="app_dashboard_memberised_list")
     */
    public function memberisedList(Request $request, PaginatorInterface $paginator): Response
    {
        // Handling direct AJAX call
        $objid = $request->request->get('objid');
        if (!$objid) {
            $mreq = $this->request_stack->getMasterRequest();
            // Handling direct rendering
            $objid = $mreq->request->get('objid');
            // Handling OU Admin call
            if (!$objid) {
                $objid = $this->profileWorkspace->getOu()->getGuId();
            }
        }

        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneByGuId($objid);
        $ffield = $request->request->get('filterField');
        $fvalue = $request->request->get('filterValue');
        $FemployeeName = 'NOFILTER';

        if (!empty($ffield) and ('employee_name' === $ffield)) {
            $FemployeeName = '%' . $fvalue . '%';
        }

        $sqlMS = <<<SQLMS
        SELECT  e.employee_code, 
                e.employee_name, 
                e.email_address, 
                e.designation_name, 
                COALESCE(m.message_count, 0) as message_count, 
                COALESCE(last_activity, '') as last_activity, 
                e.gu_id,
                e.registered,
                mv.os, mv.app_version
        FROM 
            (select a.ou_id, a.gu_id, a.id as eId,  a.employee_code as employee_code, name as employee_name, a.email as email_address, d.designation_name, a.photo, d.designation_code, d.sort_order, a.registered
            from gim.employee a join gim.designation d on d.id=a.designation_code join gim.organization_unit o on o.ou_id=a.ou_id  
            where o.gu_id = :guid AND (a.name ILIKE :FemployeeName OR :FemployeeName = 'NOFILTER')) as e
            LEFT JOIN 
            (select emp_id, sum(message_count) as message_count, to_char(max(date_hour),'DD-MM-YYYY HH24:MI:SS') as last_activity
            from report.message_activity_emp r join gim.organization_unit o on o.ou_id=r.ou_id
            where o.gu_id = :guid 
            group by emp_id) as m ON e.eId = m.emp_id
            LEFT JOIN  
            gim.file_detail f on f.id=e.photo
            LEFT JOIN gim.user_app_device mv on mv.emp_id = eId
            order by e.sort_order, e.employee_name
SQLMS;

        $qrychat = $myCon->prepare($sqlMS);
        $qrychat->bindValue('guid', $objid);
        $qrychat->bindValue('FemployeeName', $FemployeeName);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll();
        $pagination = $paginator->paginate($qrychatResult, $request->query->getInt('page', 1), 48);
        $pagination->setUsedRoute('app_dashboard_memberised_list');
        $pagination->setSortableTemplate('/bases/knp_sortable_link_dashboard.html.twig');
        $pagination->setFiltrationTemplate('/bases/knp_filtration_dashboard.html.twig');

        return $this->render('dashboard/_memberised_list.html.twig', ['paged_records' => $pagination, 'ou' => $OrganizationUnit]);
    }

    /**
     * @Route("/dash/onlineusers", name="app_dashboard_online_users")
     */
    public function onlineUsers(Request $request): JsonResponse
    {
        return new JsonResponse($this->xmppGeneral->getOnlineUsers());
    }

    /**
     * @Route("/dash/gdonline", name="app_dashboard_gdata_online")
     */
    public function dashboardGraphDataOnline(): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $dql = <<<SQL
            select  to_char(log_time::timestamptz,'YYYY-MM-DD HH24:MI:SS'),
                    cnt
            from report.active_user_log 
            WHERE log_time::timestamptz > NOW() - INTERVAL '48 hour' 
            order by log_time
SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll(FetchMode::NUMERIC);
        $gdata = 'Date/Time, Online Users' . PHP_EOL;
        foreach ($qrychatResult as $row) {
            $gdata .= $row[0] . ',' . $row[1] . PHP_EOL;
        }

        return new JsonResponse($gdata);
    }

    /**
     * @Route("/dash/gddwonline", name="app_dashboard_gdata_dwonline")
     */
    public function dashboardGraphDataDWOnline(): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $dql = <<<SQL
        select  to_char(log_time::timestamptz,'YYYY-MM-DD') as message_date,
                max(cnt) as message_count
        from report.active_user_log m 
        where log_time >= date_trunc('month', now()) - interval '2 month' 
        group by message_date
        order by message_date
SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll(FetchMode::NUMERIC);

        $gdata = 'Date/Time, Online Users' . PHP_EOL;
        foreach ($qrychatResult as $row) {
            $gdata .= $row[0] . ',' . $row[1] . PHP_EOL;
        }

        return new JsonResponse($gdata);
    }

    /**
     * @Route("/dash/gdchats", name="app_dashboard_gdata_chats")
     */
    public function dashboardGraphDataChats(): JsonResponse
    {
        $loggedUser = $this->getUser();
        $userOU = 0;
        if ($loggedUser) {
            if ($loggedUser and $loggedUser->hasRole('ROLE_OU_ADMIN') or $loggedUser->hasRole('ROLE_OU_SUPERVISOR')) {
                $userOU = $this->profileWorkspace->getOu()->getId();
            }
        }
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $dql = <<<SQL
            select  to_char(date_hour::timestamptz,'YYYY-MM-DD HH24:MI:SS') as message_date,
                    message_count
            from report.message_activity_ou m
            WHERE date_hour::timestamptz > NOW() - INTERVAL '48 hour' 
            AND (ou_id = :userOU OR :userOU = 0 )
            order by message_date
SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':userOU', $userOU);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll(FetchMode::NUMERIC);
        $gdata = 'Date/Time, Message Count' . PHP_EOL;
        foreach ($qrychatResult as $row) {
            $gdata .= $row[0] . ',' . $row[1] . PHP_EOL;
        }

        return new JsonResponse($gdata);
    }

    /**
     * @Route("/dash/gdau", name="app_dashboard_graph_active_users")
     */
    public function dashboardGraphActiveUsers(): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $dql = <<<SQL
            select  to_char(activity_hour::timestamptz,'YYYY-MM-DD HH24:MI:SS') as message_date, active_employee_count
            from report.active_users_hourly m
            WHERE activity_hour::timestamptz > NOW() - interval '2 month'
            order by activity_hour
SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll(FetchMode::NUMERIC);
        $gdata = 'Date, Users' . PHP_EOL;
        foreach ($qrychatResult as $row) {
            $gdata .= $row[0] . ',' . $row[1] . PHP_EOL;
        }

        return new JsonResponse($gdata);
    }

    /**
     * @Route("/dash/gddwchats", name="app_dashboard_gdata_dwchats")
     */
    public function dashboardGraphDataDWChats(): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $userOU = 0;
        if ($loggedUser) {
            if ($loggedUser and $loggedUser->hasRole('ROLE_OU_ADMIN') or $loggedUser->hasRole('ROLE_OU_SUPERVISOR')) {
                $userOU = $this->profileWorkspace->getOu()->getId();
            }
        }

        $myCon = $em->getConnection();
        $dql = <<<SQL
        select  to_char(date_hour,'YYYY-MM-DD') as message_date,
                sum(message_count) as message_count
        from report.message_activity_ou m 
        where ou_id = :userOU OR :userOU = 0 
        and date_hour >= date_trunc('month', now()) - interval '2 month'
        group by message_date
        order by message_date
SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':userOU', $userOU);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll(FetchMode::NUMERIC);

        $gdata = 'Date/Time, Message Count' . PHP_EOL;
        foreach ($qrychatResult as $row) {
            $gdata .= $row[0] . ',' . $row[1] . PHP_EOL;
        }

        return new JsonResponse($gdata);
    }

    /**
     * @Route("/dash/gdrd", name="app_dashboard_graph_data_registration_daily")
     */
    public function dashboardGraphDataRegistrationDaily(): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $userOU = 0;
        if ($loggedUser) {
            if ($loggedUser and $loggedUser->hasRole('ROLE_OU_ADMIN') or $loggedUser->hasRole('ROLE_OU_SUPERVISOR')) {
                $userOU = $this->profileWorkspace->getOu()->getId();
            }
        }

        $myCon = $em->getConnection();
        $dql = <<<SQL
        select  to_char(reg_date,'YYYY-MM-DD') as reg_date,
                sum(reg_count) as reg_count
        from report.registration_activity_ou m 
        where ou_id = :userOU OR :userOU = 0 
        and reg_date >= date_trunc('month', now()) - interval '12 month'
        group by reg_date
        order by reg_date
SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':userOU', $userOU);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll(FetchMode::NUMERIC);

        $gdata = 'Date/Time, Registration Count' . PHP_EOL;
        foreach ($qrychatResult as $row) {
            $gdata .= $row[0] . ',' . $row[1] . PHP_EOL;
        }

        return new JsonResponse($gdata);
    }

    /**
     * @Route("/dash/pivot/home/{objid}", name="app_dashboard_pivot_home")
     */
    public function dashboardPivotHome(Request $request, $objid): Response
    {
        if ($objid === '00000000-0000-4000-0000-000000000000') {
            $objid = $this->profileWorkspace->getOu()->getGuId();
        }
        $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneByGuId($objid);
        return $this->render('dashboard/pivot_view.html.twig', ['ou' => $OrganizationUnit]);
    }

    /**
     * @Route("/dash/pivot/load", name="app_dashboard_pivot_load")
     */
    public function dashboardPivotLoad(Request $request): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $date_range = $request->request->get('dateRange');
        $dates = explode(' - ', $date_range);
        $date_from = \DateTimeImmutable::createFromFormat('d/m/Y', $dates[0]);
        $date_to = \DateTimeImmutable::createFromFormat('d/m/Y', $dates[1]);
        $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneByGuId($objid);
        $myCon = $em->getConnection();
        $dql = <<<SQL
            SELECT COALESCE(rpt.name, em.name), COALESCE(rpt.md,'INACTIVE'), COALESCE(rpt.mc,0) FROM gim.employee as em LEFT JOIN
            (SELECT e.name,to_char(date_hour, 'MM-DD') as md, sum(message_count) as mc 
            FROM report.message_activity_emp as r inner join gim.employee as e ON e.id = r.emp_id  AND e.account_type= 'U' 
            WHERE r.ou_id = :ou  AND date_hour >= :fromdate AND date_hour < :todate group by e.name, to_char(date_hour, 'MM-DD')) as rpt
            ON rpt.name = em.name WHERE em.ou_id = :ou ORDER BY 3 DESC LIMIT 1000
SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ou', $OrganizationUnit->getId());
        $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll(FetchMode::NUMERIC);
        $gdata = 'Employee,Day,Count' . PHP_EOL;
        foreach ($qrychatResult as $row) {
            $gdata .= $row[0] . ',' . $row[1] . ',' . $row[2] . PHP_EOL;
        }

        return new JsonResponse($gdata);
    }

    /**
     * @Route("/dash/swtchRle", name="app_dashboard_switch_role")
     */
    public function switchRole(Request $request)
    {
        $objid = $request->request->get('objid');
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $profile = $em->getRepository('App:Portal\Profile')->findOneBy(['user' => $user, 'isEnabled' => true, 'isCurrent' => false, 'guId' => $objid]);
        $currentProfile = $em->getRepository('App:Portal\Profile')->findOneBy(['user' => $user, 'isEnabled' => true, 'isCurrent' => true]);
        if ($profile) {
            if ($currentProfile) {
                $currentProfile->setIscurrent(false);
            }
            $profile->setIscurrent(true);
            $luserid = $user->getId();
            if ($luserid === 8 || $luserid === 35 || $luserid === 47 || $luserid === 46 || $luserid === 39) {
                $user->setRoles(['ROLE_MEMBER', 'ROLE_SYSTEM_ADMIN', $profile->getRole()->getRole()]);
            } else {
                $user->setRoles(['ROLE_MEMBER', $profile->getRole()->getRole()]);
            }
            // $user->isEqualto($user);
            $em->flush();
            // Paras - Drop redis session
            $user = $this->getUser();
            $sessionKey = 'user:' . $user->getId() . ':session';
            $this->redis->del($sessionKey);
            return new JsonResponse(['status' => 'success', 'path' => $this->generateUrl('app_dashboard')]);
        }
        return new JsonResponse(['status' => 'error']);

        // $objid = $request->request->get('objid');
        // $role = $request->request->get('role');
        // $user = $this->getUser();
        // $em = $this->getDoctrine()->getManager();
        // $roleObj = $em->getRepository('App:Portal\Roles')->findOneByRole($role);
        // $selectedOuObj = $em->getRepository('App:Portal\OrganizationUnit')->findOneByGuId($objid);
        // $profile = $em->getRepository('App:Portal\Profile')->findOneBy(['user' => $loggedUser, 'isEnabled' => true, 'organizationUnit' => $selectedOuObj, 'role' => $roleObj, 'isCurrent' => false]);
        // $currentProfile = $em->getRepository('App:Portal\Profile')->findOneBy(['user' => $loggedUser, 'isEnabled' => true, 'isCurrent' => true]);
        // if ($profile) {
        //     if ($currentProfile) {
        //         $currentProfile->setIscurrent(false);
        //     }
        //     $profile->setIscurrent(true);
        //     $luserid = $loggedUser->getId();
        //     if ($luserid === 8 || $luserid === 35 || $luserid === 47){
        //         $loggedUser->setRoles(['ROLE_MEMBER','ROLE_SYSTEM_ADMIN', $role]);
        //     } else {
        //         $loggedUser->setRoles(['ROLE_MEMBER', $role]);
        //     }
        //     $loggedUser->isEqualto($loggedUser);
        //     $em->persist($loggedUser);
        //     $em->flush();
        //     return new JsonResponse(['status' => 'success', 'path' => $this->generateUrl('app_dashboard')]);
        // }
        // return new JsonResponse(['status' => 'error']);
    }


    /**
     * @Route("/ptl/downloadCsvGeneric/index", name="dashboard_download_csv_generic")
     */
    public function downloadCsvGeneric(Request $request): Response
    {
        $arr = [];
        $arr['custom_filter_param'] = $request->request->get('custom_filter_param');
        $arr['actionPath'] = $request->request->get('actionPath');
        $arr['cust_field_val'] = $request->request->get('cust_field_val');
        return $this->render('dashboard/downloadCsvGeneric.html.twig', ['arr' => $arr]);
    }
}
