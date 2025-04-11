<?php

namespace App\Controller\Portal;

use App\Entity\Masters\Ministry;
use App\Entity\Portal\Organization;
use App\Entity\Portal\OrganizationUnit;
use App\Entity\Portal\Designation;
use App\Entity\Portal\EmployeeLevel;
use App\Form\Portal\MinistryType;
use App\Form\Portal\OrganizationType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use App\Services\ProfileWorkspace;

class MinistryController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;

    public function __construct(ProfileWorkspace $profileWorkspace)
    {
        $this->profileWorkspace = $profileWorkspace;
    }


    /**
     * @Route("/portal/min/", name="portal_ministry_index")
     */
    public function index(): Response
    {
        $dfConfig = [];
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN') || $loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $dfConfig = ([['field_alias' => 'ministry_name', 'display_text' => 'Name', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
        ['field_alias' => 'ministry_code', 'display_text' => 'Alias', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
        ]);
        }
        return $this->render('portal/ministry/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
     * @Route("/portal/min/list", name="portal_ministry_list")
     */
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);

        $query = $this->processQry($dynamicFilters);
        $ministryPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
        $ministryPaginated->setUsedRoute('portal_ministry_list');

        return $this->render('portal/ministry/_list.html.twig', ['pagination' => $ministryPaginated]);
    }

    /**
     * @Route("/portal/min/new", name="portal_ministry_new")
     */
    public function new(): Response
    {
        $Ministry = new Ministry();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $Ministry->setGuId($uuid->toString());
        $form = $this->createForm(MinistryType::class, $Ministry, array('action' => $this->generateUrl('portal_ministry_ins'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
       
        return $this->render('portal/ministry/_form_new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/portal/min/ins", name="portal_ministry_ins")
     */
    public function insert(Request $request): Response
    {
        $Ministry = new Ministry();
        $form = $this->createForm(MinistryType::class, $Ministry, array('action' => $this->generateUrl('portal_ministry_ins'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
            
        if ('POST' == $request->getMethod()) {

            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();              
                $em->persist($Ministry);
                $em->flush();

                return new Response(json_encode(array('status' => 'success', 'message' => 'Saved Successfully')));
            }
        }

        $formView = $this->renderView('portal/ministry/_form_new.html.twig', [
            'form' => $form->createView()
        ]);

        return new Response(json_encode(array('form' => $formView, 'status' => 'error', 'message' => 'Unexpected error')));
    }

    /**
     * @Route("/portal/min/edit", name="portal_ministry_edit")
     */
    public function edit(Request $request): Response
    {
        $objid = $request->request->get('objid');        
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
           $Ministry = $this->getDoctrine()->getRepository(Ministry::class)->findOneByGuId($objid);
        } else {
            $ministry = $this->profileWorkspace->getMinistry()->getId();
            $Ministry = $this->getDoctrine()->getRepository(Ministry::class)->findOneBy(['guId'=>$objid,'ministryCode'=>$ministry]);
        }        
        $form = $this->createForm(MinistryType::class, $Ministry, array('action' => $this->generateUrl('portal_ministry_upd'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Update', 'attr' => array('data-objid' => $objid)));
        return $this->render('portal/ministry/_form_edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/portal/min/upd", name="portal_ministry_upd")
     */
    public function update(Request $request): Response
    {
        $objid = $request->request->get('objid');       
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $Ministry = $this->getDoctrine()->getRepository(Ministry::class)->findOneByGuId($objid);
        } else {
            $ministry = $this->profileWorkspace->getMinistry()->getId();
            $Ministry = $this->getDoctrine()->getRepository(Ministry::class)->findOneBy(['guId' => $objid, 'ministryCode' => $ministry]);
        }

        $form = $this->createForm(MinistryType::class, $Ministry, array('action' => $this->generateUrl('portal_ministry_upd'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Update', 'attr' => array('data-objid' => $objid)));
      
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();               
                $em->persist($Ministry);
                $em->flush();

                return new Response(json_encode(array('status' => 'success', 'message' => 'Updation successful')));
            }
        }

        $formView = $this->renderView('portal/ministry/_form_edit.html.twig', [
            'form' => $form->createView()
        ]);

        return new Response(json_encode(array('form' => $formView, 'status' => 'error')));
    }

    /**
     * @Route("/portal/min/delete", name="portal_ministry_delete")
     */
    public function delete(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $Ministry = $this->getDoctrine()->getRepository(Ministry::class)->findOneByGuId($objid);
        } else {
            $ministry = $this->profileWorkspace->getMinistry()->getId();
            $Ministry = $this->getDoctrine()->getRepository(Ministry::class)->findOneBy(['guId' => $objid, 'ministryCode' => $ministry]);
        }       
        return $this->render('portal/ministry/_view.html.twig', [
            'ministry' => $Ministry
        ]);
    }

    /**
     * @Route("/portal/min/deleteconfirm", name="portal_ministry_delete_confirm")
     */
    public function deleteConfirm(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();         
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $Ministry = $this->getDoctrine()->getRepository(Ministry::class)->findOneByGuId($objid);
        } else {
            $ministry = $this->profileWorkspace->getMinistry()->getId();
            $Ministry = $this->getDoctrine()->getRepository(Ministry::class)->findOneBy(['guId' => $objid, 'ministryCode' => $ministry]);
        }
          
       
        if (!$em->createQueryBuilder('o')->select('COUNT(o.id)')->from('App:Portal\Organization', 'o')->where('o.ministry = :ministry')->setParameter(':ministry', $Ministry)->getQuery()->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR) > 0)
        {
            if ('POST' == $request->getMethod()) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($Ministry);
                $em->flush();
                return new Response(json_encode(array('status' => 'success', 'message' => 'Deletion successful')));
            }
        }
        else 
        {
            return new Response(json_encode(array('status' => 'error', 'message' => 'Action unsuccessful, Organizations are already assigned to this ministry')));
        }
    }
    /**
     * @Route("/portal/min/getosbyministry", name="portal_ministry_get_os_by_ministry")
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
     * @Route("/portal/min/getdddbyorganization", name="portal_get_ou_dg_el_by_organization")
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
     * @Route("/portal/min/get_mbr", name="portal_get_ministry_by_role")
     */
    public function getMinistryByRole(Request $request, $param): Response
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $ministry = $em->getRepository(Ministry::class)->findAll();
        }else{
            $ministry = $this->profileWorkspace->getMinistry()->getId();
            $ministry = $em->getRepository(Ministry::class)->findById($ministry);
        }

        return $this->render('CodeFinder/parent1.html.twig', ['masterEntity' => $ministry, 'param' => $param]);
    
    }

    private function processQry($dynamicFilters = null) {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $fieldAliases = ['ministry_name' => 'm.ministryName', 'ministry_code' => 'm.ministryCode'];
        $quer = $em->createQueryBuilder('e')
                ->select('m.guId,m.ministryCode,m.ministryName')
                ->from('App:Masters\Ministry', 'm');
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
