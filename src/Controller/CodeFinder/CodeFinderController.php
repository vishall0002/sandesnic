<?php

namespace App\Controller\CodeFinder;

use App\Form\CodeFinder\CodeFinderType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Response;

class CodeFinderController extends AbstractController implements \App\Interfaces\AuditableControllerInterface {

    /**
     * @Route("/finder", name="app_code_finder_show_form")
     *
     */
    public function showFormAction(Request $request) {
        $form = $this->createCreateForm();
        $formView = $this->renderView('CodeFinder/showForm.html.twig', array(
            'form' => $form->createView(),));

        return new JsonResponse(['form' => $formView]);
    }

    private function createCreateForm() {
        $form = $this->createForm(CodeFinderType::class);

        return $form;
    }

    /**
     * @Route("/form-for-code-finder", name="app_code_finder_show_form_for_code_finder")
     */
    public function codeFinderFormAction(Request $request, KernelInterface $appKernel) {
        $em = $this->getDoctrine()->getManager();
    //    dump($appKernel->getProjectDir());
    
    //    die;
//        $root = new Kernel())
        $jsonFilePrefixName = $request->get('usage');
        $selectType = $request->get('selectType') ? $request->get('selectType') : '';
        $selectFor = $request->get('for') ? $request->get('for') : '';
        $custFunctionName = $request->get('custfunction') ? $request->get('custfunction') : '';
//        $jsonFileLoc = $root->locateResource('@App/Resources/JSON/CodeFinder');



        $subscriberType = $this->getDoctrine()->getRepository(\App\Entity\Masters\ListSubscriberType::class)->findOneByCfCode($jsonFilePrefixName);

        
        $title = $subscriberType ? $subscriberType->getSubscriberType() : '';
        $jsonFileLoc = $appKernel->getProjectDir() . '/src/JSON/CodeFinder';
        $finder = new Finder();
        $masterEntity = array();
        $cfJson = '';
        $finder->files()->name($jsonFilePrefixName . '.json')->in($jsonFileLoc);

        

        foreach ($finder as $file) {
            $cfJson = $file->getContents();
        }
        if ($cfJson == '') {
            return new JsonResponse(['type' => 'danger', 'msg' => "Matching resource not found. Enter proper value in the field."]);
        }


        $fileContentsArr = json_decode($cfJson, true);
        $depthCount = count($fileContentsArr[$jsonFilePrefixName]);
        $masterEntity = '';
        foreach ($fileContentsArr[$jsonFilePrefixName] as $key => $parentObj) {
            if ($key == 'Parent1' && !array_key_exists("path", $parentObj)) {
                $masterEntity = $em->getRepository('App:' . $parentObj['Entity'])->findAll();
            }
        }


      
        $formView = $this->renderView('CodeFinder/codeFinderForm.html.twig', compact('masterEntity', 'depthCount', 'cfJson', 'fileContentsArr', 'jsonFilePrefixName', 'selectType', 'selectFor', 'custFunctionName'));

        // dump($formView);
        // die;
        return new JsonResponse(['type' => 'success', 'finderFor' => $jsonFilePrefixName, 'title' => $title, 'form' => $formView]);
    }

    /**
     * @Route("/parnt1_test", name="code_finder_parent_test")
     */
    public function test(Request $request, $param) {
        $em = $this->getDoctrine()->getManager();
        $masterEntity = $em->getRepository('App:Masters\State')->findAll();
        return $this->render('CodeFinder/parent1.html.twig', array('masterEntity' => $masterEntity, 'param' => $param));
    }
     /**
     * @Route("/get-cfdists", name="get_cf_districts")
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
     * @Route("/get-cfsts", name="get_cf_states")
     */
    public function getStates(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $country = $request->request->get('country');
        $qry = $em->createQueryBuilder('m.id,m.state')
                ->select('m.id,m.state')
                ->from('App:Masters\State', 'm')
                ->where('m.country = 1')
                // ->where('m.country = :country')
                // ->setParameter('country', $country)
                ->getQuery();
        $sboxResult = json_encode($qry->getResult());

        return new Response($sboxResult);
    }
     /**
     * @Route("/get-cfapps", name="get_cf_externalApp")
     */
    public function getExternalApps(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $country = $request->request->get('country');
        $qry = $em->createQueryBuilder('m.id,m.state')
                ->select('m.id,m.appTitle')
                ->from('App:Portal\ExternalApps', 'm')
                ->where('m.allowPortalMessaging IS NULL')
                // ->where('m.allowPortalMessaging = :allowPortalMessaging')
                // ->setParameter('allowPortalMessaging',TRUE)
                ->getQuery();
        $sboxResult = json_encode($qry->getResult());

        return new Response($sboxResult);
    }
    
}
