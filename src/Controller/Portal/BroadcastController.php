<?php

namespace App\Controller\Portal;

use App\Entity\Lists\BroadcastList;
use App\Entity\Lists\ListPublisher;
use App\Entity\Portal\FileDetail;
use App\Entity\Portal\Profile;
use App\Entity\Portal\User;
use App\Form\Portal\BroadcastListType;
use App\Interfaces\AuditableControllerInterface;
use App\Security\Encoder\SecuredLoginPasswordEncoder;
use App\Services\DefaultValue;
use App\Services\ImageProcess;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\XMPPGeneral;


class BroadcastController extends AbstractController implements AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $imageProcess;
    private $defaultValue;
    private $xmppGeneral;

    public function __construct(XMPPGeneral $xmpp, SecuredLoginPasswordEncoder $password_encoder, DefaultValue $defVal, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, ImageProcess $imageProcess)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->defaultValue = $defVal;
        $this->imageProcess = $imageProcess;
        $this->password_encoder = $password_encoder;
        $this->xmppGeneral = $xmpp;
    }

    /**
     * @Route("/portal/bl/", name="portal_brdcst_index")
     */
    public function index(Request $request)
    {
        return $this->render('portal/broadcast/index.html.twig');
    }

    /**
     * @Route("/portal/bl/list", name="portal_brdcst_list")
     */
    public function list(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();

        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();

        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = 0;
        } else {
            $oU = $this->profileWorkspace->getOu()->getId();
        }
        $ffield = $request->query->get('filterField');
        $fvalue = $request->query->get('filterValue');
        $FlistName = 'NOFILTER';
        $FgroupTitle = 'NOFILTER';
        $FgroupDescription = 'NOFILTER';
        $FouName = 'NOFILTER';

        if (!empty($ffield) and ('list_name' === $ffield)) {
            $FgroupName = '%'.$fvalue.'%';
        }
//        if (!empty($ffield) and ('group_title' === $ffield)) {
//            $FgroupTitle = '%'.$fvalue.'%';
//        }
//        if (!empty($ffield) and ('group_description' === $ffield)) {
//            $FgroupDescription = '%'.$fvalue.'%';
//        }
        if (!empty($ffield) and ('ou_name' === $ffield)) {
            $FouName = '%'.$fvalue.'%';
        }
        $sqlMS = <<<SQLMS
        SELECT  g.list_id,
                g.gu_id,
                g.list_name as list_name, 
                ou.ou_name as ou_name
        FROM gim.lists as g 
            LEFT JOIN gim.organization_unit as ou ON g.parent_ou = ou.ou_id
        WHERE   (g.parent_ou = :ou OR :ou = 0) AND
                (g.list_name ILIKE :FlistName OR :FlistName = 'NOFILTER') AND
                (ou.ou_name ILIKE :FouName OR :FouName = 'NOFILTER')
        ORDER BY g.list_id
SQLMS;

        $qryList = $myCon->prepare($sqlMS);
        $qryList->bindValue('ou', $oU);
        $qryList->bindValue('FlistName', $FlistName);
        $qryList->bindValue('FouName', $FouName);
        $qryList->execute();
        $qryListResult = $qryList->fetchAll();

        $groupsPaginated = $paginator->paginate($qryListResult, $request->query->getInt('page', 1), 20);
        $groupsPaginated->setUsedRoute('portal_brdcst_list');

        return $this->render('portal/broadcast/_list.html.twig', ['pagination' => $groupsPaginated]);
    }

    /**
     * @Route("/portal/bl/new",name="portal_brdcst_new")
     */
    public function new(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $group = new BroadcastList();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $group->setGuId($uuid->toString());
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } else {
            $oU = $this->profileWorkspace->getOu();
        }

        $form = $this->createForm(BroadcastListType::class, $group, ['action' => $this->generateUrl('portal_brdcst_ins'), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $oU, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnInsert', SubmitType::class, ['label' => 'Save']);

        return $this->render('portal/broadcast/_form_new.html.twig', [
                    'form' => $form->createView(),
                    'organizationUnit' => $oU,
                    'caption' => 'Create New List',
        ]);
    }

    /**
     * @Route("/portal/bl/ins",name="portal_brdcst_ins")
     */
    public function insert(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $group = new BroadcastList();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
//        $group->setGroupName($uuid->toString());

        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } else {
            $oU = $this->profileWorkspace->getOu();
        }
        $form = $this->createForm(BroadcastListType::class, $group, ['action' => $this->generateUrl('portal_brdcst_ins'), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $oU, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnInsert', SubmitType::class, ['label' => 'Save']);
//        if ('POST' == $request->getMethod()) {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $group->setOrganizationUnit($this->profileWorkspace->getOu());
            $group->setInsertMetadata($this->metadata->getPortalMetadata('I')->getId());
            $em->persist($group);
            $em->flush();
            $this->addFlash('success', 'The List has been created successfully !!');

            return $this->redirectToRoute('portal_brdcst_edit', ['objid' => $group->getGuId()]);
//            return new Response(json_encode(['status' => 'success', 'message' => 'The group has been created successfully !!']));
        } else {
            return $this->render('portal/broadcast/_form_new.html.twig', [
                        'form' => $form->createView(),
                        'organizationUnit' => $oU,
                        'caption' => 'Create New List', ]);

            return new Response(json_encode(['form' => $formView, 'status' => 'error']));
        }
//        }

        return $this->render('portal/broadcast/_form_new.html.twig', [
                    'form' => $form->createView(),
                    'organizationUnit' => $oU,
                    'caption' => 'Create List Form',
                    'status' => 'New',
        ]);
    }

    /**
     * @Route("/portal/bl/edit/{objid}", name="portal_brdcst_edit")
     */
    public function edit(Request $request, $objid)
    {
//        $objid = $request->request->get('objid');
        $list = $this->getDoctrine()->getRepository(BroadcastList::class)->findOneByGuId($objid);
        $em = $this->getDoctrine()->getManager();

        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } else {
            $oU = $this->profileWorkspace->getOu();
        }
        $listPublishers = $this->getDoctrine()->getRepository(\App\Entity\Lists\ListPublisher::class)->findByList($list);
        $subscriberTypes = $this->getDoctrine()->getRepository(\App\Entity\Masters\ListSubscriberType::class)->findAll();

        $subscribers = [];
        $ministries = $em->createQueryBuilder('m')
                ->select('m.id as id,s.ministryName as name,l.listName,mc.ministryCategory,m.guId as guid')
                ->from('App:Lists\SubscriberMinistry', 'm')
                ->leftJoin('App:Lists\SubscriberHead', 'h', 'WITH', 'm.subscriberHead = h.id')
                ->innerJoin('h.subscriberType', 'st', 'WITH', 'h.subscriberType = st.id')
                ->innerJoin('m.subscriber', 's', 'WITH', 'm.subscriber = s.id')
                ->innerJoin('s.ministryCategoryId', 'mc', 'WITH', 's.ministryCategoryId = mc.id')
                ->innerJoin('App:Lists\BroadcastList', 'l', 'WITH', 'h.list = l.id')
                ->where('st.cfCode = :type')
                ->andWhere('h.list = :list')
                ->setParameter('type', 'SubscriberMinistry')
                ->setParameter('list', $list)
                ->getQuery()
                ->getResult();
        $subscribers['SubscriberMinistry']['data'] = $ministries;
        $subscribers['SubscriberMinistry']['keys'] = ['name' => 'Ministry Name', 'ministryCategory' => 'Ministry Category'];
        $orgs = $em->createQueryBuilder('m')
                ->select('m.id as id,s.organizationName as name,mn.ministryName,mnc.ministryCategory,l.listName,m.guId as guid')
                ->from('App:Lists\SubscriberOrg', 'm')
                ->leftJoin('App:Lists\SubscriberHead', 'h', 'WITH', 'm.subscriberHead = h.id')
                ->innerJoin('h.subscriberType', 'st', 'WITH', 'h.subscriberType = st.id')
                ->innerJoin('m.subscriber', 's', 'WITH', 'm.subscriber = s.id')
                ->innerJoin('s.ministry', 'mn', 'WITH', 's.ministry = mn.id')
                ->innerJoin('mn.ministryCategoryId', 'mnc', 'WITH', 'mn.ministryCategoryId = mnc.id')
                ->innerJoin('App:Lists\BroadcastList', 'l', 'WITH', 'h.list = l.id')
                ->where('st.cfCode = :type')
                ->andWhere('h.list = :list')
                ->setParameter('type', 'SubscriberOrg')
                ->setParameter('list', $list)
                ->getQuery()
                ->getResult();
        $subscribers['SubscriberOrg']['data'] = $orgs;
        $subscribers['SubscriberOrg']['keys'] = ['name' => 'Organization Name', 'ministryName' => 'Ministry', 'ministryCategory' => 'Ministry Category'];
        $lists = $em->createQueryBuilder('m')
                ->select('m.id as id,s.listName as name,l.listName,m.guId as guid')
                ->from('App:Lists\SubscriberList', 'm')
                ->leftJoin('App:Lists\SubscriberHead', 'h', 'WITH', 'm.subscriberHead = h.id')
                ->innerJoin('h.subscriberType', 'st', 'WITH', 'h.subscriberType = st.id')
                ->innerJoin('m.subscriber', 's', 'WITH', 'm.subscriber = s.id')
                ->innerJoin('App:Lists\BroadcastList', 'l', 'WITH', 'h.list = l.id')
                ->where('st.cfCode = :type')
                ->andWhere('h.list = :list')
                ->setParameter('type', 'SubscriberList')
                ->setParameter('list', $list)
                ->getQuery()
                ->getResult();
        $subscribers['SubscriberList']['data'] = $lists;
        $subscribers['SubscriberList']['keys'] = ['name' => 'List Name'];
        $ous = $em->createQueryBuilder('m')
                ->select('m.id as id,s.OUName as name,o.organizationName,mn.ministryName,mnc.ministryCategory,l.listName,m.guId as guid')
                ->from('App:Lists\SubscriberOu', 'm')
                ->leftJoin('App:Lists\SubscriberHead', 'h', 'WITH', 'm.subscriberHead = h.id')
                ->innerJoin('h.subscriberType', 'st', 'WITH', 'h.subscriberType = st.id')
                ->innerJoin('m.subscriber', 's', 'WITH', 'm.subscriber = s.id')
                ->innerJoin('s.organization', 'o', 'WITH', 's.organization = o.id')
                ->innerJoin('o.ministry', 'mn', 'WITH', 'o.ministry = mn.id')
                ->innerJoin('mn.ministryCategoryId', 'mnc', 'WITH', 'mn.ministryCategoryId = mnc.id')
                ->innerJoin('App:Lists\BroadcastList', 'l', 'WITH', 'h.list = l.id')
                ->where('st.cfCode = :type')
                ->andWhere('h.list = :list')
                ->setParameter('type', 'SubscriberOu')
                ->setParameter('list', $list)
                ->getQuery()
                ->getResult();
//        dump($ous);
//        die;
        $subscribers['SubscriberOu']['data'] = $ous;
        $subscribers['SubscriberOu']['keys'] = ['name' => 'Organization Unit Name', 'organizationName' => 'Organization', 'ministryName' => 'Ministry', 'ministryCategory' => 'Ministry Category'];
        $groups = $em->createQueryBuilder('m')
                ->select('m.id as id,s.groupName,s.groupTitle as name,l.listName,m.guId as guid')
                ->from('App:Lists\SubscriberGroup', 'm')
                ->leftJoin('App:Lists\SubscriberHead', 'h', 'WITH', 'm.subscriberHead = h.id')
                ->innerJoin('h.subscriberType', 'st', 'WITH', 'h.subscriberType = st.id')
                ->innerJoin('m.subscriber', 's', 'WITH', 'm.subscriber = s.id')
                ->innerJoin('App:Lists\BroadcastList', 'l', 'WITH', 'h.list = l.id')
                ->where('st.cfCode = :type')
                ->andWhere('h.list = :list')
                ->setParameter('type', 'SubscriberGroup')
                ->setParameter('list', $list)
                ->getQuery()
                ->getResult();
        $subscribers['SubscriberGroup']['data'] = $groups;
        $subscribers['SubscriberGroup']['keys'] = ['name' => 'Group Title'];
        $users = $em->createQueryBuilder('m')
                ->select('m.id as id,s.employeeName as name,d.designationName,l.listName,m.guId as guid')
                ->from('App:Lists\SubscriberUser', 'm')
                ->leftJoin('App:Lists\SubscriberHead', 'h', 'WITH', 'm.subscriberHead = h.id')
                ->innerJoin('h.subscriberType', 'st', 'WITH', 'h.subscriberType = st.id')
                ->innerJoin('m.subscriber', 's', 'WITH', 'm.subscriber = s.id')
                ->innerJoin('s.designation', 'd', 'WITH', 's.designation = d.id')
                ->innerJoin('App:Lists\BroadcastList', 'l', 'WITH', 'h.list = l.id')
                ->where('st.cfCode = :type')
                ->andWhere('h.list = :list')
                ->setParameter('type', 'SubscriberUser')
                ->setParameter('list', $list)
                ->getQuery()
                ->getResult();
        $subscribers['SubscriberUser']['data'] = $users;
        $subscribers['SubscriberUser']['keys'] = ['name' => 'Name', 'designationName' => 'Designation'];
        $countries = $em->createQueryBuilder('m')
                ->select('m.id as id,s.countryName as name,l.listName,m.guId as guid')
                ->from('App:Lists\SubscriberCountry', 'm')
                ->leftJoin('App:Lists\SubscriberHead', 'h', 'WITH', 'm.subscriberHead = h.id')
                ->innerJoin('h.subscriberType', 'st', 'WITH', 'h.subscriberType = st.id')
                ->innerJoin('m.subscriber', 's', 'WITH', 'm.subscriber = s.id')
                ->innerJoin('App:Lists\BroadcastList', 'l', 'WITH', 'h.list = l.id')
                ->where('st.cfCode = :type')
                ->andWhere('h.list = :list')
                ->setParameter('type', 'SubscriberCountry')
                ->setParameter('list', $list)
                ->getQuery()
                ->getResult();
        $subscribers['SubscriberCountry']['data'] = $countries;
        $subscribers['SubscriberCountry']['keys'] = ['name' => 'Country'];
        $states = $em->createQueryBuilder('m')
                ->select('m.id as id,s.state as name,c.countryName,l.listName,m.guId as guid')
                ->from('App:Lists\SubscriberState', 'm')
                ->leftJoin('App:Lists\SubscriberHead', 'h', 'WITH', 'm.subscriberHead = h.id')
                ->innerJoin('h.subscriberType', 'st', 'WITH', 'h.subscriberType = st.id')
                ->innerJoin('m.subscriber', 's', 'WITH', 'm.subscriber = s.id')
                ->innerJoin('s.country', 'c', 'WITH', 's.country = c.id')
                ->innerJoin('App:Lists\BroadcastList', 'l', 'WITH', 'h.list = l.id')
                ->where('st.cfCode = :type')
                ->andWhere('h.list = :list')
                ->setParameter('type', 'SubscriberState')
                ->setParameter('list', $list)
                ->getQuery()
                ->getResult();
        $subscribers['SubscriberState']['data'] = $states;
        $subscribers['SubscriberState']['keys'] = ['name' => 'State Name', 'countryName' => 'Country'];
        $districts = $em->createQueryBuilder('m')
                ->select('m.id as id,s.district as name,ms.state,c.countryName,l.listName,m.guId as guid')
                ->from('App:Lists\SubscriberDistrict', 'm')
                ->leftJoin('App:Lists\SubscriberHead', 'h', 'WITH', 'm.subscriberHead = h.id')
                ->innerJoin('h.subscriberType', 'st', 'WITH', 'h.subscriberType = st.id')
                ->innerJoin('m.subscriber', 's', 'WITH', 'm.subscriber = s.id')
                ->innerJoin('s.state', 'ms', 'WITH', 's.state = ms.id')
                ->innerJoin('ms.country', 'c', 'WITH', 'ms.country = c.id')
                ->innerJoin('App:Lists\BroadcastList', 'l', 'WITH', 'h.list = l.id')
                ->where('st.cfCode = :type')
                ->andWhere('h.list = :list')
                ->setParameter('type', 'SubscriberDistrict')
                ->setParameter('list', $list)
                ->getQuery()
                ->getResult();
        $subscribers['SubscriberDistrict']['data'] = $districts;
        $subscribers['SubscriberDistrict']['keys'] = ['name' => 'District Name', 'state' => 'State', 'countryName' => 'Country'];
//        }
        $form = $this->createForm(BroadcastListType::class, $list, ['action' => $this->generateUrl('portal_brdcst_upd', ['objid' => $objid]), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $oU, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnInsert', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);

        return $this->render('portal/broadcast/_form_new.html.twig', [
                    'form' => $form->createView(),
                    'organizationUnit' => $oU,
                    'caption' => 'Modify List Details',
                    'status' => 'edit',
                    'objid' => $list->getGuId(),
                    'publishers' => $listPublishers,
                    'subscriberTypes' => $subscriberTypes,
                    'subscribers' => $subscribers,
        ]);
    }

    /**
     * @Route("/portal/bl/upd/{objid}",name="portal_brdcst_upd")
     */
    public function update(Request $request, $objid)
    {
//        $objid = $request->request->get('objid');

        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $broadcast_list = $this->getDoctrine()->getRepository(BroadcastList::class)->findOneByGuId($objid);
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $oU = null;
        } else {
            $oU = $this->profileWorkspace->getOu();
        }

        $form = $this->createForm(BroadcastListType::class, $broadcast_list, ['action' => $this->generateUrl('portal_brdcst_upd', ['objid' => $objid]), 'attr' => ['id' => 'frmBaseModal'], 'ou' => $oU, 'tokenStorage' => $this->container->get('security.token_storage'), 'em' => $em])->add('btnInsert', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objid]]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($broadcast_list);
            $em->flush();
            $this->addFlash('success', 'The List has been Updated successfully !!');

            return $this->redirectToRoute('portal_brdcst_edit', ['objid' => $broadcast_list->getGuId()]);
//            return new Response(json_encode(['status' => 'success', 'message' => 'The group details has been updated successfully !!']));
        } else {
            $formView = $this->renderView('portal/broadcast/_form_new.html.twig', [
                'form' => $form->createView(),
                'organizationUnit' => $oU,
                'caption' => 'Group Registration Form', ]);

            return new Response(json_encode(['form' => $formView, 'status' => 'error'.$form->getErrors()]));
        }
        $formView = $this->renderView('portal/broadcast/_form_new.html.twig', [
            'form' => $form->createView(),
            'organizationUnit' => $oU,
            'caption' => 'Modify List Details', ]);

        return new Response(json_encode(['form' => $formView, 'status' => 'New']));
    }

    /**
     * @Route("/new-pblshr", name="list_add_publisher")
     */
    public function newPublisher(Request $request): Response
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $objid = $request->request->get('objid');
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $Organization = null;
        } else {
            $Organization = $this->profileWorkspace->getOU();
        }
        $form = $this->createFormBuilder()
                ->add('employee', EntityType::class, [
                    'class' => 'App\Entity\Portal\Employee',
                    'placeholder' => 'Select Employee ',
                    'choice_label' => 'EmployeeName',
                    'query_builder' => function (\Doctrine\ORM\EntityRepository $repository) use ($Organization) {
                        $qb = $repository->createQueryBuilder('e')
                                ->where('e.organizationUnit = :ou')
                                ->setParameter('ou', $Organization);

                        return $qb;
                    },
                    'attr' => ['class' => 'searchable publisher'],
                ])
                ->add('rateLimitName', EntityType::class, [
                    'class' => 'App\Entity\Masters\PublisherRateLimiter',
                    'placeholder' => 'Select Rate Limiter ',
                    'choice_label' => 'rateLimitName',
                    'attr' => ['class' => 'searchable publisher'],
                ])
                ->add('btnUpdate', SubmitType::class, ['label' => 'Save', 'attr' => ['data-objid' => $objid, 'data-path' => $this->generateUrl('list_add_publisher')]])
                ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $list = $this->getDoctrine()->getRepository(BroadcastList::class)->findOneByGuId($objid);
            $publisher = new ListPublisher();
            $publisher->setEmployee($form['employee']->getData());
            $publisher->setList($list);
            $publisher->setRateLimiter($form['rateLimitName']->getData());
            $publisher->setInsertMetadata($this->metadata->getPortalMetadata('I')->getId());
            $publisher->setGuId($uuid->toString());
            $em->persist($publisher);
            $em->flush();
            $th = '<th>Publisher Name</th><th>Rate Limiter</th>';
            $tr = "<tr class='data'><td></td><td>".$publisher->getEmployee()->getEmployeeName().'</td>'
                    .'<td>'.$publisher->getRateLimiter()->getRateLimitName().'</td>'
                    ."<td data-objid='".$uuid->toString()."'><span class='btn btn-xs btn-danger deleteSubscriber' data-subtype='ListPublisher' data-path='".$this->generateUrl('list_delete_subscriber')."'>Delete</span></td>";

            return new Response(json_encode(['status' => 'success', 'message' => 'The List details has been updated successfully !!', 'th' => $th, 'tr' => $tr]));
        }

        return $this->render('portal/broadcast/_form_new_publisher.html.twig', [
                    'form' => $form->createView(),
                    'organization' => $Organization,
                    'caption' => 'Add Publisher',
                    'objid' => $objid,
        ]);
    }

    /**
     * @Route("/sbcrbr-insrt", name="list_subscriber_insert")
     */
    public function newSubscriber(Request $request): Response
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $objid = $request->request->get('objid');
        $subscriberType = $request->request->get('cfFor');
        $selectedVal = $request->request->get('selectedVal');
        $em = $this->getDoctrine()->getManager();
        $list = $this->getDoctrine()->getRepository(BroadcastList::class)->findOneByGuId($objid);
        $subscriberTypeEntity = $this->getDoctrine()->getRepository(\App\Entity\Masters\ListSubscriberType::class)->findOneByCfCode($subscriberType);
        $head = $this->getDoctrine()->getRepository(\App\Entity\Lists\SubscriberHead::class)->findOneBy(['list' => $list, 'subscriberType' => $subscriberTypeEntity]);
        if (!$head) {
            $head = new \App\Entity\Lists\SubscriberHead();
            $head->setList($list);
            $head->setSubscriberType($subscriberTypeEntity);
            $head->setGuId($uuid->toString());
            $em->persist($head);
        }

        $em->getConnection()->beginTransaction();
        try {
            $myClass = "\App\Entity\Lists\\$subscriberType";
            $entity = new $myClass();
            $targetEntity = $em->getClassMetadata($myClass)->getAssociationTargetClass('subscriber');
            $entity->setSubscriber($em->getReference($targetEntity, $selectedVal));
            $entity->setSubscriberHead($head);
            $entity->setGuId($uuid->toString());
            $em->persist($entity);
            $em->flush();
            $em->getConnection()->commit();
            $actionTd = "<td data-objid='".$uuid->toString()."'><span class='btn btn-xs btn-danger deleteSubscriber' data-subtype='".$subscriberType."' data-path='".$this->generateUrl('list_delete_subscriber')."'>Delete</span></td>";
            if ('SubscriberMinistry' == $subscriberType) {
                $th = '<th>Ministry Name</th><th>Ministry Category</th>';
                $tr = "<tr class='data'><td></td><td>".$entity->getSubscriber()->getministryName().'</td>
                                    <td>'.$entity->getSubscriber()->getministryCategoryId()->getministryCategory()."</td>
                                    $actionTd </tr>";
            } elseif ('SubscriberUser' == $subscriberType) {
                $th = '<th>Name</th><th>Designation</th>';
                $tr = "<tr class='data'><td></td><td>".$entity->getSubscriber()->getEmployeeName().'</td>
                                    <td>'.$entity->getSubscriber()->getDesignation()->getDesignationName()."</td>
                                    $actionTd</tr>";
            } elseif ('SubscriberOrg' == $subscriberType) {
                $th = '<th>Organization Name</th><th>Ministry</th><th>Ministry Category</th>';
                $tr = "<tr class='data'><td></td><td>".$entity->getSubscriber()->getOrganizationName().'</td>
                                    <td>'.$entity->getSubscriber()->getMinistry()->getMinistryName().'</td>
                                    <td>'.$entity->getSubscriber()->getMinistry()->getMinistryCategoryId()->getMinistryCategory()."</td>
                                    $actionTd</tr>";
            } elseif ('SubscriberOu' == $subscriberType) {
                $th = '<th>Organization Unit Name</th><th>Organization</th><th>Ministry</th><th>Ministry Category</th>';
                $tr = "<tr class='data'><td></td><td>".$entity->getSubscriber()->getOUName().'</td>
                                    <td>'.$entity->getSubscriber()->getOrganization()->getOrgnizationName().'</td>
                                    <td>'.$entity->getSubscriber()->getOrganization()->getMinistry()->getMinistryName().'</td>
                                    <td>'.$entity->getSubscriber()->getOrganization()->getMinistry()->getMinistryCategoryId()->getMinistryCategory()."</td>
                                    $actionTd</tr>";
            } elseif ('SubscriberGroup' == $subscriberType) {
                $th = '<th>Group Title</th>';
                $tr = "<tr class='data'><td></td><td>".$entity->getSubscriber()->getGroupTitle()."</td>
                                    $actionTd</tr>";
            } elseif ('SubscriberList' == $subscriberType) {
                $th = '<th>List Name</th>';
                $tr = "<tr class='data'><td></td><td>".$entity->getSubscriber()->getListName()."</td>
                                    $actionTd</tr>";
            } elseif ('SubscriberState' == $subscriberType) {
                $th = '<th>State Name</th><th>Country</th>';
                $tr = "<tr class='data'><td></td><td>".$entity->getSubscriber()->getState().'</td>
                                    <td>'.$entity->getSubscriber()->getCountry()->getCountryName()."</td>
                                    $actionTd</tr>";
            } elseif ('SubscriberDistrict' == $subscriberType) {
                $th = '<th>District Name</th><th>State</th><th>Country</th>';
                $tr = "<tr class='data'><td></td><td>".$entity->getSubscriber()->getDistrict().'</td>
                                    <td>'.$entity->getSubscriber()->getState()->getState().'</td>
                                    <td>'.$entity->getSubscriber()->getState()->getCountry()->getCountryName()."</td>
                                    $actionTd</tr>";
            } elseif ('SubscriberCountry' == $subscriberType) {
                $th = '<th>Country</th>';
                $tr = "<tr class='data'><td></td><td>".$entity->getSubscriber()->getCountryName()."</td>
                                    $actionTd</tr>";
            }

            return new Response(json_encode(['status' => 'success', 'message' => 'The Subscriber details has been updated successfully !!', 'tr' => $tr, 'th' => $th, 'subscriberType' => $subscriberTypeEntity->getSubscriberType()]));
        } catch (\Exception $ex) {
            dump($ex);
            die;
            $em->getConnection()->rollback();

            return new Response(json_encode(['status' => 'error', 'message' => 'Unable to Add Subscriber']));
        }
    }

    /**
     * @Route("/portal/bl/delete-subscriber", name="list_delete_subscriber")
     */
    public function deleteSubscriber(Request $request)
    {
        $objid = $request->request->get('objid');
        $subscriberType = $request->request->get('type');

        // Sanitize subscriberType to allow only alphanumeric characters and underscores - Paras
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $subscriberType)) {
            return new Response(json_encode(['status' => 'error', 'message' => 'Invalid subscriber type!']), 400);
        }
        $entityClass = "App\Entity\Lists\\$subscriberType";
        if (!class_exists($entityClass)) {
            return new Response(json_encode(['status' => 'error', 'message' => 'Subscriber type not found!']), 400);
        }
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $query = $qb->delete($entityClass, 's')
                    ->where('s.guId = :objid')
                    ->setParameter('objid', $objid)
                    ->getQuery();

        $query->execute();
        // $em->remove($subscriber);
            $em->flush();

        return new Response(json_encode(['status' => 'success', 'message' => 'Successfully Removed Subscriber from the List !!']));
    }

    /**
     * @Route("/portal/bl/publish", name="list_publish")
     */
    public function publishList(Request $request)
    {
        $objid = $request->request->get('objid');
        $action = $request->request->get('action');
        $list = $this->getDoctrine()->getRepository(BroadcastList::class)->findOneByGuId($objid);
        $list_publishers = $this->getDoctrine()->getRepository(ListPublisher::class)->findByList($list);
        $em = $this->getDoctrine()->getManager();

        $em->getConnection()->beginTransaction();

        try {
            if ('PUBLISH' === $action) {
                if (!$list->getEmployee()) {
                    $employee = new \App\Entity\Portal\Employee();
                    $newUser = new User();

                    $oU = $list->getOrganizationUnit();
                    $jabberId = str_replace(' ', '', strtolower($list->getListName()).'@listgateway.gims.gov.in');
                    $employee->setEmployeeName($list->getListName());
                    $employee->setJabberId($jabberId);
                    $employee->setOrganizationUnit($oU);
                    $employee->setJabberName($list->getGuId());
                    $employee->setIsRegistered('Y');
                    $employee->setRegisteredDate(new \DateTime('now'));
                    $employee->setAccountType('L');
                    $employee->setHost('listgateway.gims.gov.in');
                    $employee->setEmailAddress($list->getGuId().'@listgateway.gims.gov.in');
                    $employee->setEmployeeCode(substr($list->getGuId(), 0, 8));
                    $blankPhoto = $this->getDoctrine()->getRepository(FileDetail::class)->findOneById(4);

                    $newUser->setRoles(['ROLE_MEMBER']);
                    $role = $em->getRepository("App:Portal\Roles")->findOneByRole('ROLE_MEMBER');

                    $newUser->setUsername($list->getGuId());

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
                    $employee->setPhoto($blankPhoto);
                    $employee->setGuid($uuid->toString());

                    $metada = $this->metadata->getPortalMetadata('I');
                    $employee->setInsertMetaData($metada);

//            $employee->setOrganizationUnit($list->getO);
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
                    $list->setEmployee($employee);
                    $em->persist($newUser);
                    $em->persist($profile);
                    $em->persist($employee);
                }

                $list->setIsActive(true);
                $msg = 'The list has been published successfully !!';
            } else {
                $list->setIsActive(false);
                $msg = 'The list has been unpublished successfully !!';
            }
            foreach ($list_publishers as $list_publisher) {
                $this->xmppGeneral->refreshProfileV5($list_publisher->getEmployee()->getGuId());
            }

            $em->flush();
            $em->getConnection()->commit();

            return new Response(json_encode(['status' => 'success', 'message' => $msg]));
        } catch (Exception $ex) {
            $em->getConnection()->rollback();

            return new Response(json_encode(['status' => 'error', 'message' => 'Unable to Publish/Unpublish']));
        }
    }

    /**
     * @Route("/grp-byou", name="portal_group_byou")
     */
    public function getGroupByOu(Request $request, $param)
    {
        $em = $this->getDoctrine()->getManager();
        $oU = $this->profileWorkspace->getOu()->getId();
        $employee = $em->getRepository('App:Portal\Group')->findByOrganizationUnit($oU);

        return $this->render('CodeFinder/parent1.html.twig', ['masterEntity' => $employee, 'param' => $param]);
    }

    /**
     * @Route("/lst-byou", name="portal_list_byou")
     */
    public function getListByOu(Request $request, $param)
    {
        $em = $this->getDoctrine()->getManager();
        $oU = $this->profileWorkspace->getOu()->getId();
        $employee = $em->getRepository('App:Lists\BroadcastList')->findByOrganizationUnit($oU);

        return $this->render('CodeFinder/parent1.html.twig', ['masterEntity' => $employee, 'param' => $param]);
    }

    /**
     * @Route("/get-mnstry", name="get_ministries")
     */
    public function getMinistries(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $catId = $request->request->get('ministryCategoryId');
        $qry = $em->createQueryBuilder('m.id,m.ministryName')
                ->select('m.id,m.ministryName')
                ->from('App:Masters\Ministry', 'm')
                ->where('m.ministryCategoryId = :cat')
                ->setParameter('cat', $catId)
                ->getQuery();
        $sboxResult = json_encode($qry->getResult());

        return new Response($sboxResult);
    }

    /**
     * @Route("/get-dists", name="get_districts")
     */
    public function getDistricts(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $state = $request->request->get('state');
        $qry = $em->createQueryBuilder('m.id,m.district')
                ->select('m.id,m.district')
                ->from('App:Masters\District', 'm')
                ->where('m.state = :state')
                ->setParameter('state', $state)
                ->getQuery();
        $sboxResult = json_encode($qry->getResult());

        return new Response($sboxResult);
    }

    /**
     * @Route("/get-sts", name="get_states")
     */
    public function getStates(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $country = $request->request->get('country');
        $qry = $em->createQueryBuilder('m.id,m.state')
                ->select('m.id,m.state')
                ->from('App:Masters\State', 'm')
                ->where('m.country = 1')
                ->getQuery();
        $sboxResult = json_encode($qry->getResult());

        return new Response($sboxResult);
    }
}
