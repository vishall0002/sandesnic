<?php

namespace App\Controller\Portal;

use App\Entity\Masters\GroupType;
use App\Entity\Portal\Group;
use App\Entity\Portal\OrganizationUnit;
use App\Form\Portal\OrganizationUnitType;
use App\Services\DefaultValue;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;
use App\Services\XMPPGroupV5;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\XMPPGeneral;

class OrganizationUnitController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $xmppGroupV5;
    private $defaultValue;
    private $metadata;
    private $profileWorkspace;

    public function __construct(DefaultValue $defVal, XMPPGroupV5 $xmpp, PortalMetadata $metadata, ProfileWorkspace $profileWorkspace, XMPPGeneral $xmppGen)
    {
        $this->xmppGroupV5 = $xmpp;
        $this->metadata = $metadata;
        $this->defaultValue = $defVal;
        $this->profileWorkspace = $profileWorkspace;
        $this->xmppGeneral = $xmppGen;
    }

    /**
     * @Route("/portal/ou/", name="portal_ou_index")
     */
    public function index(): Response
    {
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN') || $loggedUser->hasRole('ROLE_SUPERVISOR')) {
            $dfConfig = ([['field_alias' => "organization_type", 'display_text' => "Organization Type", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "ou_code", 'display_text' => "OUCode", 'operator_type' => ['ILIKE','='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "ou_name", 'display_text' => "OUName", 'operator_type' => ['ILIKE','='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "organization_name", 'display_text' => "Organization", 'operator_type' => ['=', 'ILIKE'], 'input_type' => "codefinder", 'input_schema' => 'Organization'],
                ['field_alias' => "ministry", 'display_text' => "Ministry", 'operator_type' => ['=', 'ILIKE'], 'input_type' => "codefinder", 'input_schema' => 'Ministry']
                
            ]);
        } elseif ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $dfConfig = ([['field_alias' => "organization_type", 'display_text' => "Organization Type", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "ou_code", 'display_text' => "OUCode", 'operator_type' => ['ILIKE','='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "ou_name", 'display_text' => "OUName", 'operator_type' => ['ILIKE','='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "organization_name", 'display_text' => "Organization", 'operator_type' => ['=', 'ILIKE'], 'input_type' => "codefinder", 'input_schema' => 'Organization']
            ]);
        } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $dfConfig = ([['field_alias' => "organization_type", 'display_text' => "Organization Type", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "ou_code", 'display_text' => "OUCode", 'operator_type' => ['ILIKE','='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "ou_name", 'display_text' => "OUName", 'operator_type' => ['ILIKE','='], 'input_type' => "text", 'input_schema' => '']
            ]);
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $dfConfig = ([['field_alias' => "organization_type", 'display_text' => "Organization Type", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "ou_code", 'display_text' => "OUCode", 'operator_type' => ['ILIKE','='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "ou_name", 'display_text' => "OUName", 'operator_type' => ['ILIKE','='], 'input_type' => "text", 'input_schema' => '']
            ]);
        }
       
        return $this->render('portal/organization_unit/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
     * @Route("/portal/ou/list", name="portal_ou_list")
     */
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);
        $query = $this->processQry($dynamicFilters);
        $ouPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
        $ouPaginated->setUsedRoute('portal_ou_list');
        return $this->render('portal/organization_unit/_list.html.twig', ['pagination' => $ouPaginated]);

//         $em = $this->getDoctrine()->getManager();
//         $myCon = $em->getConnection();

//         $loggedUser = $this->getUser();

//         if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
//             $oU = 0;
//         } else {
//             $oU = $this->profileWorkspace->getOu()->getId();
//         }
//         $ffield = $request->query->get('filterField');
//         $fvalue = $request->query->get('filterValue');
//         $FOTName = 'NOFILTER';
//         $FOName = 'NOFILTER';
//         $FOUName = 'NOFILTER';
//         $FOUCode = 'NOFILTER';

//         if (!empty($ffield) and ('ot_name' === $ffield)) {
//             $FOTName = '%'.$fvalue.'%';
//         }
//         if (!empty($ffield) and ('o_name' === $ffield)) {
//             $FOName = '%'.$fvalue.'%';
//         }
//         if (!empty($ffield) and ('ou_code' === $ffield)) {
//             $FOUCode = '%'.$fvalue.'%';
//         }
//         if (!empty($ffield) and ('ou_name' === $ffield)) {
//             $FOUName = '%'.$fvalue.'%';
//         }
//         $sqlMS = <<<SQLMS
//         SELECT  ou.ou_id,
//                 ou.gu_id,
//                 ot.description as ot_name,
//                 o.o_name as o_name,
//                 ou.ou_code as ou_code,
//                 ou.ou_name as ou_name
//         FROM gim.organization_unit as ou
//             LEFT JOIN gim.organization as o ON ou.organization_id = o.id
//             LEFT JOIN gim.organization_type as ot ON ou.ou_type = ot.code
//         WHERE   (ou.parent_ou = :ou OR :ou = 0 OR ou.ou_id = :ou) AND
//                 (ot.description ILIKE :FOTName OR :FOTName = 'NOFILTER') AND
//                 (o.o_name ILIKE :FOName OR :FOName = 'NOFILTER') AND
//                 (ou.ou_code ILIKE :FOUCode OR :FOUCode = 'NOFILTER') AND
//                 (ou.ou_name ILIKE :FOUName OR :FOUName = 'NOFILTER')
//         ORDER BY ou.ou_id
// SQLMS;

//         $qryList = $myCon->prepare($sqlMS);
//         $qryList->bindValue('ou', $oU);
//         $qryList->bindValue('FOTName', $FOTName);
//         $qryList->bindValue('FOName', $FOName);
//         $qryList->bindValue('FOUCode', $FOUCode);
//         $qryList->bindValue('FOUName', $FOUName);
//         $qryList->execute();
//         $qryListResult = $qryList->fetchAll();

//         $ousPaginated = $paginator->paginate($qryListResult, $request->query->getInt('page', 1), 20);
//         $ousPaginated->setUsedRoute('portal_ou_list');

//         return $this->render('portal/organization_unit/_list.html.twig', ['paged_records' => $ousPaginated]);
    }

    /**
     * @Route("/portal/ou/new", name="portal_ou_new")
     */
    public function new(): Response
    {
        $OrganizationUnit = new OrganizationUnit();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $OrganizationUnit->setGuId($uuid->toString());
        $loggedUser = $this->getUser();

        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $organization = null;
            $ministry = null;
            $organizationUnit = null;
        } else {
            $organization = $this->profileWorkspace->getOrganization();
            $ministry = $organization->getMinistry();
            $organizationUnit = $this->profileWorkspace->getOU();
        }

        $form = $this->createForm(OrganizationUnitType::class, $OrganizationUnit, ['profile' => $this->profileWorkspace->getProfile(), 'action' => $this->generateUrl('portal_ou_ins'), 'attr' => ['id' => 'frmBaseModal']])->add('btnInsert', SubmitType::class, ['label' => 'Save']);

        return $this->render('portal/organization_unit/_form_new.html.twig', [
            'form' => $form->createView(), 'organization' => $organization, 'ministry' => $ministry, 'organizationUnit' => $organizationUnit,
            ]);
    }

    /**
     * @Route("/portal/ou/ins", name="portal_ou_ins")
     */
    public function insert(Request $request): Response
    {
        $OrganizationUnit = new OrganizationUnit();
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $organization = null;
            $ministry = null;
            $organizationUnit = null;
        } else {
            $organization = $this->profileWorkspace->getOrganization();
            $ministry = $organization->getMinistry();
            $organizationUnit = $this->profileWorkspace->getOU();
        }
        $form = $this->createForm(OrganizationUnitType::class, $OrganizationUnit, ['profile' => $this->profileWorkspace->getProfile(), 'action' => $this->generateUrl('portal_ou_ins'), 'attr' => ['id' => 'frmBaseModal']])->add('btnInsert', SubmitType::class, ['label' => 'Save']);
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                if ($loggedUser->hasRole('ROLE_SUPER_ADMIN') or $loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
                    $organization = $em->getRepository("App:Portal\Organization")->findOneById($form['organization']->getData());
                }
                if ($loggedUser->hasRole('ROLE_O_ADMIN')) {
                    $organization = $this->profileWorkspace->getOrganization();
                }
                if ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
                    $organization = $this->profileWorkspace->getOrganization();
                    $OrganizationUnit->setParentOrganizationUnit($this->profileWorkspace->getOU());
                }
                $OrganizationUnit->setOrganization($organization);
                if ($OrganizationUnit->getParentOrganizationUnit()) {
                    $OrganizationUnit->setOUCode($OrganizationUnit->getParentOrganizationUnit()->getOUCode().'-'.$OrganizationUnit->getOUCode());
                }
                $metada = $this->metadata->getPortalMetadata('I');
                $OrganizationUnit->setInsertMetaData($metada->getId());
                $em->persist($OrganizationUnit);
                $em->flush();
                // $this->xmppCreateGroup($OrganizationUnit->getGuId());
                $this->xmppGeneral->updateCache('ou', $OrganizationUnit->getId());

                return new Response(json_encode(['status' => 'success', 'message' => 'Saved Successfully']));
            }
        }
        $formView = $this->renderView('portal/organization_unit/_form_new.html.twig', [
                            'form' => $form->createView(), 'organization' => $organization, 'ministry' => $ministry, 'organizationUnit' => $organizationUnit,
                         ]);

        return new Response(json_encode(['form' => $formView, 'status' => 'error']));
    }

    /**
     * @Route("/portal/ou/edit", name="portal_ou_edit")
     */
    public function edit(Request $request): Response
    {
        $loggedUser = $this->getUser();
        $objid = $request->request->get('objid');

        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $organization = null;
            $ministry = null;
            $organizationUnit = null;
            $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneByGuId($objid);
        } else {
            $organization = $this->profileWorkspace->getOrganization();
            $ministry = $organization->getMinistry();
            $organizationUnit = $this->profileWorkspace->getOU();
            $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneBy(['guId'=>$objid,'organization'=>$organization]);
        }
      

        $form = $this->createForm(OrganizationUnitType::class, $OrganizationUnit, ['profile' => $this->profileWorkspace->getProfile(), 'action' => $this->generateUrl('portal_ou_upd'), 'attr' => ['id' => 'frmBaseModal']])->add('btnUpdate', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);
        return $this->render('portal/organization_unit/_form_edit.html.twig', [
            'form' => $form->createView(),  'organization' => $organization, 'ministry' => $ministry, 'organizationUnit' => $organizationUnit,
        ]);
    }

    /**
     * @Route("/portal/ou/upd", name="portal_ou_upd")
     */
    public function update(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $organization = null;
            $ministry = null;
            $organizationUnit = null;
            $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneByGuId($objid);
        } else {
            $organization = $this->profileWorkspace->getOrganization();
            $ministry = $organization->getMinistry();
            $organizationUnit = $this->profileWorkspace->getOU();
            $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneBy(['guId'=>$objid,'organization'=>$organization]);
        }
       
        $form = $this->createForm(OrganizationUnitType::class, $OrganizationUnit, ['profile' => $this->profileWorkspace->getProfile(), 'action' => $this->generateUrl('portal_ou_upd'), 'attr' => ['id' => 'frmBaseModal']])->add('btnUpdate', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                if ($loggedUser->hasRole('ROLE_SUPER_ADMIN') or $loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
                    $organization = $em->getRepository("App:Portal\Organization")->findOneById($form['organization']->getData());
                }
                if ($loggedUser->hasRole('ROLE_O_ADMIN')) {
                    $organization = $this->profileWorkspace->getOrganization();
                }
                if ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
                    $organization = $this->profileWorkspace->getOrganization();
                    $OrganizationUnit->setParentOrganizationUnit($this->profileWorkspace->getOU());
                }
                $metada = $this->metadata->getPortalMetadata('U');
                $OrganizationUnit->setUpdateMetaData($metada->getId());
                $OrganizationUnit->setOrganization($organization);
                $em->persist($OrganizationUnit);
                $em->flush();
                $this->xmppGeneral->updateCache('ou', $OrganizationUnit->getId());
                // $this->xmppCreateGroup($OrganizationUnit->getGuId());

                return new Response(json_encode(['status' => 'success', 'message' => 'Updation successful']));
            }
        }

        $formView = $this->renderView('portal/organization_unit/_form_edit.html.twig', [
            'form' => $form->createView(), 'organization' => $organization, 'ministry' => $ministry,  'organizationUnit' => $organizationUnit, ]);

        return new Response(json_encode(['form' => $formView, 'status' => 'error']));
    }


    /**
     * @Route("/portal/ou/delete", name="portal_ou_delete")
     */
    public function delete(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $organization = null;
            $ministry = null;
            $organizationUnit = null;
            $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneByGuId($objid);
        } else {
            $organization = $this->profileWorkspace->getOrganization();
            $ministry = $organization->getMinistry();
            $organizationUnit = $this->profileWorkspace->getOU();
            $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneBy(['guId' => $objid, 'organization' => $organization]);
        }
        return $this->render('portal/organization_unit/_view.html.twig', ['ou'=> $OrganizationUnit]);
    }

    /**
     * @Route("/portal/ou/deleteconfirm", name="portal_ou_delete_confirm")
     */
    public function deleteConfirm(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
       
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $organization = null;
            $ministry = null;
            $organizationUnit = null;
            $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneByGuId($objid);
        } else {
            $organization = $this->profileWorkspace->getOrganization();
            $ministry = $organization->getMinistry();
            $organizationUnit = $this->profileWorkspace->getOU();
            $OrganizationUnit = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneBy(['guId' => $objid, 'organization' => $organization]);
        }

        if (!$em->createQueryBuilder('e')->select('COUNT(e.id)')->from('App:Portal\Employee', 'e')->where('e.organizationUnit = :organizationUnit')->setParameter(':organizationUnit', $OrganizationUnit)->getQuery()->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR) > 0) {
            if ('POST' == $request->getMethod()) {
                $em = $this->getDoctrine()->getManager();
                $this->xmppGeneral->removeCache('ou', $OrganizationUnit->getId());
                $em->remove($OrganizationUnit);
                $em->flush();
                return new Response(json_encode(array('status' => 'success', 'message' => 'Deletion successful')));
            }
        } else {
            return new Response(json_encode(array('status' => 'error', 'message' => 'Action unsuccessful, Members  are already assigned to this organization unit')));
        }
    }

    private function xmppCreateGroup($objid)
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $ou = $this->getDoctrine()->getRepository(OrganizationUnit::class)->findOneByGuId($objid);
        $ouCode = $ou->getOUCode();
        $ouName = $ou->getOUName();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $defaultGroupName = $uuid->toString();
        $defaultGroupTitle = 'Default Group for '.$ouCode;
        $defaultGroupDescription = 'Default Group for '.$ouName;

        $payload = ['title' => $defaultGroupTitle, 'description' => $defaultGroupDescription, 'group_purpose' => 2, 'group_type' => 1, 'group_creation' => 2, 'e2ee' => 'v2', 'image' => '', 'cover_image' => ''];
        $gzrResult = $this->xmppGroupV5->createGroupV5($loggedUser->getId(), \json_encode($payload), $ou->getId());

        return json_encode(['status' => 'success', 'message' => 'The group has been created successfully !!']);
    }

    /**
     * @Route("/portal/ou/districtLoader", name="portal_ou_district_loader")
     */
    public function districtLoaderAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $stateVal = $request->request->get('stateVal');
        if ($stateVal) {
            $district = $em->getRepository('App:Masters\District')->findBy(['state' => $stateVal]);
            $result = [];
            foreach ($district as $obj) {
                $districtId = $obj->getId();
                $districtVal = $obj->getDistrict();
                $result[$districtId] = $districtVal;
            }
        }

        return new JsonResponse(['result' => $result]);
    }

    /**
     * @Route("/getousbyo", name="portal_ou_get_ous_by_o")
     */
    public function getOUsByO(Request $request): Response
    {
        $objid = $request->request->get('oVal');
        $em = $this->getDoctrine()->getManager();
        $OrganizationUnits = $em->getRepository(OrganizationUnit::class)->findByOrganization($objid);

        $result = [];
        foreach ($OrganizationUnits as $OrganizationUnit) {
            $guid = $OrganizationUnit->getId();
            $ouname = $OrganizationUnit->getOUName();
            $result[$guid] = $ouname;
        }

        return new JsonResponse(['result' => $result]);
    }


    private function processQry($dynamicFilters = null)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $fieldAliases = ['organization_type' => 'ot.organizationTypeName', 'ou_code' => 'ou.OUCode', 'ou_name' => 'ou.OUName', 'organization_name' => 'o.organizationName', 'ministry' => 'mm.ministryName'];
        if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $Organization = 0;
            $Ministry = $this->profileWorkspace->getMinistry()->getId();
        } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $Organization = $this->profileWorkspace->getOrganization()->getId();
            $Ministry = 0;
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $Organization = $this->profileWorkspace->getOrganization()->getId();
            $OrganizationUnit = $this->profileWorkspace->getOU()->getId();
            $Ministry = 0;
        } else {
            $Organization = 0;
            $Ministry = 0;
        }
        $quer = $em->createQueryBuilder('e')
            ->select('ou.id as ou_id,ou.guId as gu_id,ot.organizationTypeName as ot_name,o.organizationName as o_name,ou.OUCode as ou_code, ou.OUName as ou_name, mm.ministryCode as ministrycode')
            ->from('App:Portal\OrganizationUnit', 'ou')
            ->leftJoin('App:Portal\Organization', 'o', 'WITH', 'ou.organization = o.id')
            ->leftJoin('App:Masters\OrganizationType', 'ot', 'WITH', 'ou.organizationUnitType = ot.id')
            ->leftJoin('App:Masters\Ministry', 'mm', 'WITH', 'o.ministry = mm.id')
            ->Where('o.ministry = :min OR :min = 0')
            ->setParameter('min', $Ministry);
        if ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $quer->andWhere('o.id = :org OR :org = 0')
                    ->setParameter('org', $Organization);
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $quer->andWhere('ou.id = :ou OR :ou = 0')
                    ->setParameter('ou', $OrganizationUnit);
        }

        if ($dynamicFilters) {
            foreach ($dynamicFilters as $k => $v) {
                if ($v['operator'] === 'ILIKE') {
                    $quer->andwhere($v['operator'] . "(" . $fieldAliases[$k] . ",:$k )=TRUE");
                    $quer->setParameter($k, '%' . trim($v['fvalue']) . '%');
                } else {
                    $quer->andwhere($fieldAliases[$k] . " " . $v['operator'] . " :$k");
                    $quer->setParameter($k, trim($v['fvalue']));
                }
            }
        }
        return $quer->getQuery();
    }
    /**
     * @Route("/get-ous", name="get_organisation_units")
     */
    public function getOrganizationUnits(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $orgId = $request->request->get('orgId');
        $qry = $em->createQueryBuilder('m.id,m.OUName')
                ->select('m.id,m.OUName')
                ->from('App:Portal\OrganizationUnit', 'm')
                ->where('m.organization = :org')
                ->setParameter('org', $orgId)
                ;
        if (!$loggedUser->hasRole('ROLE_SUPER_ADMIN') && !$loggedUser->hasRole('ROLE_MINISTRY_ADMIN') && !$loggedUser->hasRole('ROLE_O_ADMIN')) {
            $ou = $this->profileWorkspace->getOU()->getId();
            $qry->andWhere('m.id =:ou')
            ->setParameter('ou', $ou);
        }
        $sboxResult = json_encode($qry->getQuery()->getResult());

        return new Response($sboxResult);
    }
}
