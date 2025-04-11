<?php

namespace App\Controller\Portal;

use App\Entity\Portal\EmployeeLevel;
use App\Form\Portal\EmployeeLevelType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @Route("/portal/emplvl")
 */
class EmployeeLevelController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    /**
     * @Route("/", name="portal_employee_level_index")
     */
    public function index(): Response
    {
        return $this->render('portal/employeeLevel/index.html.twig');
    }

    /**
     * @Route("/list", name="portal_employee_level_list")
     */
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createFormBuilder()
            ->add('filters', EntityType::class, array(
                       'class' => 'App\Entity\Portal\Organization',
                       'placeholder' => 'Select Organization ',
                       'label' => 'Filters',
                       'required' => false,
                       'attr'=>['class'=>'listfilter form-control-sm  mb-2 mr-sm-2','data-list-filter-path'=>$this->generateUrl('portal_employee_level_list'),'data-main-path'=>$this->generateUrl('portal_employee_level_index')],
                   ))
            ->getForm();
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param'):$request->query->get('custom_filter_param');
        if ($filters == '') {
            $dql = "SELECT d FROM App:Portal\EmployeeLevel d ORDER BY d.id DESC";
            $qryOU = $em->createQuery($dql);
        }
        else
        {
            $qryOU = $em->createQueryBuilder('d')
                ->select('d')
                ->from('App:Portal\EmployeeLevel', 'd')
                ->where('d.organization = :org')
                ->setParameter('org',$filters);
        }

        $pagination = $paginator->paginate($qryOU, $request->query->getInt('page', 1), 20);
        $pagination->setUsedRoute('portal_employee_level_list');

        return $this->render('portal/employeeLevel/_list.html.twig', array('paged_records' => $pagination, 'form' => $form->createView(), 'filter'=>$filters));
    }

    /**
     * @Route("/new", name="portal_employee_level_new")
     */
    public function new(): Response
    {
        $employeeLevel = new employeeLevel();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $employeeLevel->setGuId($uuid->toString());
        $loggedUser = $this->getUser();
        $form = $this->createForm(employeeLevelType::class, $employeeLevel, array('action' => $this->generateUrl('portal_employee_level_ins'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));

        return $this->render('portal/employeeLevel/_form_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/ins", name="portal_employee_level_ins")
     */
    public function insert(Request $request): Response
    {
        $employeeLevel = new employeeLevel();
        $form = $this->createForm(employeeLevelType::class, $employeeLevel, array('action' => $this->generateUrl('portal_employee_level_ins'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $employeeLevel->setLevelNumber(0);
                $em->persist($employeeLevel);
                $em->flush();

                return new Response(json_encode(array('status' => 'success', 'message' => 'Saved Successfully')));
            } else {
            }
        }
        $formView = $this->renderView('portal/employeeLevel/_form_new.html.twig', array(
            'form' => $form->createView(), ));

        return new Response(json_encode(array('form' => $formView, 'status' => 'danger', 'message' => 'error')));
    }

    /**
     * @Route("/edit", name="portal_employee_level_edit")
     */
    public function edit(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $employeeLevel = $this->getDoctrine()->getRepository(employeeLevel::class)->findOneByGuId($objid);
        $form = $this->createForm(employeeLevelType::class, $employeeLevel, array('action' => $this->generateUrl('portal_employee_level_upd'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Update', 'attr' => array('data-objid' => $objid)));

        return $this->render('portal/employeeLevel/_form_edit.html.twig', [
            'form' => $form->createView(),
            ]);
    }

    /**
     * @Route("/upd", name="portal_employee_level_upd")
     */
    public function update(Request $request): Response
    {
        $objid = $request->request->get('objid');

        $employeeLevel = $this->getDoctrine()->getRepository(employeeLevel::class)->findOneByGuId($objid);
        $form = $this->createForm(employeeLevelType::class, $employeeLevel, array('action' => $this->generateUrl('portal_employee_level_upd'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Update', 'attr' => array('data-objid' => $objid)));

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($employeeLevel);
                $em->flush();

                return new Response(json_encode(array('status' => 'success', 'message' => 'Updation successful')));
            } else {
            }
        }
        $formView = $this->renderView('portal/employeeLevel/_form_edit.html.twig', array(
            'form' => $form->createView(), ));

        return new Response(json_encode(array('form' => $formView, 'status' => 'danger', 'message' => 'error')));
    }

    /**
     * @Route("/delete", name="portal_employee_level_delete")
     */
    public function delete(Request $request): Response
    {
        $objid = $request->request->get('objid');

        $employeeLevel = $this->getDoctrine()->getRepository(employeeLevel::class)->findOneByGuId($objid);
        $form = $this->createForm(employeeLevelType::class, $employeeLevel, array('action' => $this->generateUrl('portal_employee_level_delete_confirm'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Confirm Delete', 'attr' => array('data-objid' => $objid)));

        return $this->render('portal/employeeLevel/_form_edit.html.twig', [
            'form' => $form->createView(),
            ]);
    }

    /**
     * @Route("/deleteconfirm", name="portal_employee_level_delete_confirm")
     */
    public function deleteConfirm(Request $request): Response
    {
        $objid = $request->request->get('objid');

        $employeeLevel = $this->getDoctrine()->getRepository(EmployeeLevel::class)->findOneByGuId($objid);
        $form = $this->createForm(employeeLevelType::class, $employeeLevel, array('action' => $this->generateUrl('portal_employee_level_delete_confirm'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Confirm Delete', 'attr' => array('data-objid' => $objid)));

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($employeeLevel);
                $em->flush();
                return new Response(json_encode(array('status' => 'success', 'message' => 'Action successful')));
            }
        }
        $formView = $this->renderView('portal/employeeLevel/_form_edit.html.twig', array(
            'form' => $form->createView(), ));

        return new Response(json_encode(array('form' => $formView, 'status' => 'error', 'message' => 'Action Unsuccessful')));
    }
}
