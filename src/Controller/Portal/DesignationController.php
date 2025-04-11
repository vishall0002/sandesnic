<?php

namespace App\Controller\Portal;

use App\Entity\Portal\Designation;
use App\Entity\Portal\Organization;
use App\Form\Portal\DesignationType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Services\ProfileWorkspace;
use Doctrine\ORM\EntityRepository;
use App\Services\XMPPGeneral;

/**
 * @Route("/portal/dsg")
 */
class DesignationController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;
    public function __construct(ProfileWorkspace $profileWorkspace, XMPPGeneral $xmpp)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->xmppGeneral = $xmpp;
    }
    /**
     * @Route("/", name="portal_designation_index")
     */
    public function index(): Response
    {
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN') or $loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $dfConfig = ([['field_alias' => "designation_name", 'display_text' => "Designation", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
                ['field_alias' => "organization_name", 'display_text' => "Organization", 'operator_type' => ['=', 'ILIKE'], 'input_type' => "codefinder", 'input_schema' => 'Organization']
            ]);
        } else {
            $dfConfig = ([['field_alias' => "designation_name", 'display_text' => "Designation", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
            ]);
        }
        return $this->render('portal/designation/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
     * @Route("/list", name="portal_designation_list")
     */
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);

        $query = $this->processQry($dynamicFilters);
        $designationPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
        $designationPaginated->setUsedRoute('portal_designation_list');

        return $this->render('portal/designation/_list.html.twig', ['pagination' => $designationPaginated]);
    }

    /**
     * @Route("/new", name="portal_designation_new")
     */
    public function new(): Response
    {
        $Designation = new Designation();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $Designation->setGuId($uuid->toString());
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $Organization = null;
            $Ministry = null;
        } else {
            $Ministry = $this->profileWorkspace->getMinistry()->getId();
            $Organization = $this->profileWorkspace->getOrganization();
        }
       
        $form = $this->createForm(DesignationType::class, $Designation, ['action' => $this->generateUrl('portal_designation_ins'),'ministry' => $Ministry, 'attr' => ['id' => 'frmBaseModal'], 'org' => $Organization ])->add('btnInsert', SubmitType::class, array('label' => 'Save', 'attr' => array('data-objid' => '')));
        return $this->render('portal/designation/_form_new.html.twig', [
            'form' => $form->createView(),
            'organization' => $Organization
        ]);
    }

    /**
     * @Route("/ins", name="portal_designation_ins")
     */
    public function insert(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $loggedUser = $this->getUser();
        $Organization = "";
        $Ministry = "";
        if (!$loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $Organization = $this->profileWorkspace->getOrganization();
            $Ministry = $this->profileWorkspace->getMinistry()->getId();
        }
        $Designation = new Designation();
        $form = $this->createForm(DesignationType::class, $Designation, ['action' => $this->generateUrl('portal_designation_ins'),'ministry'=> $Ministry, 'attr' => ['id' => 'frmBaseModal'], 'org' => $Organization ])->add('btnInsert', SubmitType::class, array('label' => 'Save', 'attr' => array('data-objid' => '')));

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
                    $Organization_id = $request->request->get('codefinderId');
                    $Organization = $em->getRepository('App:Portal\Organization')->findOneById($Organization_id);
                }
                $uuid = \Ramsey\Uuid\Uuid::uuid4();
                $Designation->setDesignationCode($uuid->toString());
                $Designation->setOrganization($Organization);
                $em->persist($Designation);
                $em->flush();
                $this->xmppGeneral->updateCache('designation', $Designation->getId());
                return new Response(json_encode(array('status' => 'success', 'message' => 'Saved Successfully')));
            }
        }
        $formView = $this->renderView('portal/designation/_form_new.html.twig', array(
                    'form' => $form->createView(),
                    'organization' => $Organization ));

        return new Response(json_encode(array('form' => $formView, 'status' => 'error', 'message' => 'Updation unsuccessful')));
    }

    /**
     * @Route("/edit", name="portal_designation_edit")
     */
    public function edit(Request $request): Response
    {
        $objid = $request->request->get('objid');

        $Designation = $this->getDoctrine()->getRepository(Designation::class)->findOneByGuId($objid);
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $Organization = null;
            $Ministry = null;
        } else {
            $Organization = $this->profileWorkspace->getOrganization();
            $Ministry = $this->profileWorkspace->getMinistry()->getId();
        }
        $form = $this->createForm(DesignationType::class, $Designation, ['action' => $this->generateUrl('portal_designation_upd'),'ministry' => $Ministry, 'attr' => ['id' => 'frmBaseModal'], 'org' => $Organization])->add('btnUpdate', SubmitType::class, array('label' => 'Update', 'attr' => array('data-objid' => $objid)));

        return $this->render('portal/designation/_form_edit.html.twig', [
            'form' => $form->createView(),
            'organization' => $Organization
        ]);
    }

    /**
     * @Route("/upd", name="portal_designation_upd")
     */
    public function update(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $Designation = $this->getDoctrine()->getRepository(Designation::class)->findOneByGuId($objid);
        $loggedUser = $this->getUser();
        $Organization = "";
        $Ministry = "";
        if (!$loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $Organization = $this->profileWorkspace->getOrganization();
            $Ministry = $this->profileWorkspace->getMinistry()->getId();
        }
        $form = $this->createForm(DesignationType::class, $Designation, ['action' => $this->generateUrl('portal_designation_upd'),'ministry' => $Ministry, 'attr' => ['id' => 'frmBaseModal'], 'org' => $Organization])->add('btnUpdate', SubmitType::class, array('label' => 'Update', 'attr' => array('data-objid' => $objid)));

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
                    $Organization_id = $request->request->get('codefinderId');
                    if ($Organization_id != 'undefined') {
                        $Organization = $em->getRepository('App:Portal\Organization')->findOneById($Organization_id);
                        $Designation->setOrganization($Organization);
                    }
                } elseif ($loggedUser->hasRole('ROLE_O_ADMIN')) {
                    $Designation->setOrganization($Organization);
                }
                $em->persist($Designation);
                $em->flush();
                $this->xmppGeneral->updateCache('designation', $Designation->getId());

                return new Response(json_encode(array('status' => 'success', 'message' => 'Updation successful')));
            }
        }
        $formView = $this->renderView('portal/designation/_form_edit.html.twig', array(
            'form' => $form->createView(),
            'organization' => $Organization ));

        return new Response(json_encode(array('form' => $formView, 'status' => 'error', 'message' => 'Updation unsuccessful')));
    }

    /**
     * @Route("/delete", name="portal_designation_delete")
     */
    public function delete(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $Designation = $this->getDoctrine()->getRepository(Designation::class)->findOneByGuId($objid);
        return $this->render('portal/designation/_view.html.twig', [
           'designation' =>  $Designation
         ]);
    }

    /**
     * @Route("/deleteconfirm", name="portal_designation_delete_confirm")
     */
    public function deleteConfirm(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $Designation = $this->getDoctrine()->getRepository(Designation::class)->findOneByGuId($objid);
        if (!$em->createQueryBuilder('e')->select('COUNT(e.id)')->from('App:Portal\Employee', 'e')->where('e.designation = :designation')->setParameter(':designation', $Designation)->getQuery()->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_SINGLE_SCALAR) > 0) {
            if ('POST' == $request->getMethod()) {
                $em = $this->getDoctrine()->getManager();
                $this->xmppGeneral->removeCache('designation', $Designation->getId());
                $em->remove($Designation);
                $em->flush();

                return new Response(json_encode(array('status' => 'success', 'message' => 'Action successful')));
            }
        } else {
            return new Response(json_encode(array('status' => 'error', 'message' => 'Action unsuccessful, Members are already assigned to this designation')));
        }
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
        } else {
            $Ministry = $this->profileWorkspace->getMinistry()->getId();
            $Organization = $this->profileWorkspace->getOrganization()->getId();
        }

        $fieldAliases = ['organization_name' => 'o.organizationName', 'designation_name' => 'd.designationName'];
        $quer = $em->createQueryBuilder('e')
                ->select('d.id,d.guId,d.designationCode,d.designationName,o.organizationName, m.ministryName')
                ->from('App:Portal\Designation', 'd')
                ->leftJoin('App:Portal\Organization', 'o', 'WITH', 'o.id = d.organization')
                ->leftJoin('App:Masters\Ministry', 'm', 'WITH', 'o.ministry = m.id')
                ->where('d.organization = :org OR :org = 0')
                ->setParameter('org', $Organization);
        if ($loggedUser->hasRole('ROLE_MINISTRY_ADMIN')) {
            $quer->andWhere('o.ministry = :min OR :min = 0')
                    ->setParameter('min', $Ministry);
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
}
