<?php

namespace App\Controller\Portal;

use App\Entity\Portal\Item;

use App\Interfaces\AuditableControllerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Form\Portal\ItemType;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Services\ProfileWorkspace;
use App\Services\PortalMetadata;

class ItemController extends AbstractController implements AuditableControllerInterface {

    private $profileWorkspace;
    private $metadata;

    public function __construct(ProfileWorkspace $profileWorkspace, PortalMetadata $metadata)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
    }

    /**
     * @Route("/portal/item/", name="portal_item_index")
     */
    public function index(Request $request) {
        $dfConfig = (
            [
                [
                    'field_alias' => "itemName", 
                    'display_text' => "Item Name", 
                    'operator_type' => ['ILIKE', '='], 
                    'input_type' => "text", 'input_schema' => ''
                ],
            ]
        );

        return $this->render('portal/item/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
     * @Route("/portal/item/list", name="portal_item_list")
     */
    public function list(Request $request, PaginatorInterface $paginator) {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);

        $loggedUser = $this->getUser();

        $fieldAliases = [
            'itemName' => 'item.itemName'
        ];

        $quer = $em->createQueryBuilder('e')
        ->select('item')
        ->from('App:Portal\Item', 'item');

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
        
        $itemsPaginated = $paginator->paginate($query->getResult(), $request->query->getInt('page', 1), 20);
        $itemsPaginated->setUsedRoute('portal_item_list');

        return $this->render('portal/item/_list.html.twig', ['pagination' => $itemsPaginated]);
    }

    /**
     * @Route("/portal/item/new",name="portal_item_new")
     */
    public function new(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $Item = new Item();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $Item->setGuId($uuid->toString());
        $loggedUser = $this->getUser();

        $form = $this->createForm(
            ItemType::class, 
            $Item, 
            [
                'action' => $this->generateUrl('portal_item_ins'), 
                'attr' => ['id' => 'frmBaseModal'],
                'tokenStorage' => $this->container->get('security.token_storage'),
            ])
            ->add('btnInsert', SubmitType::class, ['label' => 'Save']);

        return $this->render('portal/item/_form_new.html.twig', [
                    'form' => $form->createView(),
                    'caption' => 'Item Form',
        ]);
    }

    /**
     * @Route("/portal/item/ins",name="portal_item_ins")
     */
    public function insert(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $Item = new Item();

        $form = $this->createForm(ItemType::class, $Item, 
        [
            'action' => $this->generateUrl('portal_item_ins'), 
            'attr' => ['id' => 'frmBaseModal'],
            'tokenStorage' => $this->container->get('security.token_storage')
        ])
        ->add('btnInsert', SubmitType::class, ['label' => 'Save']);
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $Item->setInsertMetadata($this->metadata->getPortalMetadata('I')->getId());
                $em->persist($Item);
                $em->flush();
                return new Response(json_encode(['status' => 'success','message' => 'Insertion Successful']));
            }
        }

        $formView = $this->renderView('portal/item/_form_new.html.twig', [
            'form' => $form->createView(),
            'caption' => 'Item Form']);
        return new Response(json_encode(['form' => $formView, 'status' => 'error','message' => 'Insertion Error']));
    }


    /**
     * @Route("/portal/item/edit", name="portal_item_edit")
     */
    public function edit(Request $request) {
        $objid = $request->request->get('objid');
        $Item = $this->getDoctrine()->getRepository(Item::class)->findOneByGuId($objid);
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(
            ItemType::class, 
            $Item, 
            [
                'action' => $this->generateUrl('portal_item_upd', array('objId' => $objid)), 
                'attr' => ['id' => 'frmBaseModal'],
                'tokenStorage' => $this->container->get('security.token_storage'),
            ])
            ->add('btnUpdate', SubmitType::class, ['label' => 'Update']);

        return $this->render('portal/item/_form_edit.html.twig', [
                    'form' => $form->createView(),
                    'caption' => 'Update Item Details',
                    'itemText' => $Item->getItemText()
        ]);
    }

    /**
     * @Route("/portal/item/upd/{objId}",name="portal_item_upd")
     */
    public function update(Request $request, $objId) {
        $em = $this->getDoctrine()->getManager();
        $Item = $this->getDoctrine()->getRepository(Item::class)->findOneByGuId($objId);

        $form = $this->createForm(ItemType::class, $Item, 
        [
            'action' => $this->generateUrl('portal_item_upd', ['objId' => $objId]), 
            'attr' => ['id' => 'frmBaseModal'],
            'tokenStorage' => $this->container->get('security.token_storage'),
            ])->add('btnUpdate', SubmitType::class, ['label' => 'Update', 'attr' => ['data-objid' => $objId]]);

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $Item->setUpdateMetadata($this->metadata->getPortalMetadata('U')->getId());
                $em->persist($Item);
                $em->flush();
                return new Response(json_encode(['status' => 'success','message' => 'Updation Successful']));
            }
        }
        $formView = $this->renderView('portal/item/_form_edit.html.twig', [
            'form' => $form->createView(),
            'itemText' => $Item->getItemText(),
            'caption' => 'Item Form']);

        return new Response(json_encode(['form' => $formView, 'status' => 'error', 'message' => 'Updation Error']));
    }



    /**
     * @Route("/portal/item/view", name="portal_item_view")
     */
    public function view(Request $request) {
        $objid = $request->request->get('objid');
        $Item = $this->getDoctrine()->getRepository(Item::class)->findOneByGuId($objid);

        return $this->render('portal/item/_view.html.twig', [
                    'Item' => $Item,
                    'action' => null
        ]);
    }

    /**
     * @Route("/delete", name="portal_item_delete")
     */
    public function delete(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $Item = $this->getDoctrine()->getRepository(Item::class)->findOneByGuId($objid);
        return $this->render('portal/item/_view.html.twig', [
           'Item' =>  $Item,
           'action' => 'delete'
         ]);
    }

    

    /**
     * @Route("/deleteconfirm", name="portal_item_delete_confirm")
     */
    public function deleteConfirm(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $Item = $this->getDoctrine()->getRepository(Item::class)->findOneByGuId($objid);
        if ('POST' == $request->getMethod()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($Item);
            $em->flush();

            return new Response(json_encode(array('status' => 'success', 'message' => 'Action successful')));
        }
    }

    /**
     * @Route("/pub/mobile/{itemType}/{itemName}",name="portal_item_mobile")
     */
    public function mobile(Request $request, $itemType, $itemName) {
        $em = $this->getDoctrine()->getManager();
        $Item = $this->getDoctrine()->getRepository(Item::class)->findOneBy(
        array('itemName' => $itemName,
        'itemType' => $itemType));

        if ($Item){
            return $this->render('portal/item/_item_index.html.twig', ['item' => $Item]);
        }else{
            return $this->redirect($this->generateUrl('app_home'));
        }
    }



}