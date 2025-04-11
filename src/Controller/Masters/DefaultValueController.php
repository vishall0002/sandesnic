<?php

namespace App\Controller\Masters;

use App\Entity\Masters\DefaultValue;
use App\Form\Masters\DefaultValueType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Services\PortalMetadata;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @Route("/masters/defaultvalue")
 */
class DefaultValueController extends AbstractController implements \App\Interfaces\AuditableControllerInterface {

    /**
     * @Route("/", name="masters_default_value_index")
     */
  
    public function index(): Response
    {
        $loggedUser = $this->getUser();
        $dfConfig = ([
            ['field_alias' => "code", 'display_text' => "Code", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
            ['field_alias' => "environment", 'display_text' => "Environment", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
            ['field_alias' => "description", 'display_text' => "Description", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
            ['field_alias' => "default_value", 'display_text' => "Default Value", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => '']
        ]);
        return $this->render('masters/defaultValue/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
     * @Route("/list", name="masters_default_value_list")
     */
    public function list(Request $request, PaginatorInterface $paginator): Response {

        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);

        $query = $this->processQry($dynamicFilters);
        $dvPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
        $dvPaginated->setUsedRoute('masters_default_value_list');

        return $this->render('masters/defaultValue/_list.html.twig', ['pagination' => $dvPaginated]);
    }

    /**
     * @Route("/new", name="masters_default_value_new")
     */
    public function new(): Response {
        $DefaultValue = new DefaultValue();
        $form = $this->createForm(DefaultValueType::class, $DefaultValue, array('action' => $this->generateUrl('masters_default_value_ins'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
        return $this->render('masters/defaultValue/_form_new.html.twig', [
                    'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/ins", name="masters_default_value_ins")
     */
    public function insert(Request $request): Response {
        $DefaultValue = new DefaultValue();
        $form = $this->createForm(DefaultValueType::class, $DefaultValue, array('action' => $this->generateUrl('masters_default_value_ins'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($DefaultValue);
                $em->flush();
                return new Response(json_encode(array('status' => 'success', 'message' => 'Saved Successfully')));
            }
        }
        $formView = $this->renderView('masters/defaultValue/_form_new.html.twig', [
            'form' => $form->createView()
        ]);
        return new Response(json_encode(array('status' => 'danger', 'message' => 'error', 'form' => $formView)));
    }

    /**
     * @Route("/edit/", name="masters_default_value_edit")
     */
    public function edit(Request $request): Response {
        $objId = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        $DefaultValue = $em->getRepository(DefaultValue::class)->findOneById($objId);
        $form = $this->createForm(DefaultValueType::class, $DefaultValue, array('action' => $this->generateUrl('masters_default_value_upd', array('objId' => $objId)), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Update'));
        return $this->render('masters/defaultValue/_form_edit.html.twig', [
                    'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/upd/{objId}", name="masters_default_value_upd")
     */
    public function update(Request $request, $objId): Response {
        $em = $this->getDoctrine()->getManager();
        $DefaultValue = $em->getRepository(DefaultValue::class)->findOneById($objId);
        $form = $this->createForm(DefaultValueType::class, $DefaultValue, array('action' => $this->generateUrl('masters_default_value_upd', array('objId' => $objId)), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Update'));

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->persist($DefaultValue);
                $em->flush();
                return new Response(json_encode(array('status' => 'success', 'message' => 'Updation successful')));
            }
        }
        $formView = $this->renderView('masters/defaultValue/_form_edit.html.twig', [
            'form' => $form->createView()
        ]);
        return new Response(json_encode(array('status' => 'error', 'form' => $formView)));
    }

    private function processQry($dynamicFilters = null) {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $fieldAliases = ['code' => 'd.code', 'environment' => 'd.environment', 'description' => 'd.description', 'default_value' => 'd.defaultValue'];
        $quer = $em->createQueryBuilder('d')
                ->select('d.id,d.code,d.environment, d.description, d.defaultValue')
                ->from('App:Masters\DefaultValue', 'd');
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

