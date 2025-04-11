<?php

namespace App\Controller\Portal;

use App\Entity\Portal\Employee;
use App\Entity\Portal\FileDetail;
use App\Entity\Portal\Group;
use App\Entity\Portal\MemberInGroup;
use App\Form\Portal\GroupEditType;
use App\Form\Portal\GroupType;
use App\Form\Portal\ChangeOuType;
use App\Interfaces\AuditableControllerInterface;
use App\Services\DefaultValue;
use App\Services\ImageProcess;
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

class GroupController extends AbstractController implements AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $xmppGroupV5;
    private $imageProcess;
    private $defaultValue;

    public function __construct(DefaultValue $defVal, XMPPGroupV5 $xmpp, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, ImageProcess $imageProcess)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->xmppGroupV5 = $xmpp;
        $this->defaultValue = $defVal;
        $this->imageProcess = $imageProcess;
    }

    /**
     * @Route("/portal/grp/", name="portal_grp_index")
     */
    public function index(Request $request)
    {
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $dfConfig = ([['field_alias' => "title", 'display_text' => "Group Title", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
            ['field_alias' => "ou_name", 'display_text' => "Organization Unit", 'operator_type' => ['=', 'ILIKE'], 'input_type' => "codefinder", 'input_schema' => 'OrganizationUnit'],
            ['field_alias' => "o_name", 'display_text' => "Organization", 'operator_type' => ['=', 'ILIKE'], 'input_type' => "codefinder", 'input_schema' => 'Organization'],
        ]);
        } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $dfConfig = ([['field_alias' => "title", 'display_text' => "Group Title", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
            ['field_alias' => "ou_name", 'display_text' => "Organization Unit", 'operator_type' => ['=', 'ILIKE'], 'input_type' => "codefinder", 'input_schema' => 'OrganizationUnit'],
        ]);
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $dfConfig = ([['field_alias' => "title", 'display_text' => "Group Title", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
        ]);
        } else {
            $dfConfig = ([['field_alias' => "title", 'display_text' => "Group Title", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
        ['field_alias' => "ou_name", 'display_text' => "Organization Unit", 'operator_type' => ['=', 'ILIKE'], 'input_type' => "codefinder", 'input_schema' => 'OrganizationUnit'],
        ['field_alias' => "o_name", 'display_text' => "Organization", 'operator_type' => ['=', 'ILIKE'], 'input_type' => "codefinder", 'input_schema' => 'Organization'],
        ['field_alias' => "ministry_name", 'display_text' => "Ministry", 'operator_type' => ['=', 'ILIKE'], 'input_type' => "codefinder", 'input_schema' => 'Ministry']
    ]);
        }

        return $this->render('portal/group/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
     * @Route("/portal/grp/list", name="portal_grp_list")
     */
    public function list(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);

        $loggedUser = $this->getUser();

        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = 0;
        } else {
            $oU = $this->profileWorkspace->getOu()->getId();
        }

        if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $ministry = $this->profileWorkspace->getMinistry()->getId();
        } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $Organization = $this->profileWorkspace->getOrganization()->getId();
        }
        $fieldAliases = ['title' => 'g.groupTitle', 'ou_name' => 'ou.OUName', 'o_name' => 'orgz.organizationName', 'ministry_name' => 'mn.ministryName'];

        
         if ($loggedUser->hasRole('ROLE_GROUP_ADMIN')) {
             $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($loggedUser);

             $quer = $em->createQueryBuilder('e')
    ->select(' g.id,g.guId,g.groupName, g.groupTitle,g.groupDescription,ou.OUName,orgz.organizationCode ,mn.ministryCode ,ou.OUCode')
    ->from('App:Portal\Group', 'g')
    ->join('App:Portal\OrganizationUnit', 'ou', 'WITH', 'ou.id = g.organizationUnit')
    ->join('App:Portal\Organization', 'orgz', 'WITH', 'orgz.id = ou.organization')
    ->join('App:Masters\Ministry', 'mn', 'WITH', 'mn.id = orgz.ministry')
    ->leftJoin('App:Portal\EmployeeGroupAdmin', 'ega', 'WITH', 'ega.group = g.id AND ega.isEnabled = true');
         }
         else{
             $quer = $em->createQueryBuilder('e')
    ->select(' g.id,g.guId,g.groupName, g.groupTitle,g.groupDescription,ou.OUName,orgz.organizationCode ,mn.ministryCode ,ou.OUCode')
    ->from('App:Portal\Group', 'g')
    ->join('App:Portal\OrganizationUnit', 'ou', 'WITH', 'ou.id = g.organizationUnit')
    ->join('App:Portal\Organization', 'orgz', 'WITH', 'orgz.id = ou.organization')
    ->join('App:Masters\Ministry', 'mn', 'WITH', 'mn.id = orgz.ministry');
         }

        if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $quer->where('mn.id = :ministyId')
            ->setParameter('ministyId', $ministry);
        } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
            $quer->where('orgz.id = :organizationId')
    ->setParameter('organizationId', $Organization);
        }
        elseif ($loggedUser->hasRole('ROLE_GROUP_ADMIN')) {
            $quer->where('ega.employee = :employee')
    ->setParameter('employee', $employee->getId());
        }
        else {
            $quer ->where('g.organizationUnit = :ou OR :ou = 0')
    ->setParameter('ou', $oU);
        }
        // Mail dated June 14 resolved but asked today to revert 
        // $quer ->andWhere('g.groupType != 1');
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
        $query=$quer->getQuery();
        
        $groupsPaginated = $paginator->paginate($query->getResult(), $request->query->getInt('page', 1), 20);
        $groupsPaginated->setUsedRoute('portal_grp_list');

        return $this->render('portal/group/_list.html.twig', ['pagination' => $groupsPaginated]);
    }

    /**
     * @Route("/portal/grp/new",name="portal_grp_new")
     */
    public function new(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $group = new Group();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $group->setGuId($uuid->toString());
        $group->setGroupName($uuid->toString());
        $loggedUser = $this->getUser();
        $profile = $this->profileWorkspace->getProfile();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } else {
            $oU = $this->profileWorkspace->getOu();
        }

        $form = $this->createForm(GroupType::class, $group, ['profile' => $profile,'action' => $this->generateUrl('portal_grp_ins'), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $oU, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnInsert', SubmitType::class, ['label' => 'Save']);

        return $this->render('portal/group/_form_new.html.twig', [
                    'form' => $form->createView(),
                    'organizationUnit' => $oU,
                    'caption' => 'Group Registration Form',
        ]);
    }

    /**
     * @Route("/portal/grp/ins",name="portal_grp_ins")
     */
    public function insert(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $group = new Group();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $group->setGroupName($uuid->toString());
        $profile = $this->profileWorkspace->getProfile();

        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } else {
            $oU = $this->profileWorkspace->getOu();
        }

        $form = $this->createForm(GroupType::class, $group, ['profile'=>$profile,'action' => $this->generateUrl('portal_grp_ins'), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $oU, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnInsert', SubmitType::class, ['label' => 'Save']);
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->getConnection()->beginTransaction();
                try {
                    if ($oU == null) {
                        $oU = $form->getData()->getOrganizationUnit();
                    }
                    $payload = ['title' => $group->getGroupTitle(), 'description' => $group->getGroupDescription(), 'group_purpose' => 1, 'group_type' => 2, 'group_creation' => 1, 'e2ee' => 'v2', 'image' => '', 'cover_image' => ''];
                    $gzrResult = $this->xmppGroupV5->createGroupV5($loggedUser->getId(), \json_encode($payload), $oU->getId());
                    return new Response($gzrResult);
                } catch (Exception $ex) {
                    $em->getConnection()->rollback();

                    return new Response(json_encode(['form' => $formView, 'status' => 'error']));
                }
            } else {
                $formView = $this->renderView('portal/group/_form_new.html.twig', [
                    'form' => $form->createView(),
                    'organizationUnit' => $oU,
                    'caption' => 'Group Registration Form',]);

                return new Response(json_encode(['form' => $formView, 'status' => 'error']));
            }
        }

        return $this->render('portal/group/_form_new.html.twig', [
                    'form' => $form->createView(),
                    'organizationUnit' => $oU,
                    'caption' => 'Group Registration Form',
                    'status' => 'New',
        ]);
    }

    /**
     * @Route("/portal/grp/edit", name="portal_grp_edit")
     */
    public function edit(Request $request)
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $profile = $this->profileWorkspace->getProfile();
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $Group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $Group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        $form = $this->createForm(GroupEditType::class, $Group, ['profile'=>$profile,'action' => $this->generateUrl('portal_grp_upd', ['objid' => $objid]), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $oU, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnUpdate', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);

        return $this->render('portal/group/_form_edit.html.twig', [
                    'form' => $form->createView(),
                    'organizationUnit' => $oU,
                    'caption' => 'Update Group Details',
        ]);
    }

    /**
     * @Route("/portal/grp/upd",name="portal_grp_upd")
     */
    public function update(Request $request)
    {
        $objid = $request->request->get('objid');

        $loggedUser = $this->getUser();
        $profile = $this->profileWorkspace->getProfile();
        $em = $this->getDoctrine()->getManager();
       
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        $form = $this->createForm(GroupEditType::class, $group, ['profile'=>$profile,'action' => $this->generateUrl('portal_grp_upd', ['objid' => $objid]), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $oU, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnUpdate', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->getConnection()->beginTransaction();
                try {
                    $groupName = $group->getGroupName();
                    $payload = ['attr_name' => 'title', 'attr_val' => $group->getGroupTitle()];
                    $gzrResult = $this->xmppGroupV5->updateGroupV5($loggedUser->getId(), \json_encode($payload), $groupName, $group->getXmppHost());
                    $payload = ['attr_name' => 'description', 'attr_val' => $group->getGroupDescription()];
                    $gzrResult = $this->xmppGroupV5->updateGroupV5($loggedUser->getId(), \json_encode($payload), $groupName, $group->getXmppHost());
                    return new Response($gzrResult);
                } catch (\Exception $ex) {
                    $em->getConnection()->rollback();

                    return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'An error has been occurred '])]);
                }
            } else {
                $formView = $this->renderView('portal/group/_form_edit.html.twig', [
                    'form' => $form->createView(),
                    'organizationUnit' => $oU,
                    'caption' => 'Group Registration Form',]);

                return new Response(json_encode(['form' => $formView, 'status' => 'error' . $form->getErrors()]));
            }
        }
        $formView = $this->renderView('portal/group/_form_edit.html.twig', [
            'form' => $form->createView(),
            'organizationUnit' => $oU,
            'caption' => 'Modify Group Details',]);

        return new Response(json_encode(['form' => $formView, 'status' => 'New']));
    }

    /**
     * @Route("/portal/grp/view", name="portal_grp_view")
     */
    public function view(Request $request)
    {
        $objid = $request->request->get('objid');
        $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);
        $insertMetaData = $this->metadata->getMetadataValue($group->getInsertMetaData());
        $updateMetaData = $this->metadata->getMetadataValue($group->getUpdateMetaData());
        $insertEmployee = null;
        $updateEmployee = null;
        if ($insertMetaData) {
            $insertEmployee = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($insertMetaData->getTransactionUserId());
        }
        if ($updateMetaData) {
            $updateEmployee = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($updateMetaData->getTransactionUserId());
        }
        $photo = base64_encode(stream_get_contents($group->getPhoto()->getFileData()));

        return $this->render('portal/group/_view.html.twig', [
                    'group' => $group,
                    'photo' => $photo,
                    'insertMetaData' => $insertMetaData,
                    'updateMetaData' => $updateMetaData,
                    'insertEmployee' => $insertEmployee,
                    'updateEmployee' => $updateEmployee,
        ]);
    }

    /**
     * @Route("/portal/grp/view_e2ee", name="portal_grp_view_e2ee")
     */
    public function viewe2ee(Request $request)
    {
        $objid = $request->request->get('objid');
        $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);
        $insertMetaData = $this->metadata->getMetadataValue($group->getInsertMetaData());
        $updateMetaData = $this->metadata->getMetadataValue($group->getUpdateMetaData());
        $insertEmployee = null;
        $updateEmployee = null;
        if ($insertMetaData) {
            $insertEmployee = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($insertMetaData->getTransactionUserId());
        }
        if ($updateMetaData) {
            $updateEmployee = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($updateMetaData->getTransactionUserId());
        }
        $photo = base64_encode(stream_get_contents($group->getPhoto()->getFileData()));
        return $this->render('portal/group/_viewe2ee.html.twig', [
                    'group' => $group,
                    'photo' => $photo,
                    'insertMetaData' => $insertMetaData,
                    'updateMetaData' => $updateMetaData,
                    'insertEmployee' => $insertEmployee,
                    'updateEmployee' => $updateEmployee,
        ]);
    }

    /**
     * @Route("/portal/grp/view_e2ee_confirm", name="portal_grp_view_e2ee_confirm")
     */
    public function e2eeConfirm(Request $request)
    {
        $GroupId = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($GroupId);

        try {
            $groupName = $group->getGroupName();
            $payload = ['attr_name' => 'e2ee', 'attr_val' => 'true'];
            $gzrResult = $this->xmppGroupV5->updateGroupV5($loggedUser->getId(), \json_encode($payload), $groupName, $group->getXmppHost());
            return new Response($gzrResult);
        } catch (Exception $ex) {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'E2EE failed.'])]);
        }
    }

    /**
     * @Route("/portal/grp/listmembers", name="portal_grp_listmembers")
     */
    public function listMembers(Request $request)
    {
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();

        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN') or $loggedUser->hasRole('ROLE_OU_MANAGER') or $loggedUser->hasRole('ROLE_GROUP_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        } else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Access denied'])]);
        }

        //$group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);
        $em = $this->getDoctrine()->getManager();
        $members = $em->createQueryBuilder('g')
                ->select('mg.id, e.guId as eGuId, e.employeeName, e.mobileNumber, e.jabberName, ga.description as affiliation, gr.description as role')
                ->from('App:Portal\MemberInGroup', 'mg')
                ->innerJoin('App:Portal\Employee', 'e', 'WITH', 'mg.employee = e.id')
                ->leftJoin('App:Masters\GroupAffiliation', 'ga', 'WITH', 'mg.groupAffiliation = ga.id')
                ->leftJoin('App:Masters\GroupRole', 'gr', 'WITH', 'mg.groupRole = gr.id')
                ->where('mg.group = :groupId')
                ->addOrderBy('e.employeeName', 'ASC')
                ->getQuery()
                ->setParameter('groupId', $group->getId())
                ->getResult();

        return $this->render('portal/group/_list_members.html.twig', [
                    'group' => $group,
                    'members' => $members,
        ]);
    }

    /**
     * @Route("/portal/grp/setadmin", name="portal_grp_attr")
     */
    public function setMIGAttributes(Request $request)
    {
        $submittedToken = $request->request->get('token');
        $migId = $request->request->get('migid');
        $migType = $request->request->get('migtype');

        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $em = $this->getDoctrine()->getManager();
        $memberInGroup = $this->getDoctrine()->getRepository(MemberInGroup::class)->findOneById($migId);
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
        }
        elseif ($loggedUser->hasRole('ROLE_GROUP_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
        } 
        else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Access denied'])]);
        }
        if ($memberInGroup) {
            $group = $memberInGroup->getGroup();
            $recordOU = $memberInGroup->getGroup()->getOrganizationUnit();
            if (null === $oU or $oU->getId() === $recordOU->getId()) {
                $groupName = $group->getGroupName();
                $employee = $memberInGroup->getEmployee();
                $role = 1;
                $affiliation = 1;
                if ('DGA' === $migType) {
                    $affiliation = 1;
                    $role = 1;
                } elseif ('DGM' === $migType) {
                    $affiliation = 1;
                    $role = 3;
                } elseif ('LM' === $migType) {
                    $affiliation = 1;
                    $role = 3;
                } elseif ('GM' === $migType) {
                    $affiliation = 1;
                    $role = 1;
                } else {
                    $affiliation = 1;
                    $role = 1;
                }
                $members = [['jid' => $employee->getJabberId(), 'role' => $role, 'affiliation' => $affiliation]];
                $gzrResult = $this->xmppGroupV5->subscribeMemberV5($loggedUser->getId(), \json_encode(['member' => $members]), $groupName, $group->getXmppHost());
            } else {
                return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Access denied'])]);
            }

            $members = $em->createQueryBuilder('g')
                    ->select('mg.id, e.guId as eGuId, e.employeeName, e.mobileNumber, e.jabberName, ga.description as affiliation, gr.description as role')
                    ->from('App:Portal\MemberInGroup', 'mg')
                    ->innerJoin('App:Portal\Employee', 'e', 'WITH', 'mg.employee = e.id')
                    ->leftJoin('App:Masters\GroupAffiliation', 'ga', 'WITH', 'mg.groupAffiliation = ga.id')
                    ->leftJoin('App:Masters\GroupRole', 'gr', 'WITH', 'mg.groupRole = gr.id')
                    ->where('mg.group = :groupId')
                    ->addOrderBy('e.employeeName', 'ASC')
                    ->getQuery()
                    ->setParameter('groupId', $group->getId())
                    ->getResult();

            return new JsonResponse(['form' => $this->renderView('portal/group/_list_members.html.twig', [
                        'group' => $group,
                        'members' => $members,]),
                        'result' => $gzrResult,
            ]);
        } else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Invalid data '])]);
        }
    }

    /**
     * @Route("/portal/grp/listaddmembers/index/{objid}", name="portal_grp_listaddmembers_index")
     */
    public function addMembersindex(Request $request, $objid = NULL)
    {
        $loggedUser = $this->getUser();
        $dfConfig = ([['field_alias' => "employeeName", 'display_text' => "Member Name", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
            ['field_alias' => "Email", 'display_text' => "Email", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
            ['field_alias' => "designation_name", 'display_text' => "Designation [Contains]", 'operator_type' => ['ILIKE'], 'input_type' => "text", 'input_schema' => ''],
            ['field_alias' => "designation_id", 'display_text' => "Designation [Select]", 'operator_type' => ['='], 'input_type' => "codefinder", 'input_schema' => 'Designation'],
        ]);
        $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);

        return $this->render('portal/group/add_members_index.html.twig', ['dfConfig' => $dfConfig,  'group' => $group,]);
    }


    /**
     * @Route("/portal/grp/listaddmembers/{objid}", name="portal_grp_listaddmembers")
     */
    public function listAddMembers(Request $request,PaginatorInterface $paginator,  $objid = NULL)
    {
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);
        $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();


        $fieldAliases = [
            'employeeName' => 'e.employeeName',
            'Email' => 'e.emailAddress', 
            'role' => 'gr.description',
            'designation_name' => 'd.designationName',
            'designation_id' => 'd.id'];

        $members = $em->createQueryBuilder('g')
                ->select('e.guId as eGuId, g.guId as gGuId, mg.id as mgId, e.employeeName,e.emailAddress, e.mobileNumber, e.jabberName, ga.description as affiliation, gr.description as role, d.designationName, ea.id')
                ->from('App:Portal\Employee', 'e')
                ->leftJoin('App:Portal\Designation', 'd', 'WITH', 'd.id = e.designation')
                ->innerJoin('App:Portal\OrganizationUnit', 'ou', 'WITH', 'e.organizationUnit = ou.id')
                ->innerJoin('App:Portal\Group', 'g', 'WITH', 'g.organizationUnit = ou.id')
                ->leftJoin('App:Portal\MemberInGroup', 'mg', 'WITH', 'g.id = mg.group AND e.id = mg.employee')
                ->leftJoin('App:Masters\GroupAffiliation', 'ga', 'WITH', 'mg.groupAffiliation = ga.id')
                ->leftJoin('App:Masters\GroupRole', 'gr', 'WITH', 'mg.groupRole = gr.id')
                ->leftJoin('App:Portal\EmployeeGroupAdmin', 'ea','WITH', 'ea.employee = e.id AND ea.group = :groupId')
                ->where('g.id = :groupId')
                ->addOrderBy('e.employeeName', 'ASC')
                ->setParameter('groupId', $group->getId());

            if ($dynamicFilters) {
                foreach ($dynamicFilters as $k => $v) {
                    if ($v['operator'] === 'ILIKE') {
                        $members->andwhere($v['operator'] . "(" . $fieldAliases[$k] . ",:$k )=TRUE");
                        $members->setParameter($k, '%' . trim($v['fvalue']) . '%');
                    } else {
                        $members->andwhere($fieldAliases[$k] . " " . $v['operator'] . " :$k");
                        $members->setParameter($k, trim($v['fvalue']));
                    }
                }
            }

            //Paras
            $members->andwhere('e.isRegistered = :isRegistered');
            $members->setParameter('isRegistered', 'Y');

            $query=$members->getQuery();
            
            $groupsPaginated = $paginator->paginate($query->getResult(), $request->query->getInt('page', 1), 20);
            $groupsPaginated->setUsedRoute('portal_grp_listaddmembers');

        return $this->render('portal/group/_add_members.html.twig', [
                'members' => $groupsPaginated,
                'group' => $group,
            ]);
    }

    /**
     * @Route("/portal/grp/addm", name="portal_grp_addm")
     */
    public function addMember(Request $request,PaginatorInterface $paginator)
    {
        $submittedToken = $request->request->get('token');
        $eId = $request->request->get('eobjid');
        $gId = $request->request->get('gobjid');
        $migType = $request->request->get('migtype');

        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $em = $this->getDoctrine()->getManager();
        //$group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($gId);
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($eId);
        $loggedUser = $this->getUser();

        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($gId);
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $gId, 'organizationUnit' => $oU]);
        } elseif ($loggedUser->hasRole('ROLE_GROUP_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $gId, 'organizationUnit' => $oU]);
        } else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Access denied'])]);
        }
        $GroupMembers = $this->getDoctrine()->getRepository(MemberInGroup::class)->findByGroup($group);
        $MemberCount = count($GroupMembers);


        if ($group and $employee) {
            $recordOU = $group->getOrganizationUnit();
            if (null === $oU or $oU->getId() === $recordOU->getId()) {
                $groupName = $group->getGroupName();
                $role = 1;
                $affiliation = 1;
                if ('DGA' === $migType) {
                    $affiliation = 1;
                    $role = 1;
                } elseif ('DGM' === $migType) {
                    $affiliation = 1;
                    $role = 3;
                } elseif ('LM' === $migType) {
                    $affiliation = 1;
                    $role = 3;
                } elseif ('GM' === $migType) {
                    $affiliation = 1;
                    $role = 1;
                } else {
                    $affiliation = 1;
                    $role = 1;
                }
                $members = [['jid' => $employee->getJabberId(), 'role' => $role, 'affiliation' => $affiliation]];
                $gzrResult = $this->xmppGroupV5->subscribeMemberV5($loggedUser->getId(), \json_encode(['member' => $members]), $groupName, $group->getXmppHost());
            } else {
                return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Access denied'])]);
            } 

            $members = $em->createQueryBuilder('g')
                ->select('e.guId as eGuId, g.guId as gGuId, mg.id as mgId, e.employeeName,e.emailAddress, e.mobileNumber, e.jabberName, ga.description as affiliation, gr.description as role, d.designationName, ea.id')
                ->from('App:Portal\Employee', 'e')
                ->leftJoin('App:Portal\Designation', 'd', 'WITH', 'd.id = e.designation')
                ->innerJoin('App:Portal\OrganizationUnit', 'ou', 'WITH', 'e.organizationUnit = ou.id')
                ->innerJoin('App:Portal\Group', 'g', 'WITH', 'g.organizationUnit = ou.id')
                ->leftJoin('App:Portal\MemberInGroup', 'mg', 'WITH', 'g.id = mg.group AND e.id = mg.employee')
                ->leftJoin('App:Masters\GroupAffiliation', 'ga', 'WITH', 'mg.groupAffiliation = ga.id')
                ->leftJoin('App:Masters\GroupRole', 'gr', 'WITH', 'mg.groupRole = gr.id')
                ->leftJoin('App:Portal\EmployeeGroupAdmin', 'ea','WITH',  'ea.employee = e.id g.id = ea.group')
                ->where('g.id = :groupId')
                ->addOrderBy('e.employeeName', 'ASC')
                ->setParameter('groupId', $group->getId());
            $query=$members->getQuery();
    
            $groupsPaginated = $paginator->paginate($query->getResult(), $request->query->getInt('page', 1), 20);
            $groupsPaginated->setUsedRoute('portal_grp_listaddmembers');
            $formView = $this->renderView('portal/group/_add_members.html.twig', [
                'members' => $groupsPaginated,
                'group' => $group]);
            return new JsonResponse(['form' => $formView, 'result' => $gzrResult ]);
        } else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Invalid data '])]);
        }
    }

    /**
     * @Route("/portal/grp/addanym", name="portal_grp_add_any_member")
     */
    public function addAnyMember(Request $request)
    {
        $submittedToken = $request->request->get('token');
        $eId = $request->request->get('eemail');
        $gId = $request->request->get('gobjid');
        $migType = $request->request->get('migtype');

        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $em = $this->getDoctrine()->getManager();
        $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($gId);
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByEmailAddress($eId);
        if (!$employee){
            $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByMobileNumber($eId);
        }
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
        } 
         elseif ($loggedUser->hasRole('ROLE_GROUP_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
        }
        else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Access denied'])]);
        }
        if ($group and $employee) {
            $recordOU = $group->getOrganizationUnit();
            $memberInGroup = new MemberInGroup();
            $groupName = $group->getGroupName();
            $memberInGroup->setEmployee($employee);
            $memberInGroup->setGroup($group);
            $memberInGroup->setGroupName($groupName);

            if ('DGA' === $migType) {
                $affiliation = 1;
                $role = 1;
            } elseif ('DGM' === $migType) {
                $affiliation = 1;
                $role = 3;
            } elseif ('LM' === $migType) {
                $affiliation = 1;
                $role = 3;
            } elseif ('GM' === $migType) {
                $affiliation = 1;
                $role = 1;
            } else {
                $affiliation = 1;
                $role = 1;
            }

            $themember = ['mobile' => $employee->getMobileNumber(), 'role' => $role, 'affiliation' => $affiliation];
            $gzrResult = $this->xmppGroupV5->subscribeMemberByMobileV5($loggedUser->getId(), \json_encode($themember), $groupName, $group->getXmppHost());

            $group = $memberInGroup->getGroup();
            $members = $em->createQueryBuilder('g')
                    ->select('mg.id, e.guId as eGuId, e.employeeName, e.mobileNumber, e.jabberName, ga.description as affiliation, gr.description as role')
                    ->from('App:Portal\MemberInGroup', 'mg')
                    ->innerJoin('App:Portal\Employee', 'e', 'WITH', 'mg.employee = e.id')
                    ->leftJoin('App:Masters\GroupAffiliation', 'ga', 'WITH', 'mg.groupAffiliation = ga.id')
                    ->leftJoin('App:Masters\GroupRole', 'gr', 'WITH', 'mg.groupRole = gr.id')
                    ->where('mg.group = :groupId')
                    ->addOrderBy('e.employeeName', 'ASC')
                    ->getQuery()
                    ->setParameter('groupId', $group->getId())
                    ->getResult();

            return new JsonResponse(['form' => $this->renderView('portal/group/_list_members.html.twig', [
                        'group' => $group,
                        'members' => $members]),
                        'result' => $gzrResult,
            ]);
        } else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Unable to identify the member using the mobile number provided.'])]);
        }
    }

    /**
     * @Route("/portal/grp/removegroup", name="portal_grp_removegroup")
     */
    public function removeGroup(Request $request)
    {
        $objid = $request->request->get('objid');
       
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $Group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $Group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        $form = $this->createForm(GroupEditType::class, $Group, ['action' => $this->generateUrl('portal_grp_removegroup_confirm', ['objid' => $objid]), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $oU, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnUpdate', SubmitType::class, ['label' => 'Disperse Group', 'attr' => ['data-objid' => $objid]]);

        return $this->render('portal/group/_form_edit.html.twig', [
                    'form' => $form->createView(),
                    'organizationUnit' => $oU,
                    'caption' => 'Disperse Group ',
        ]);
    }

    /**
     * @Route("/portal/grp/removegroupconfirm",name="portal_grp_removegroup_confirm")
     */
    public function removeGroupConfirm(Request $request)
    {
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
       
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($objid);
        } else {
            $oU = $this->profileWorkspace->getOu();
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $objid, 'organizationUnit' => $oU]);
        }

        $form = $this->createForm(GroupEditType::class, $group, ['action' => $this->generateUrl('portal_grp_removegroup_confirm', ['objid' => $objid]), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $oU, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnUpdate', SubmitType::class, ['label' => 'Disperse Group', 'attr' => ['data-objid' => $objid]]);

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->getConnection()->beginTransaction();
                try {
                    $groupName = $group->getGroupName();
                    $gzrResult = $this->xmppGroupV5->disperseGroupV5($loggedUser->getId(), $groupName, $group->getXmppHost());

                    return new Response($gzrResult);
                } catch (\Exception $ex) {
                    $em->getConnection()->rollback();

                    return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'An error has been occurred '])]);
                }
            } else {
                $formView = $this->renderView('portal/group/_form_edit.html.twig', [
                    'form' => $form->createView(),
                    'organizationUnit' => $oU,
                    'caption' => 'Remove Group',]);

                return new Response(json_encode(['form' => $formView, 'status' => 'error' . $form->getErrors()]));
            }
        }
        $formView = $this->renderView('portal/group/_form_edit.html.twig', [
            'form' => $form->createView(),
            'organizationUnit' => $oU,
            'caption' => 'Disperse Group',]);

        return new Response(json_encode(['form' => $formView, 'status' => 'New']));
    }

    /**
     * @Route("/portal/grp/remm", name="portal_grp_remm")
     */
    public function removeMember(Request $request)
    {
        $submittedToken = $request->request->get('token');
        $eId = $request->request->get('eobjid');
        $gId = $request->request->get('gobjid');
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $em = $this->getDoctrine()->getManager();
        //$group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($gId);
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($eId);
        $loggedUser = $this->getUser();
       
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($gId);
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $gId, 'organizationUnit' => $oU]);
        } 
        elseif ($loggedUser->hasRole('ROLE_GROUP_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $gId, 'organizationUnit' => $oU]);
        }
        else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Access denied'])]);
        }
        if ($group and $employee) {
            $recordOU = $group->getOrganizationUnit();
            if (null === $oU or $oU->getId() === $recordOU->getId()) {
                $memberInGroup = $this->getDoctrine()->getRepository(MemberInGroup::class)->findOneBy(['employee' => $employee, 'group' => $group]);
                if ($memberInGroup) {
                    $gzrResult = $this->xmppGroupV5->unSubscribeMemberV5($loggedUser->getId(), $employee->getJabberId(), $group->getGroupName(), $group->getXmppHost());
                }
            } else {
                return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Access denied'])]);
            } 

            $group = $memberInGroup->getGroup();
            $members = $em->createQueryBuilder('g')
                ->select('e.guId as eGuId, g.guId as gGuId, mg.id as mgId, e.employeeName,e.emailAddress, e.mobileNumber, e.jabberName, ga.description as affiliation, gr.description as role, d.designationName, ea.id')
                ->from('App:Portal\Employee', 'e')
                ->leftJoin('App:Portal\Designation', 'd', 'WITH', 'd.id = e.designation')
                ->innerJoin('App:Portal\OrganizationUnit', 'ou', 'WITH', 'e.organizationUnit = ou.id')
                ->innerJoin('App:Portal\Group', 'g', 'WITH', 'g.organizationUnit = ou.id')
                ->leftJoin('App:Portal\MemberInGroup', 'mg', 'WITH', 'g.id = mg.group AND e.id = mg.employee')
                ->leftJoin('App:Masters\GroupAffiliation', 'ga', 'WITH', 'mg.groupAffiliation = ga.id')
                ->leftJoin('App:Masters\GroupRole', 'gr', 'WITH', 'mg.groupRole = gr.id')
                ->leftJoin('App:Portal\EmployeeGroupAdmin', 'ea','WITH',  'ea.employee = e.id g.id = ea.group')
                ->where('g.id = :groupId')
                ->addOrderBy('e.employeeName', 'ASC')
                ->setParameter('groupId', $group->getId());
            
            $groupsPaginated = $paginator->paginate($query->getResult(), $request->query->getInt('page', 1), 20);
            $groupsPaginated->setUsedRoute('portal_grp_listaddmembers');
            $formView = $this->renderView('portal/group/_add_members.html.twig', [
                'members' => $groupsPaginated,
                'group' => $group]);
            return new JsonResponse(['form' => $formView, 'result' => $gzrResult]);
        } else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Invalid data '])]);
        }
    }

    /**
     * @Route("/portal/grp/remmfl", name="portal_grp_remmfl")
     */
    public function removeMemberFromList(Request $request)
    {
        $eId = $request->request->get('eobjid');
        $gId = $request->request->get('gobjid');
        $submittedToken = $request->request->get('token');
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');
        $em = $this->getDoctrine()->getManager();
        //$group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($gId);
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($eId);
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneByGuId($gId);
        } elseif ($loggedUser->hasRole('ROLE_OU_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $gId, 'organizationUnit' => $oU]);
        } 
        elseif ($loggedUser->hasRole('ROLE_GROUP_ADMIN')) {
            $oU = $this->profileWorkspace->getOu();
            $group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(['guId' => $gId, 'organizationUnit' => $oU]);
        } 
        else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Access denied'])]);
        }
        if ($group and $employee) {
            $recordOU = $group->getOrganizationUnit();
            if (null === $oU or $oU->getId() === $recordOU->getId()) {
                $memberInGroup = $this->getDoctrine()->getRepository(MemberInGroup::class)->findOneBy(['employee' => $employee, 'group' => $group]);
                if ($memberInGroup) {
                    $gzrResult = $this->xmppGroupV5->unSubscribeMemberV5($loggedUser->getId(), $employee->getJabberId(), $group->getGroupName(), $group->getXmppHost());
                }
            } else {
                return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Access denied'])]);
            }

            $group = $memberInGroup->getGroup();
            $members = $em->createQueryBuilder('g')
                    ->select('mg.id, e.guId as eGuId, e.employeeName, e.mobileNumber, e.jabberName, ga.description as affiliation, gr.description as role')
                    ->from('App:Portal\MemberInGroup', 'mg')
                    ->innerJoin('App:Portal\Employee', 'e', 'WITH', 'mg.employee = e.id')
                    ->leftJoin('App:Masters\GroupAffiliation', 'ga', 'WITH', 'mg.groupAffiliation = ga.id')
                    ->leftJoin('App:Masters\GroupRole', 'gr', 'WITH', 'mg.groupRole = gr.id')
                    ->where('mg.group = :groupId')
                    ->addOrderBy('e.employeeName', 'ASC')
                    ->getQuery()
                    ->setParameter('groupId', $group->getId())
                    ->getResult();

            return new JsonResponse(['form' => $this->renderView('portal/group/_list_members.html.twig', [
                        'group' => $group,
                        'members' => $members,]),
                        'result' => $gzrResult,
            ]);
        } else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Invalid data '])]);
        }
    }

    /**
     * @Route("/portal/grp/photo",name="portal_grp_photo")
     */
    public function photoAction(Request $request)
    {
        $groupGuId = $request->request->get('objid');
        $groupPhoto = '';

        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('App:Portal\Group')->findOneByGuId($groupGuId);
        $fileDetail = $em->getRepository('App:Portal\FileDetail')->findOneById($group->getPhoto());

        $csrf = $this->container->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('yourkey');

        if ($fileDetail) {
            $groupPhoto = base64_encode(stream_get_contents($fileDetail->getFileData()));
        }
        $formView = $this->renderView('portal/group/_photo.html.twig', [
            'groupPhoto' => $groupPhoto,
            'groupGuId' => $groupGuId,
        ]);

        return new JsonResponse($formView);
    }

    /**
     * @Route("/portal/grp/photo/upload",name="portal_grp_photo_upload")
     */
    public function photoUploadAction(Request $request)
    {
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $submittedToken = $request->request->get('token');
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $em = $this->getDoctrine()->getManager();
        $imageChunk = $request->request->get('img');
        $groupGuId = $request->request->get('groupGuId');
        if ('' != $imageChunk || null != $imageChunk) {
            $result = explode(',', $imageChunk);
            $imageData = $result[1];
        } else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Invalid/Incomplete file type/request'])]);
        }
        $minWidth = 150;
        $maxWidth = 460;
        $minHeight = 150;
        $maxHeight = 460;

        $decodedImageData = base64_decode($imageData);
        $encodedImageData = base64_encode($decodedImageData);
        $imageDetails = getimagesize($imageChunk);
        $Imgsize = (int) ((strlen($imageChunk) * 3 / 4) - substr_count(substr($imageChunk, -2), '=')) / 1024;
        $mime = $imageDetails['mime'];
        $imageWidth = $imageDetails[0];
        $imageHeight = $imageDetails[1];
        if (30 < $Imgsize) {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'File size exceeds the allowed limit(10kb)' . $Imgsize])]);
        }

        $allowdedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($mime, $allowdedTypes)) {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Invalid Image Type.'])]);
        }
        if ($imageWidth > $maxWidth || $imageWidth < $minWidth || $imageHeight > $maxHeight || $imageHeight < $minHeight) {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Invalid File Dimesions.Please Upload 150x200 image'])]);
        }
        $group = $em->getRepository('App:Portal\Group')->findOneByGuId($groupGuId);
        try {
            $groupName = $group->getGroupName();
            $payload = ['attr_name' => 'photo', 'attr_val' => $encodedImageData];
            $gzrResult = $this->xmppGroupV5->updateGroupV5($loggedUser->getId(), \json_encode($payload), $groupName, $group->getXmppHost());

            return new JsonResponse(['result' => $gzrResult]);
        } catch (Exception $ex) {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Image Uploading Failed.'])]);
        }
    }

    /**
     * @Route("/portal/grp/coverimage",name="portal_grp_coverimage")
     */
    public function coverimageAction(Request $request)
    {
        $groupGuId = $request->request->get('objid');
        $groupPhoto = '';

        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('App:Portal\Group')->findOneByGuId($groupGuId);
        $fileDetail = $em->getRepository('App:Portal\FileDetail')->findOneById($group->getCoverImage());

        $csrf = $this->container->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('yourkey');

        if ($fileDetail) {
            $groupPhoto = base64_encode(stream_get_contents($fileDetail->getFileData()));
        }

        $formView = $this->renderView('portal/group/_coverimage.html.twig', [
            'groupPhoto' => $groupPhoto,
            'groupGuId' => $groupGuId,
        ]);

        return new JsonResponse($formView);
    }

    /**
     * @Route("/portal/grp/coverimage/upload",name="portal_grp_coverimage_upload")
     */
    public function coverimageUploadAction(Request $request)
    {
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $submittedToken = $request->request->get('token');
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $em = $this->getDoctrine()->getManager();
        $imageChunk = $request->request->get('img');
        $groupGuId = $request->request->get('groupGuId');
        if ('' != $imageChunk || null != $imageChunk) {
            $result = explode(',', $imageChunk);
            $imageData = $result[1];
        } else {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Invalid/Incomplete file type/request'])]);
        }
        $minWidth = 150;
        $maxWidth = 460;
        $minHeight = 150;
        $maxHeight = 460;

        $decodedImageData = base64_decode($imageData);
        $encodedImageData = base64_encode($decodedImageData);
        $imageDetails = getimagesize($imageChunk);
        $Imgsize = (int) ((strlen($imageChunk) * 3 / 4) - substr_count(substr($imageChunk, -2), '=')) / 1024;
        $mime = $imageDetails['mime'];
        $imageWidth = $imageDetails[0];
        $imageHeight = $imageDetails[1];
        if (30 < $Imgsize) {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'File size exceeds the allowed limit(10kb)' . $Imgsize])]);
        }

        $allowdedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($mime, $allowdedTypes)) {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Invalid Image Type.'])]);
        }
        if ($imageWidth > $maxWidth || $imageWidth < $minWidth || $imageHeight > $maxHeight || $imageHeight < $minHeight) {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Invalid File Dimesions.Please Upload 150x200 image'])]);
        }

        $group = $em->getRepository('App:Portal\Group')->findOneByGuId($groupGuId);

        try {
            $groupName = $group->getGroupName();
            $payload = ['attr_name' => 'cover_image', 'attr_val' => $encodedImageData];
            $gzrResult = $this->xmppGroupV5->updateGroupV5($loggedUser->getId(), \json_encode($payload), $groupName, $group->getXmppHost());

            return new JsonResponse(['result' => $gzrResult]);
        } catch (Exception $ex) {
            return new JsonResponse(['result' => json_encode(['status' => 'error', 'message' => 'Cover Image Uploading Failed.'])]);
        }
    }

    /**
     * @Route("/genthumb", name="app_uphoto_genthumb")
     */
    public function generateThumb()
    {
        $em = $this->getDoctrine()->getManager();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $loggedUser = $this->getUser();
        $group = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($loggedUser);
        $groupPhoto = $this->getDoctrine()->getRepository(FileDetail::class)->findOneById($group->getPhoto());
        $encPhoto = $groupPhoto->getFileData();
        $thumb = $this->imageProcess->generateThumbnail(stream_get_contents($encPhoto));
        $groupPhoto->setThumbnail($thumb);
        $em->persist($groupPhoto);
        $em->flush();

        return new RedirectResponse($this->generateUrl('app_dashboard'));
    }

    /**
     * @Route("/changeOU", name="portal_grp_change_ou")
     */
    public function changeOu(Request $request)
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $profile = $this->profileWorkspace->getProfile();
        $group = $em->getRepository('App:Portal\Group')->findOneByGuId($objid);
        $ou = $group->getOrganizationUnit()->getOUName();
        $form = $this->createForm(ChangeOuType::class, $group, ['profile'=>$profile,'action' => $this->generateUrl('portal_grp_change_ou', ['objid' => $objid]), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $ou, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnUpdate', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $organizationUnit = $form['organizationUnit']->getData();
                $group->setOrganizationUnit($organizationUnit);
                $em->persist($group);
                $em->flush();
                return new JsonResponse(['result' => json_encode(['status' => 'success', 'message' => 'Organization Unit changed successfully'])]);
            } elseif ($form->isSubmitted() == true && $form->isValid() == false) {
                $formView = $this->renderView('portal/group/_change_ou.html.twig', [
                    'form' => $form->createView(),
                    'ou' => $ou,
                    'caption' => 'Change OU -',]);

                return new Response(json_encode(['form' => $formView, 'status' => 'error']));
            }
        }
        return $this->render('portal/group/_change_ou.html.twig', [
           'ou' => $ou,
           'form' => $form->createView(),
           'caption' => 'Change OU - '
        ]);
    }
}
