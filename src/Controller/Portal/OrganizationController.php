<?php

namespace App\Controller\Portal;

use App\Entity\Portal\Designation;
use App\Entity\Portal\EmployeeLevel;
use App\Entity\Portal\Organization;
use App\Entity\Portal\OrganizationUnit;
use App\Form\Portal\OrganizationType;
use App\Services\ProfileWorkspace;
use App\Services\XMPPGeneral;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrganizationController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;
    private $xmppGeneral;

    public function __construct(ProfileWorkspace $profileWorkspace, XMPPGeneral $xmpp)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->xmppGeneral = $xmpp;
    }

    /**
     * @Route("/portal/o/", name="portal_o_index")
     */
    public function index(): Response
    {
        $dfConfig=[];
        $loggedUser = $this->getUser();
        if (!$loggedUser->hasRole('ROLE_OU_ADMIN')) {
            if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
                $dfConfig = ([['field_alias' => 'organization_type', 'display_text' => 'Organization Type', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
                ['field_alias' => 'organization_code', 'display_text' => 'Organization Code', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
                ['field_alias' => 'organization_name', 'display_text' => 'Organization', 'operator_type' => ['=', 'ILIKE'], 'input_type' => 'codefinder', 'input_schema' => 'Organization'],
                ['field_alias' => 'ministry', 'display_text' => 'Ministry', 'operator_type' => ['=', 'ILIKE'], 'input_type' => 'codefinder', 'input_schema' => 'Ministry'],
            ]);
            } else {
                $dfConfig = ([['field_alias' => 'organization_type', 'display_text' => 'Organization Type', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
                ['field_alias' => 'organization_code', 'display_text' => 'Organization Code', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
                ['field_alias' => 'organization_name', 'display_text' => 'Organization', 'operator_type' => ['=', 'ILIKE'], 'input_type' => 'codefinder', 'input_schema' => 'Organization'],
            ]);
            }
        }
        return $this->render('portal/organization/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
     * @Route("/portal/o/list", name="portal_o_list")
     */
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);

        $query = $this->processQry($dynamicFilters);        
        $designationPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 20);  
        $designationPaginated->setUsedRoute('portal_o_list'); 
        
        return $this->render('portal/organization/_list.html.twig', ['pagination' => $designationPaginated]);

//         $em = $this->getDoctrine()->getManager();
//         $myCon = $em->getConnection();
//         $ffield = $request->query->get('filterField');
//         $fvalue = $request->query->get('filterValue');
//         $FOName = 'NOFILTER';
//         $FOUCode = 'NOFILTER';
//         if (!empty($ffield) and ('o_name' === $ffield)) {
//             $FOName = '%'.$fvalue.'%';
//         }
//         if (!empty($ffield) and ('organization_code' === $ffield)) {
//             $FOUCode = '%'.$fvalue.'%';
//         }

//         $sqlMS = <<<SQLMS
//         SELECT  o.id,
//                 o.gu_id as gu_id,
//                 o.o_name as o_name,
//                 o.organization_code as organization_code,
//                 o.vhost as vhost,
//                 ot.description as organizationType,
//                 mm.ministry_code as ministrycode
//         FROM gim.organization as o
//             LEFT JOIN gim.organization_type as ot ON o.organization_type_id = ot.code
//             LEFT JOIN gim.masters_ministries as mm ON o.ministry_id = mm.id
//         WHERE   (o.o_name ILIKE :FOName OR :FOName = 'NOFILTER') AND
//                 (o.organization_code ILIKE :FOUCode OR :FOUCode = 'NOFILTER')
//         ORDER BY o.id
// SQLMS;

// $qryList = $myCon->prepare($sqlMS);
// $qryList->bindValue('FOName', $FOName);
// $qryList->bindValue('FOUCode', $FOUCode);
// $qryList->execute();
// $qryOU = $qryList->fetchAll();

//         // $dql = "SELECT a FROM App:Portal\Organization a ".$where." ORDER BY a.id DESC";
//         // $qryOU = $em->createQuery($dql);

//         $pagination = $paginator->paginate($qryOU, $request->query->getInt('page', 1), 20);
//         $pagination->setUsedRoute('portal_o_list');

//         return $this->render('portal/organization/_list.html.twig', array('paged_records' => $pagination));
    }

    /**
     * @Route("/portal/o/new", name="portal_o_new")
     */
    public function new(): Response
    {
        $Organization = new Organization();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $Organization->setGuId($uuid->toString());
        $form = $this->createForm(OrganizationType::class, $Organization, ['action' => $this->generateUrl('portal_o_ins'), 'attr' => ['id' => 'frmBaseModal']])->add('btnInsert', SubmitType::class, ['label' => 'Save']);
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $ministry = null;
        } else {
            $ministry = $this->profileWorkspace->getMinistry();
        }

        return $this->render('portal/organization/_form_new.html.twig', [
            'form' => $form->createView(), 'ministry' => $ministry,
        ]);
    }

    /**
     * @Route("/portal/o/ins", name="portal_o_ins")
     */
    public function insert(Request $request): Response
    {
        $Organization = new Organization();
        $form = $this->createForm(OrganizationType::class, $Organization, ['action' => $this->generateUrl('portal_o_ins'), 'attr' => ['id' => 'frmBaseModal']])->add('btnInsert', SubmitType::class, ['label' => 'Save']);
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $ministry = null;
        } else {
            $ministry = $this->profileWorkspace->getMinistry();
        }
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
                    $ministry = $em->getRepository("App:Masters\Ministry")->findOneById($form['ministry']->getData());
                } else {
                    $ministry = $this->profileWorkspace->getMinistry();
                }
                $vhost = $em->getRepository("App:Masters\Vhost")->findOneById($form['vhostId']->getData());
                $Organization->setMinistry($ministry);
                $Organization->setVhost($vhost->getVhostUrl());
                $em->persist($Organization);
                $em->flush();
                $this->xmppGeneral->updateCache('organization', $Organization->getId());
                $this->xmppGeneral->refreshORGProfile($Organization->getId());
                return new Response(json_encode(['status' => 'success', 'message' => 'Saved Successfully']));
            }
        }

        $formView = $this->renderView('portal/organization/_form_new.html.twig', [
            'form' => $form->createView(), 'ministry' => $ministry,
        ]);

        return new Response(json_encode(['form' => $formView, 'status' => 'error']));
    }

    /**
     * @Route("/portal/o/edit", name="portal_o_edit")
     */
    public function edit(Request $request): Response
    {
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $Organization = $this->profileWorkspace->getOrganization();
            $objid = $Organization->getGuId();
            $ministry = $this->profileWorkspace->getMinistry();
            $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy(['guId' => $objid, 'ministry' => $ministry]);
        }
        else
        {
            $objid = $request->request->get('objid');            
            if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) 
            {
                $ministry = null;
                $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($objid);
            }
            else
            {
                $ministry = $this->profileWorkspace->getMinistry();
                $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy(['guId' => $objid, 'ministry' => $ministry]);
            }
        }
        $form = $this->createForm(OrganizationType::class, $Organization, ['action' => $this->generateUrl('portal_o_upd'), 'attr' => ['id' => 'frmBaseModal']])->add('btnUpdate', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);
        $loggedUser = $this->getUser();

        return $this->render('portal/organization/_form_edit.html.twig', [
            'form' => $form->createView(), 'ministry' => $ministry,
        ]);
    }

    /**
     * @Route("/portal/o/upd", name="portal_o_upd")
     */
    public function update(Request $request): Response
    {
        $objid = $request->request->get('objid');        
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $ministry = null;
            $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($objid);
        } else {
            $ministry = $this->profileWorkspace->getMinistry();
            $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy(['guId' => $objid, 'ministry' => $ministry]);
        }
       
        $form = $this->createForm(OrganizationType::class, $Organization, ['action' => $this->generateUrl('portal_o_upd'), 'attr' => ['id' => 'frmBaseModal']])->add('btnUpdate', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);
        
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
                    $ministry = $em->getRepository("App:Masters\Ministry")->findOneById($form['ministry']->getData());
                } else {
                    $ministry = $this->profileWorkspace->getMinistry();
                }
                $Organization->setMinistry($ministry);
                if (!($loggedUser->hasRole('ROLE_O_ADMIN'))) {
                    $vhost = $em->getRepository("App:Masters\Vhost")->findOneById($form['vhostId']->getData());
                    $Organization->setVhost($vhost->getVhostUrl());
                }
                $em->persist($Organization);
                $em->flush();
                $this->xmppGeneral->updateCache('organization', $Organization->getId());
                $this->xmppGeneral->refreshORGProfile($Organization->getId());
                return new Response(json_encode(['status' => 'success', 'message' => 'Updation successful']));
            }
        }

        $formView = $this->renderView('portal/organization/_form_edit.html.twig', [
            'form' => $form->createView(), 'ministry' => $ministry,
        ]);

        return new Response(json_encode(['form' => $formView, 'status' => 'error', 'message' => 'Error']));
    }

    /**
     * @Route("/portal/o/delete", name="portal_o_delete")
     */
    public function delete(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {            
            $ministry = null;
            $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($objid);

        } else {            
            $ministry = $this->profileWorkspace->getMinistry();
            $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy(['guId' => $objid, 'ministry' => $ministry]);
        }       

        return $this->render('portal/organization/_view.html.twig', [
            'Organization' => $Organization,
        ]);
    }

    /**
     * @Route("/portal/o/deleteconfirm", name="portal_o_delete_confirm")
     */
    public function deleteConfirm(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();        
        $loggedUser = $this->getUser();       
        $profile = $this->profileWorkspace->getProfile();
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $ministry = null;
            $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($objid);
        } else {
            $ministry = $this->profileWorkspace->getMinistry();
            $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy(['guId' => $objid, 'ministry' => $ministry]);
        }
        
   if (!$em->createQueryBuilder('ou')->select('COUNT(ou.id)')->from('App:Portal\OrganizationUnit', 'ou')->where('ou.organization = :organization')->setParameter(':organization', $Organization)->getQuery()->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR) > 0) {
            if ('POST' == $request->getMethod()) {
                $em = $this->getDoctrine()->getManager();
                $this->xmppGeneral->removeCache('organization', $Organization->getId());
                $em->remove($Organization);
                $em->flush();

                return new Response(json_encode(['status' => 'success', 'message' => 'Deletion successful']));
            }
        } else {
            return new Response(json_encode(['status' => 'error', 'message' => 'Action unsuccessful, Organization Units  are already assigned to this organization']));
        }
    }

    /**
     * @Route("/portal/o/getosbyministry", name="portal_o_get_os_by_ministry")
     */
    public function getOsByMinistry(Request $request): Response
    {
        $objid = $request->request->get('mVal');
        $em = $this->getDoctrine()->getManager();
        $Organizations = $em->getRepository(Organization::class)->findByMinistry($objid);

        $result = [];
        foreach ($Organizations as $Organization) {
            $guid = $Organization->getId();
            $oname = $Organization->getOrganizationName();
            $result[$guid] = $oname;
        }

        return new JsonResponse(['result' => $result]);
    }

    /**
     * @Route("/portal/o/getdddbyorganization", name="portal_get_ou_dg_el_by_organization")
     */
    public function getDDDByOrganization(Request $request): Response
    {
        $objid = $request->request->get('oVal');
        $em = $this->getDoctrine()->getManager();
        $organizationUnits = $em->getRepository(OrganizationUnit::class)->findByOrganization($objid);
        $ou = [];
        foreach ($organizationUnits as $organizationUnit) {
            $id = $organizationUnit->getId();
            $oname = $organizationUnit->getOUName();
            $ou[$id] = $oname;
        }

        $designations = $em->getRepository(Designation::class)->findByOrganization($objid);
        $dg = [];
        foreach ($designations as $designation) {
            $id = $designation->getId();
            $oname = $designation->getDesignationName();
            $dg[$id] = $oname;
        }

        $levels = $em->getRepository(EmployeeLevel::class)->findByOrganization($objid);
        $lv = [];
        foreach ($levels as $level) {
            $id = $level->getId();
            $oname = $level->getEmployeeLevelName();
            $lv[$id] = $oname;
        }

        return new JsonResponse(['ou' => $ou, 'dg' => $dg, 'lv' => $lv]);
    }

    /**
     * @Route("/get-org", name="get_organisations")
     */
    public function getOrganizations(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $ministryId = $request->request->get('ministryId');
        $qry = $em->createQueryBuilder('m.id,m.organizationName')
                ->select('m.id,m.organizationName')
                ->from('App:Portal\Organization', 'm')
                ->where('m.ministry = :min')
                ->setParameter('min', $ministryId);
        if (!$loggedUser->hasRole('ROLE_SUPER_ADMIN') && !$loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $org = $this->profileWorkspace->getOrganization()->getId();
            $qry->andWhere('m.id =:org')
            ->setParameter('org', $org);
        }
        $sboxResult = json_encode($qry->getQuery()->getResult());

        return new Response($sboxResult);
    }

    private function processQry($dynamicFilters = null)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $Organization = 0;
            $Ministry = 0;
        } elseif ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $Organization = 0;
            $Ministry = $this->profileWorkspace->getMinistry()->getId();
        } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $Organization = $this->profileWorkspace->getOrganization()->getId();
            $Ministry = 0;
        } else {
            $Organization = 0;
            $Ministry = 0;
        }
        $fieldAliases = ['organization_type' => 'ot.organizationTypeName', 'organization_name' => 'o.organizationName', 'ministry' => 'mm.ministryName', 'organization_code' => 'o.organizationCode'];
        
            $quer = $em->createQueryBuilder('e')
                ->select('o.id,o.guId as gu_id,o.organizationName as o_name, o.organizationCode as organization_code,o.vhost, ot.organizationTypeName as organizationtype, mm.ministryCode as ministrycode')
                ->from('App:Portal\Organization', 'o')
                ->leftJoin('App:Masters\OrganizationType', 'ot', 'WITH', 'o.organizationType = ot.id')
                ->leftJoin('App:Masters\Ministry', 'mm', 'WITH', 'o.ministry = mm.id')
                ->Where('o.ministry = :min OR :min = 0')
                ->setParameter('min', $Ministry);
            if ($loggedUser->hasRole('ROLE_O_ADMIN')) {
                $quer->andWhere('o.id = :org OR :org = 0')
                 >setParameter('org', $Organization);
            }            
            $quer->orderBy('o.id');
       
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
     * @Route("/list/oos", name="list_of_organizations")
     */
    public function listOfOrganizations(): Response
    {
        $em = $this->getDoctrine()->getManager();
        $strs = "";
        $myCon = $em->getConnection();
        $dql = <<<SQL
            Select gu_id
            from gim.organization as o inner join
                (select org_id, sum(reg_count)
                from report.registration_activity_org
                group by org_id
                having sum(reg_count) > 0 ) as r
                ON o.id = r.org_id
SQL;
        $qryorg = $myCon->prepare($dql);
        $qryorg->execute();
        $organizations = $qryorg->fetchAll();

        foreach ($organizations as $guid) {
            $strs .= $guid["gu_id"] .PHP_EOL;
        }

        return new Response($strs);
    }


    /**
     * @Route("/dash/emailtmorgwise", name="email_total_messages_orgwise")
     */
    public function emailTotalMessagesOrganizationWise(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $dql = <<<SQL
       SELECT
            mo.o_name AS organization,
            COALESCE(etc.onboarded_count, 0) AS onboarded_count,
            COALESCE(etc.registered_count, 0) AS registered_count,
            COALESCE(d.group_count, 0) AS group_count,
            COALESCE(d.active_users, 0) AS active_users,
            COALESCE(d.total_messages, 0) AS total_messages,
            COALESCE(td.today_messages, 0) AS today_messages,
            td.update_time AS update_time
        FROM
            gim.organization AS mo
            LEFT JOIN (
                SELECT
                    o.id,
                    sum(COALESCE(d.group_count, 0)) AS group_count,
                    max(COALESCE(d.active_users, 0)) AS active_users,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages
                FROM
                    report.drill_throughs_test AS d
                    JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                    JOIN gim.organization AS o ON o.id = ou.organization_id
                GROUP BY
                    o.id) AS d ON mo.id = d.id
            LEFT JOIN (
                SELECT
                    tdo.id,
                    sum(COALESCE(total_messages, 0)) AS today_messages,
                    to_char(max(dt.update_time), 'DD-MM-YYYY HH24:MI:SS') AS update_time
                FROM
                    report.drill_throughs_test AS dt
                    INNER JOIN gim.organization_unit AS tdou ON dt.ou_id = tdou.ou_id
                    JOIN gim.organization AS tdo ON tdo.id = tdou.organization_id
                WHERE
                    dt.report_date = date 'yesterday'
                GROUP BY
                    tdo.id) AS td ON td.id = mo.id
            LEFT JOIN (
					SELECT
					    organization_id,
					    count(1) AS onboarded_count,
					    count(
					        CASE registered
					        WHEN 'Y' THEN
					            1
					        ELSE
					            NULL
					        END) AS registered_count
					FROM
					    gim.employee AS e
					    INNER JOIN gim.organization_unit AS ou ON e.ou_id = ou.ou_id where organization_id<>1 and e.account_status='V' and account_type='U'
					GROUP BY
					    organization_id
                union 
                    SELECT
                    organization_id,
                    count(1) AS onboarded_count,
                    count(
                        CASE registered
                        WHEN 'Y' THEN
                            1
                        ELSE
                            NULL
                        END) AS registered_count
                FROM
                    gim.employee AS e
                    INNER JOIN gim.organization_unit AS ou ON e.ou_id = ou.ou_id 
                    inner join stage.employee_detail ed on ed.mobile_number =e.mobile_no where organization_id=1 and e.account_status='V' and account_type='U' and e.ou_id<>45
                GROUP BY
                    organization_id) AS etc ON etc.organization_id = mo.id
        WHERE
            COALESCE(etc.onboarded_count, 0) > 0
        ORDER BY
            today_messages DESC,
            total_messages DESC,
            registered_count DESC,
            onboarded_count DESC
SQL;
//         $dql = <<<SQL
//         SELECT
//             mo.o_name AS organization,
//             COALESCE(etc.onboarded_count, 0) AS onboarded_count,
//             COALESCE(etc.registered_count, 0) AS registered_count,
//             COALESCE(d.group_count, 0) AS group_count,
//             COALESCE(d.active_users, 0) AS active_users,
//             COALESCE(d.total_messages, 0) AS total_messages,
//             COALESCE(td.today_messages, 0) AS today_messages,
//             td.update_time AS update_time
//         FROM
//             gim.organization AS mo
//             LEFT JOIN (
//                 SELECT
//                     o.id,
//                     sum(COALESCE(d.group_count, 0)) AS group_count,
//                     max(COALESCE(d.active_users, 0)) AS active_users,
//                     sum(COALESCE(d.total_messages, 0)) AS total_messages
//                 FROM
//                     report.drill_throughs_test AS d
//                     JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
//                     JOIN gim.organization AS o ON o.id = ou.organization_id
//                 GROUP BY
//                     o.id) AS d ON mo.id = d.id
//             LEFT JOIN (
//                 SELECT
//                     tdo.id,
//                     sum(COALESCE(total_messages, 0)) AS today_messages,
//                     to_char(max(dt.update_time), 'DD-MM-YYYY HH24:MI:SS') AS update_time
//                 FROM
//                     report.drill_throughs_test AS dt
//                     INNER JOIN gim.organization_unit AS tdou ON dt.ou_id = tdou.ou_id
//                     JOIN gim.organization AS tdo ON tdo.id = tdou.organization_id
//                 WHERE
//                     dt.report_date = date 'yesterday'
//                 GROUP BY
//                     tdo.id) AS td ON td.id = mo.id
//             LEFT JOIN (
//                 SELECT
//                     organization_id,
//                     count(1) AS onboarded_count,
//                     count(
//                         CASE registered
//                         WHEN 'Y' THEN
//                             1
//                         ELSE
//                             NULL
//                         END) AS registered_count
//                 FROM
//                     gim.employee AS e
//                     INNER JOIN gim.organization_unit AS ou ON e.ou_id = ou.ou_id
//                 GROUP BY
//                     organization_id) AS etc ON etc.organization_id = mo.id
//         WHERE
//             COALESCE(etc.onboarded_count, 0) > 0
//         ORDER BY
//             today_messages DESC,
//             total_messages DESC,
//             registered_count DESC,
//             onboarded_count DESC;
// SQL;
        $qrychat = $myCon->prepare($dql);
        $qrychat->execute();
        $qrychatResult = $qrychat->fetchAll();

        return $this->render('dashboard/_email_total_messages_organization_wise.html.twig', ['records' => $qrychatResult]);
    }

}
