<?php

namespace App\Controller\Portal;

use App\Entity\Masters\Vhost;
use App\Form\Portal\VhostType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use App\Services\ProfileWorkspace;

class VhostController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;

    public function __construct(ProfileWorkspace $profileWorkspace)
    {
        $this->profileWorkspace = $profileWorkspace;
    }

    /**
     * @Route("/portal/vhost/", name="portal_vhost_index")
     */
    public function index(): Response
    {
        $dfConfig = [];
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $dfConfig = ([['field_alias' => 'vhostUrl', 'display_text' => 'Vhost Url', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
        ['field_alias' => 'vhostAlias', 'display_text' => 'Vhost Alias', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
        ]);
        }
        return $this->render('portal/vhost/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
     * @Route("/portal/vhost/list", name="portal_vhost_list")
     */
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);

        $query = $this->processQry($dynamicFilters);
        $ministryPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
        $ministryPaginated->setUsedRoute('portal_vhost_list');

        return $this->render('portal/vhost/_list.html.twig', ['pagination' => $ministryPaginated]);
    }

    /**
     * @Route("/portal/vhost/new", name="portal_vhost_new")
     */
    public function new(): Response
    {
        $Vhost = new Vhost();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $Vhost->setGuId($uuid->toString());
        $form = $this->createForm(VhostType::class, $Vhost, array('action' => $this->generateUrl('portal_vhost_ins'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
       
        return $this->render('portal/vhost/_form_new.html.twig', ['form' => $form->createView() ]);
    }

    /**
     * @Route("/portal/vhost/ins", name="portal_vhost_ins")
     */
    public function insert(Request $request): Response
    {
        $Vhost = new Vhost();
        $form = $this->createForm(VhostType::class, $Vhost, array('action' => $this->generateUrl('portal_vhost_ins'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
            
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();              
                $em->persist($Vhost);
                $em->flush();

                return new Response(json_encode(array('status' => 'success', 'message' => 'Saved Successfully')));
            }
        }

        $formView = $this->renderView('portal/vhost/_form_new.html.twig', [
            'form' => $form->createView()
        ]);

        return new Response(json_encode(array('form' => $formView, 'status' => 'error')));
    }

    /**
     * @Route("/portal/vhost/edit", name="portal_vhost_edit")
     */
    public function edit(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $Vhost = $this->getDoctrine()->getRepository(Vhost::class)->findOneByGuId($objid);
        $form = $this->createForm(VhostType::class, $Vhost, array('action' => $this->generateUrl('portal_vhost_upd'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Update', 'attr' => array('data-objid' => $objid)));
        return $this->render('portal/vhost/_form_edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/portal/vhost/upd", name="portal_vhost_upd")
     */
    public function update(Request $request): Response
    {
        $objid = $request->request->get('objid');

        $Vhost = $this->getDoctrine()->getRepository(Vhost::class)->findOneByGuId($objid);
        $form = $this->createForm(VhostType::class, $Vhost, array('action' => $this->generateUrl('portal_vhost_upd'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Update', 'attr' => array('data-objid' => $objid)));
      
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();               
                $em->persist($Vhost);
                $em->flush();

                return new Response(json_encode(array('status' => 'success', 'message' => 'Updation successful')));
            }
        }

        $formView = $this->renderView('portal/vhost/_form_edit.html.twig', [
            'form' => $form->createView()
        ]);

        return new Response(json_encode(array('form' => $formView, 'status' => 'error')));
    }

    /**
     * @Route("/portal/vhost/delete", name="portal_vhost_delete")
     */
    public function delete(Request $request): Response
    {
        $objid = $request->request->get('objid');

        $Vhost = $this->getDoctrine()->getRepository(Vhost::class)->findOneByGuId($objid);
        $form = $this->createForm(VhostType::class, $Vhost, array('action' => $this->generateUrl('portal_vhost_delete_confirm'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Confirm Delete', 'attr' => array('data-objid' => $objid)));

        return $this->render('portal/vhost/_form_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/portal/vhost/deleteconfirm", name="portal_vhost_delete_confirm")
     */
    public function deleteConfirm(Request $request): Response
    {
        $objid = $request->request->get('objid');        
        $Vhost = $this->getDoctrine()->getRepository(Vhost::class)->findOneByGuId($objid);
        $form = $this->createForm(VhostType::class, $Vhost, array('action' => $this->generateUrl('portal_vhost_delete_confirm'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Confirm Delete', 'attr' => array('data-objid' => $objid)));

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($Vhost);
                $em->flush();
                return new Response(json_encode(array('status' => 'success', 'message' => 'Deletion successful')));
            }
        }

        $formView = $this->renderView('portal/vhost/_form_edit.html.twig', [
            'form' => $form->createView(),
        ]);

        return new Response(json_encode(array('form' => $formView, 'status' => 'error')));
    }
   
    private function processQry($dynamicFilters = null) {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $fieldAliases = ['vhostUrl' => 'v.vhostUrl', 'vhostAlias' => 'v.vhostAlias'];
        $quer = $em->createQueryBuilder('e')
                ->select('v.guId,v.vhostUrl,v.vhostAlias')
                ->from('App:Masters\Vhost', 'v')
                ->orderBy('v.id', 'DESC');
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

}
