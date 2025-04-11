<?php

namespace App\Controller\Portal;

use App\Entity\Portal\ExternalApps;
use App\Entity\Portal\Employee;
use App\Entity\Portal\User;
use App\Form\Portal\ExternalAppsType;
use App\Form\Portal\EmployeeType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use App\Services\ProfileWorkspace;
use App\Services\ExternalAppService;

class ExternalAppController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;
    private $externalAppService;

    public function __construct(ProfileWorkspace $profileWorkspace,ExternalAppService $externalAppService)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->externalAppService = $externalAppService;
    }

    /**
     * @Route("/portal/externalApp/", name="portal_externalApp_index")
     */
    public function index(): Response
    {
        $loggedUser = $this->getUser();
        $dfConfig = ([['field_alias' => "app_title", 'display_text' => "App Title", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => '']]);
        return $this->render('portal/externalApp/index.html.twig', ['dfConfig' => $dfConfig]);

        return $this->render('portal/externalApp/index.html.twig');
    }

    /**
     * @Route("/portal/externalApp/list", name="portal_externalApp_list")
     */
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);
    
        $query = $this->processQry($dynamicFilters);

        $externalPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
        $externalPaginated->setUsedRoute('portal_externalApp_list');

        return $this->render('portal/externalApp/_list.html.twig', ['pagination' => $externalPaginated]);

    }

    private function processQry($dynamicFilters = null) {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();

        $fieldAliases = ['app_title' => 'e.appTitle'];
        
        $quer = $em->createQueryBuilder('e')
                ->select('e.id,e.clientId,e.appName,e.appTitle,e.hmacKey,e.ipWhiteList,e.privatePolicyLink, e.integrationScope, e.active, e.appCoverImageId, e.userCount, e.gatewayIntegration, e.chatbotIntegration, e.subscriptionIntegration, e.authIntegration, e.gatewayJid, e.chatbotJid, e.subscriptionJid, e.authJid, e.appDescription, e.appVersion, e.parentOuId, e.ratelimiterId, e.ratelimiterId, e.allowPortalMessaging')
                ->from('App:Portal\ExternalApps', 'e')
                ->orderBy('e.id', 'DESC');
        if ($dynamicFilters) {
            foreach ($dynamicFilters as $k => $v) {
                if ($v['operator'] === 'ILIKE') {
                    $quer->where($v['operator'] . "(" . $fieldAliases[$k] . ",:$k )=TRUE");
                    $quer->setParameter($k, '%' . trim($v['fvalue']) . '%');
                } else {
                    $quer->where($fieldAliases[$k] . " " . $v['operator'] . " :$k");
                    $quer->setParameter($k, trim($v['fvalue']));
                }
            }
        }
        return $quer->getQuery();

    }


    /**
     * @Route("/portal/externalApp/new", name="portal_externalApp_new")
     */
    public function new(): Response
    {
        $payload = [
            // 'appTitle' => $ExternalApps->getAppTitle(), 
            // 'description' => $ExternalApps->getAppDescription(), 
            // 'appName' => $ExternalApps->getAppName(), 
            // 'hmacKey' => $ExternalApps->getHmacKey(), 
            // 'ipWhiteList' => $ExternalApps->getIpWhiteList()
            "appTitle" => "My Test App",
            "appName"=>"my-test-app30",
            "description"=>"testing",
            "gatewayJid"=>"apigateway.gimkerala.nic.in",
            "ipWhiteList"=>"10.162.5.85,127.0.0.1",
            "homeURL"=>"http://wwww.testapp.com&quot",
            "parentOuId"=>2,
            "ratelimiterId"=>1,
            "allowPortalMessaging"=>true,
            "parent_ou_id"=>2,
            "appContact"=>[ 
                [
                    "name"=>"Manoj P A",
                    "contactType"=>"C",
                    "mobileNo"=>"9562735438",
                    "email"=>"bose.vipin@nic.in"
                ],
                [ 
                    "name"=>"Manoj P A",
                    "contactType"=>"C",
                    "mobileNo"=>"9562735438",
                    "email"=>"bose.vipin@nic.in"
                ]],
            "userCount" => 1,
        ];
        die(\json_encode($payload));

        $externalApp = $this->externalAppService->externalAppRegister(123, \json_encode($payload));
        if($externalApp == false)
        {
            return new Response(json_encode(array('status' => 'false', 'message' => 'You are offline')));
        }
        die;
        $ExternalApps = new ExternalApps();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        // $ExternalApps->setGuId($uuid->toString());
        $ExternalApps->setClientId($uuid->toString());
        $form = $this->createForm(ExternalAppsType::class, $ExternalApps, array('action' => $this->generateUrl('portal_externalApp_ins'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
       
        return $this->render('portal/externalApp/_form_new.html.twig', ['form' => $form->createView() ]);
    }

    /**
     * @Route("/portal/externalApp/ins", name="portal_externalApp_ins")
     */
    public function insert(Request $request): Response
    {
        $ExternalApps = new ExternalApps();
        $loggedUser = $this->getUser();
        $form = $this->createForm(ExternalAppsType::class, $ExternalApps, array('action' => $this->generateUrl('portal_vhost_ins'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
       
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if($form->isSubmitted())
            {
                $appName = $form['appName']->getData();
                $checkAppName = $this->check($appName);
                if ($checkAppName['status'] == false) 
                {
                    if ($form->isValid()) 
                    {
                        $payload = [
                            // 'appTitle' => $ExternalApps->getAppTitle(), 
                            // 'description' => $ExternalApps->getAppDescription(), 
                            // 'appName' => $ExternalApps->getAppName(), 
                            // 'hmacKey' => $ExternalApps->getHmacKey(), 
                            // 'ipWhiteList' => $ExternalApps->getIpWhiteList()
                            "appTitle" => "My Test App",
                            "appName"=>"my-test-app30",
                            "description"=>"testing",
                            "gatewayJid"=>"apigateway.gimkerala.nic.in",
                            "ipWhiteList"=>"10.162.5.85,127.0.0.1",
                            "homeURL"=>"http://wwww.testapp.com&quot",
                            "parentOuId"=>2,
                            "ratelimiterId"=>1,
                            "allowPortalMessaging"=>true,
                            "parent_ou_id"=>2,
                            "appContact"=>[ 
                                [
                                    "name"=>"Manoj P A",
                                    "contactType"=>"C",
                                    "mobileNo"=>"9562735438",
                                    "email"=>"bose.vipin@nic.in"
                                ],
                                [ 
                                    "name"=>"Manoj P A",
                                    "contactType"=>"C",
                                    "mobileNo"=>"9562735438",
                                    "email"=>"bose.vipin@nic.in"
                                ]],
                            "userCount" => 1,
                        ];
                        $externalApp = $this->externalAppService->externalAppRegister($loggedUser->getId(), \json_encode($payload));
                        if($externalApp == false)
                        {
                            return new Response(json_encode(array('status' => 'false', 'message' => 'You are offline')));
                        }
                        // return new Response(json_encode(['status' => 'success', 'message' => 'The group has been created successfully !!']));
                        // $externalServiceCallStatus = $this->externalAppService ->externalAppServiceCall($form['appTitle']->getData(),$form['clientId']->getData(),$form['appName']->getData(),$form['appDescription']->getData(), $form['mobileNumber']->getData());
                        // return new Response(json_encode(array('status' => $externalServiceCallStatus, 'message' => 'Saved Successfully'))); 
                    }
                }
                else {
                    $this->addFlash('danger', 'App Name Already Exists.');
                    return $this->redirect($this->generateUrl('portal_externalApp_index'));
                }
            }            
        }

        $formView = $this->renderView('portal/externalApp/_form_new.html.twig', ['form' => $form->createView()]);
        return new Response(json_encode(array('form' => $formView, 'status' => 'error')));
    }

    /**
     * @Route("/portal/externalApp/edit", name="portal_externalApp_edit")
     */
    public function edit(Request $request): Response
    {
        $clientId = $request->request->get('objid');
       
        $ExternalApps = $this->getDoctrine()->getRepository(ExternalApps::class)->findOneByClientId($clientId);
        $form = $this->createForm(ExternalAppsType::class, $ExternalApps, array('action' => $this->generateUrl('portal_externalApp_upd'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Update', 'attr' => array('data-objid' => $clientId)));
        return $this->render('portal/externalApp/_form_edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/portal/externalApp/upd", name="portal_externalApp_upd")
     */
    public function update(Request $request): Response
    {
        $clientId = $request->request->get('objid');

        $ExternalApps = $this->getDoctrine()->getRepository(ExternalApps::class)->findOneByClientId($clientId);
        $form = $this->createForm(ExternalAppsType::class, $ExternalApps, array('action' => $this->generateUrl('portal_externalApp_upd'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Update', 'attr' => array('data-objid' => $clientId)));
      
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if($form->isSubmitted())
            {
                $appName = $form['appName']->getData();
                $checkAppName = $this->check($appName);
                if ($checkAppName['status'] == false) 
                {
                    if ($form->isValid()) {
                        $em = $this->getDoctrine()->getManager();               
                        $em->persist($ExternalApps);
                        $em->flush();

                        return new Response(json_encode(array('status' => 'success', 'message' => 'Updation successful')));
                    }
                }
                else 
                {
                    $this->addFlash('danger', 'App Name Already Exists.');
                    return $this->redirect($this->generateUrl('portal_externalApp_index'));
                }
            }
        }

        $formView = $this->renderView('portal/externalApp/_form_edit.html.twig', [
            'form' => $form->createView()
        ]);

        return new Response(json_encode(array('form' => $formView, 'status' => 'error')));
    }

    /**
     * @Route("/portal/externalApp/delete", name="portal_externalApp_delete")
     */
    public function delete(Request $request): Response
    {
        $objid = $request->request->get('objid');

        $ExternalApps = $this->getDoctrine()->getRepository(ExternalApps::class)->findOneByGuId($objid);
        $form = $this->createForm(ExternalAppsType::class, $ExternalApps, array('action' => $this->generateUrl('portal_externalApp_delete_confirm'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Confirm Delete', 'attr' => array('data-objid' => $objid)));

        return $this->render('portal/externalApp/_form_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/portal/externalApp/deleteconfirm", name="portal_externalApp_delete_confirm")
     */
    public function deleteConfirm(Request $request): Response
    {
        $objid = $request->request->get('objid');        
        $ExternalApps = $this->getDoctrine()->getRepository(ExternalApps::class)->findOneByGuId($objid);
        $Employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByGuId($objid);
        $User = $this->getDoctrine()->getRepository(User::class)->findOneByGuId($objid);
        $form = $this->createForm(ExternalAppsType::class, $ExternalApps, array('action' => $this->generateUrl('portal_externalApp_delete_confirm'), 'attr' => array('id' => 'frmBaseModal')))->add('btnUpdate', SubmitType::class, array('label' => 'Confirm Delete', 'attr' => array('data-objid' => $objid)));
        
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {

                $em = $this->getDoctrine()->getManager(); 
                $User = $em->getRepository("App:Portal\User")->findOneByGuId($objid); 
                $Employee = $em->getRepository("App:Portal\Employee")->findOneByGuId($objid);
                
                $em->remove($ExternalApps);
                $em->remove($Employee);
                $em->remove($User);
                $em->flush();
                return new Response(json_encode(array('status' => 'success', 'message' => 'Deletion successful')));
            }            
        }

        $formView = $this->renderView('portal/externalApp/_form_edit.html.twig', [
            'form' => $form->createView(),
        ]);

        return new Response(json_encode(array('form' => $formView, 'status' => 'error')));
    }

    /**
     * @Route("/portal/externalApp/info", name="portal_externalApp_info")
     */
    public function appInfo(Request $request): Response
    {
        $clientId = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $ExternalApps = $em->getRepository(ExternalApps::class)->findOneByClientId($clientId);
        return $this->render('portal/externalApp/_app_info.html.twig',
        ['ExternalApps' => $ExternalApps,
        'label'=>'App Info']);
    }

    public function check($check,$container = null) {
        if ($container) {
            $this->container = $container;
        }
        $em = $this->getDoctrine()->getManager();
        $ExternalApps = new ExternalApps();
        $ExternalApps = $em->getRepository(ExternalApps::class)->findOneByAppName($check);
        if ($ExternalApps === null) {
            $status = array('status' => false);
        } else {
            $status = array('status' => true);
        }
        return $status;
    }

    /**
     * @Route("/portal/externalApp/activateOrDeactivate", name="portal_externalApp_activate_deactivate")
     */
    public function appActivateDeactivate(Request $request): Response
    {
        $clientId = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $ExternalApps = $em->getRepository(ExternalApps::class)->findOneByClientId($clientId);
        return $this->render('portal/externalApp/_app_info.html.twig',
        ['ExternalApps' => $ExternalApps,
        'label' => 'Activate Or Deactivate Apps']);
    }

    /**
     * @Route("/portal/externalApp/Deactivate", name="portal_externalApp_deactivate")
     */
    public function appDeactivate(Request $request): Response
    {
        $clientId = $request->request->get('objid');
        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $ExternalApps = $em->getRepository(ExternalApps::class)->findOneByClientId($clientId);
        $payload = ['clientId' => $ExternalApps->getClientId()];
        $externalApp = $this->externalAppService->externalAppActivateDeactivate($loggedUser->getId(), \json_encode($payload),'deactivate');
        if($externalApp == false)
        {
            return new Response(json_encode(array('status' => 'false', 'message' => 'You are offline')));
        }
        
    }

    /**
     * @Route("/portal/externalApp/activate", name="portal_externalApp_activate")
     */
    public function appActivate(Request $request): Response
    {
        $clientId = $request->request->get('objid');
        $loggedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $ExternalApps = $em->getRepository(ExternalApps::class)->findOneByClientId($clientId);
        $payload = ['clientId' => $ExternalApps->getClientId()];
        $externalApp = $this->externalAppService->externalAppActivateDeactivate($loggedUser->getId(), \json_encode($payload),'activate');
        if($externalApp == false)
        {
            return new Response(json_encode(array('status' => 'false', 'message' => 'You are offline')));
        }
        
    }
   
    /**
     * @Route("/portal/externalApp/rate_limiter", name="portal_externalApp_rate_limiter")
     */
    public function appRateLimiter(Request $request): Response
    {
        $clientId = $request->request->get('objid');
        $em = $this->getDoctrine()->getManager();
        $ExternalApps = $em->getRepository(ExternalApps::class)->findOneByClientId($clientId);
        return $this->render('portal/externalApp/_app_info.html.twig',
        ['ExternalApps' => $ExternalApps,
        'label' => 'Modify Rate Limiter']);
    }
}
