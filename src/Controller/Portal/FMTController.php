<?php

namespace App\Controller\Portal;

use App\Entity\Portal\FMT;
use App\Entity\Portal\Employee;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use App\Services\ProfileWorkspace;
use App\Services\FMTApi;

class FMTController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;
    private $fmtapi;

    public function __construct(ProfileWorkspace $profileWorkspace, FMTApi $fmtapi)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->fmtapi = $fmtapi;
    }


    /**
     * @Route("/fmt/", name="fmt_index")
     */
    public function index(): Response
    {
        $dfConfig = [];
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $dfConfig = ([['field_alias' => 'ministry_name', 'display_text' => 'Name', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
        ['field_alias' => 'ministry_code', 'display_text' => 'Alias', 'operator_type' => ['ILIKE', '='], 'input_type' => 'text', 'input_schema' => ''],
        ]);
        }
        return $this->render('fmt/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
     * @Route("/fmt/list", name="fmt_list")
     */
    public function list(Request $request, PaginatorInterface $paginator): Response
    {
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);

        $query = $this->processQry($dynamicFilters);
        $ministryPaginated = $paginator->paginate($query, $request->query->getInt('page', 1), 20);
        $ministryPaginated->setUsedRoute('fmt_list');

        return $this->render('fmt/_list.html.twig', ['pagination' => $ministryPaginated]);
    }

    /**
     * @Route("/fmt/view", name="fmt_view")
     */
    public function view(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $fmt = $this->getDoctrine()->getRepository(FMT::class)->findOneByGuId($objid);
        }
        return $this->render('fmt/_view.html.twig', [
            'fmt' => $fmt
        ]);
    }

    /**
     * @Route("/fmt/tro", name="fmt_trace_originator")
     */
    public function traceOriginatorView(Request $request): Response
    {
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $fmt = $this->fmtapi->traceOriginator($loggedUser->getId(), $objid);
            if (isset($fmt->data->originator)) {
                $employee_guid = $fmt->data->originator;
                $em = $this->getDoctrine()->getManager();
                $employee = $em->getRepository(Employee::class)->findOneByGuId($employee_guid);
                if ($employee) {
                    $myCon = $em->getConnection();
                    $emp_os = '';
                    $app_version = '';
                    $sqlMS = "SELECT mv.os, mv.app_version FROM gim.user_app_device mv LEFT JOIN gim.employee e ON e.id = emp_id WHERE e.id = :emp";
                    $emp_app_version = $myCon->prepare($sqlMS);
                    $emp_app_version->bindValue('emp', $employee->getId());
                    $emp_app_version->execute();
                    $emp_app_version = $emp_app_version->fetchAll();
                    if ([] != $emp_app_version) {
                        $emp_os = $emp_app_version[0]['os'];
                        $app_version = $emp_app_version[0]['app_version'];
                    }
                    $em = $this->getDoctrine()->getManager();
                    if ($employee->getPhoto()){
                        $photo = base64_encode(stream_get_contents($employee->getPhoto()->getFileData()));
                    } else {
                        $photo = null;
                    }
                    return $this->render('portal/employee/_view.html.twig', [
                       'employee' => $employee,
                       'photo' => $photo,
                       'emp_os' => $emp_os,
                       'emp_app_version' => $app_version]);
                }
            }
        }
        return $this->render('fmt/_view_originator.html.twig', ['fmt' => $fmt ]);
    }

    /**
     * @Route("/fmt/trr", name="fmt_trace_recipients")
     */
    public function traceRecipientsView(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $fmt = $this->getDoctrine()->getRepository(FMT::class)->findOneByGuId($objid);
       
        $myCon = $em->getConnection();
        $sqlMS =<<<SQLSTR
    SELECT
        e."name" AS employee_name,
        e.mobile_no,
        e.email,
        a.message_date,
        a.retracted,
        a.retraction_date,
        b.trace_status,
        b.gu_id AS recipient_trace_request_id
    FROM
        fmt.recipient_trace a
        RIGHT JOIN fmt.recipient_trace_request b ON a.recipient_trace_request_id = b.id
        JOIN gim.employee e ON e.username = a.username
    WHERE
        b.message_report_id = :mrptid    
SQLSTR;
        $fmt_trace_info = $myCon->prepare($sqlMS);
        $fmt_trace_info->bindValue('mrptid', $fmt->getId());
        $fmt_trace_info->execute();
        $fmt_trace_info = $fmt_trace_info->fetchAll();
    }
        return $this->render('fmt/_trace.html.twig', [
            'fmt' => $fmt, 'fmt_trace_info' => $fmt_trace_info
        ]);
    }

    /**
     * @Route("/fmt/trrconfirm", name="fmt_trace_recipients_confirm")
     */
    public function traceRecipientsConfirm(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $fmt = $this->getDoctrine()->getRepository(FMT::class)->findOneByGuId($objid);

        $myCon = $em->getConnection();
        $sqlMS =<<<SQLSTR
        SELECT
        e."name" AS employee_name,
        e.mobile_no,
        e.email,
        a.message_date,
        a.retracted,
        a.retraction_date,
        b.trace_status,
        b.gu_id AS recipient_trace_request_id
    FROM
        fmt.recipient_trace a
        RIGHT JOIN fmt.recipient_trace_request b ON a.recipient_trace_request_id = b.id
        JOIN gim.employee e ON e.username = a.username
    WHERE
        b.message_report_id = :mrptid    
SQLSTR;
        $fmt_trace_info = $myCon->prepare($sqlMS);
        $fmt_trace_info->bindValue('mrptid', $fmt->getId());
        $fmt_trace_info->execute();
        $fmt_trace_info = $fmt_trace_info->fetchAll();
    }

    $api_call_status = $this->fmtapi->traceRecipient($loggedUser->getId(), $objid);
    return new Response(json_encode($api_call_status));

    }

    /**
     * @Route("/fmt/retract", name="fmt_retract_message")
     */
    public function retractMessageView(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $fmt = $this->getDoctrine()->getRepository(FMT::class)->findOneByGuId($objid);
        
        $myCon = $em->getConnection();
        $sqlMS =<<<SQLSTR
    SELECT
        e."name" AS employee_name,
        e.mobile_no,
        e.email,
        a.message_date,
        a.retracted,
        a.retraction_date,
        b.trace_status,
        b.gu_id AS recipient_trace_request_id
    FROM
        fmt.recipient_trace a
        RIGHT JOIN fmt.recipient_trace_request b ON a.recipient_trace_request_id = b.id
        JOIN gim.employee e ON e.username = a.username
    WHERE
        b.message_report_id = :mrptid    
SQLSTR;
        $fmt_trace_info = $myCon->prepare($sqlMS);
        $fmt_trace_info->bindValue('mrptid', $fmt->getId());
        $fmt_trace_info->execute();
        $fmt_trace_info = $fmt_trace_info->fetchAll();
    }
        return $this->render('fmt/_retract.html.twig', [
            'fmt' => $fmt, 'fmt_trace_info' => $fmt_trace_info
        ]);
    }

    /**
     * @Route("/fmt/retractconfirm", name="fmt_retract_message_confirm")
     */
    public function retractMessageConfirm(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $loggedUser = $this->getUser();
        if ($loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $fmt = $this->getDoctrine()->getRepository(FMT::class)->findOneByGuId($objid);
       
        $myCon = $em->getConnection();
        $sqlMS =<<<SQLSTR
    SELECT
        e."name" AS employee_name,
        e.mobile_no,
        e.email,
        a.message_date,
        a.retracted,
        a.retraction_date,
        b.trace_status,
        b.gu_id AS recipient_trace_request_id
    FROM
        fmt.recipient_trace a
        RIGHT JOIN fmt.recipient_trace_request b ON a.recipient_trace_request_id = b.id
        JOIN gim.employee e ON e.username = a.username
    WHERE
        b.message_report_id = :mrptid    
SQLSTR;
        $fmt_trace_info = $myCon->prepare($sqlMS);
        $fmt_trace_info->bindValue('mrptid', $fmt->getId());
        $fmt_trace_info->execute();
        $fmt_trace_info = $fmt_trace_info->fetchAll();
    }
    $api_call_status = $this->fmtapi->retract($loggedUser->getId(), $objid);
    return new Response(json_encode($api_call_status));

    }

    private function processQry($dynamicFilters = null)
    {
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $loggedUser = $this->container->get('security.token_storage')->getToken()->getUser();
        $fieldAliases = ['ministry_name' => 'm.ministryName', 'ministry_code' => 'm.ministryCode'];
        $quer = $em->createQueryBuilder('e')
                ->select('fmt.guId, fmt.traceID,fmt.messageType, e.employeeName as senderID, fmt.receiverID, fmt.submittedOn, ei.username as submittedBy')
                ->from('App:Portal\FMT', 'fmt')
                ->join('App:Portal\Employee', 'e', 'WITH', 'fmt.senderID = e.jabberId')
                ->join('App:Portal\User', 'ei', 'WITH', 'fmt.submittedBy = ei.id');
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
