<?php

namespace App\Controller\Masters;

use App\Entity\Masters\OrganizationType;
use App\Form\Masters\OrganizationTypeType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/msr/out")
 */
class OrganizationTypeController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    /**
     * @Route("/", name="msr_out_index")
     */
    public function index(Request $request): Response
    {
        $repository = $this->getDoctrine()->getRepository(OrganizationType::class);
        $OrganizationTypes = $repository->findAll();

        $OrganizationType = new OrganizationType();
        $form = $this->createForm(OrganizationTypeType::class, $OrganizationType)->add('saveAndCreateNew', SubmitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($OrganizationType);
            $em->flush();

            return $this->redirectToRoute('msr_out_index');
        }

        return $this->render('masters/organization_type/index.html.twig', [
            'OUTypes' => $OrganizationTypes,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/ins", methods={"POST"}, name="msr_out_ins")
     */
    public function organizationTypeInsert(Request $request): Response
    {
        $OrganizationType = new OrganizationType();

        $form = $this->createForm(OrganizationType::class, $OrganizationType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($OrganizationType);
            $em->flush();

            return $this->redirectToRoute('msr_out_index');
        }
    }
}
