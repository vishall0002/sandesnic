<?php

namespace App\Controller;

use App\Entity\Portal\Organization;
use App\Services\DefaultValue;
use App\Services\ImageProcess;
use App\Services\PortalMetadata;
use App\Services\ProfileWorkspace;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class TBController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $defaultValue;
    private $imageProcess;
    

    public function __construct(DefaultValue $defVal, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata, ImageProcess $imageProcess)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->defaultValue = $defVal;
        $this->imageProcess = $imageProcess;
        
    }

    /**
     * @Route("/dash/topbottomstatistics", name="app_dashboard_top_bottom_statistics")
     */
    public function topBottomStatistics(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $ogid = $request->request->get('objid');
        if (!$ogid) {
            $session = new Session();
            $ogid = $session->get('lastobjid');
        }
        $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($ogid);
        $date_range = $request->request->get('input-daterange');
        $type = $request->request->get('type');
        $record = $request->request->get('record');
        if (null == $record) {
            $record = 5;
        }
        if (null != $date_range) {
            $dates = explode(' - ', $date_range);
            $date_from = \DateTimeImmutable::createFromFormat('d/m/Y', $dates[0]);
            $date_to = \DateTimeImmutable::createFromFormat('d/m/Y', $dates[1]);
            $myCon = $em->getConnection();
            if ('top' == $type) {
                $dql = <<<SQLMS
                SELECT
                    ou.gu_id,
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as C1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as C2
                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_state = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    5 DESC
                LIMIT $record
SQLMS;
                $qrychat = $myCon->prepare($dql);
                $qrychat->bindValue(':ogid', $ogid);
                $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
                $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
                $qrychat->execute();
                $topStates = $qrychat->fetchAll();
                $result1 = $this->renderView('dashboard/tb/_top_states.html.twig', [
                    'records' => $topStates,
                    'organization' => $Organization,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'record' => $record,
                ]);

                $dql = <<<SQLMS
                SELECT
                    ou.gu_id,
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as c1,
            CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as c2
                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_hog = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    5 DESC
                    limit $record
SQLMS;
                $qrychat = $myCon->prepare($dql);
                $qrychat->bindValue(':ogid', $ogid);
                $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
                $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
                $qrychat->execute();
                $topHogs = $qrychat->fetchAll();

                $result2 = $this->renderView('dashboard/tb/_top_hogs.html.twig', [
                    'records' => $topHogs,
                    'organization' => $Organization,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'record' => $record,
                ]);

                $sqlMS = <<<SQLMS
                SELECT  e.employee_code, 
                        e.employee_name, 
                        e.email_address, 
                        e.designation_name, 
                        COALESCE(m.message_count, 0) as message_count, 
                        COALESCE(last_activity, '') as last_activity, 
                        e.gu_id,
                        e.registered,
                        e.ou_name
                FROM 
                    (select ou.ou_name, a.ou_id, a.gu_id, a.id as eId,  a.employee_code as employee_code, name as employee_name, a.email as email_address, d.designation_name, a.photo, d.designation_code, d.sort_order, a.registered
                    from gim.employee a join gim.designation d on d.id=a.designation_code join gim.organization_unit ou on ou.ou_id=a.ou_id INNER JOIN gim.organization as o  ON o.id = ou.organization_id 
                    where o.gu_id = :guid AND a.employee_code not like 'load%' ) as e
                    LEFT JOIN 
                    (select emp_id, sum(message_count)::int as message_count, to_char(max(date_hour),'DD-MM-YYYY HH24:MI:SS') as last_activity
                    from report.message_activity_emp r join gim.organization_unit ou on ou.ou_id=r.ou_id INNER JOIN gim.organization as o ON o.id = ou.organization_id
                    where is_offboarders = false AND o.gu_id = :guid AND r.date_hour >= :fromdate AND r.date_hour < :todate group by emp_id) as m ON e.eId = m.emp_id
                    order by 5 DESC
                    limit $record
SQLMS;
                $qrychat = $myCon->prepare($sqlMS);
                $qrychat->bindValue('guid', $ogid);
                $qrychat->bindValue('fromdate', $date_from->format('Y-m-d'));
                $qrychat->bindValue('todate', $date_to->modify('+1 days')->format('Y-m-d'));
                $qrychat->execute();
                $topUsers = $qrychat->fetchAll();

                $result3 = $this->renderView('dashboard/tb/_top_users.html.twig',
                 ['users' => $topUsers,
                 'organization' => $Organization,
                 'date_from' => $date_from,
                 'date_to' => $date_to,
                 'record' => $record,
                ]);
            } elseif ('bottom' == $type) {
                $dql = <<<SQLMS
                SELECT
                    ou.gu_id,
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as C1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as C2

                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_state = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    5 ASC
                LIMIT $record
SQLMS;
                $qrychat = $myCon->prepare($dql);
                $qrychat->bindValue(':ogid', $ogid);
                $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
                $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
                $qrychat->execute();
                $bottomStates = $qrychat->fetchAll();
                $result1 = $this->renderView('dashboard/tb/_bottom_states.html.twig', [
                    'records' => $bottomStates,
                    'organization' => $Organization,
                    'date_from' => $date_from,
                 'date_to' => $date_to,
                    'record' => $record,
                ]);

                $dql = <<<SQLMS
                SELECT
                    ou.gu_id,
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as C1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as C2

                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_hog = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    5 ASC
                LIMIT $record
SQLMS;
                $qrychat = $myCon->prepare($dql);
                $qrychat->bindValue(':ogid', $ogid);
                $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
                $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
                $qrychat->execute();
                $bottomHogs = $qrychat->fetchAll();
                $result2 = $this->renderView('dashboard/tb/_bottom_hogs.html.twig', [
                    'records' => $bottomHogs,
                    'organization' => $Organization,
                    'date_from' => $date_from,
                 'date_to' => $date_to,
                    'record' => $record,
                ]);

                $sqlMS = <<<SQLMS
                SELECT  e.employee_code, 
                e.employee_name, 
                e.email_address, 
                e.designation_name, 
                COALESCE(m.message_count*1, 0) as message_count, 
                COALESCE(last_activity, '') as last_activity, 
                e.gu_id,
                e.registered,
                e.ou_name
                FROM 
                    (select ou.ou_name, a.ou_id, a.gu_id, a.id as eId,  a.employee_code as employee_code, name as employee_name, a.email as email_address, d.designation_name, a.photo, d.designation_code, d.sort_order, a.registered
                    from gim.employee a join gim.designation d on d.id=a.designation_code join gim.organization_unit ou on ou.ou_id=a.ou_id INNER JOIN gim.organization as o  ON o.id = ou.organization_id 
                    where is_offboarders = false AND o.gu_id = :guid and  a.registered = 'Y' AND a.employee_code not like 'load%') as e
                    LEFT JOIN 
                    (select emp_id, sum(message_count)::int as message_count, to_char(max(date_hour),'DD-MM-YYYY HH24:MI:SS') as last_activity
                    from report.message_activity_emp r join gim.organization_unit ou on ou.ou_id=r.ou_id INNER JOIN gim.organization as o ON o.id = ou.organization_id
                    where is_offboarders = false AND o.gu_id = :guid AND r.date_hour >= :fromdate AND r.date_hour < :todate group by emp_id) as m ON e.eId = m.emp_id and m.message_count < 10
                    order by 5 ASC
    
SQLMS;
                $qrychat = $myCon->prepare($sqlMS);
                $qrychat->bindValue('guid', $ogid);
                $qrychat->bindValue('fromdate', $date_from->format('Y-m-d'));
                $qrychat->bindValue('todate', $date_to->modify('+1 days')->format('Y-m-d'));
                $qrychat->execute();
                $bottomUsers = $qrychat->fetchAll();
                $result3 = $this->renderView('dashboard/tb/_bottom_users.html.twig',
                 ['users' => $bottomUsers,
                 'organization' => $Organization,
                 'date_from' => $date_from,
                 'date_to' => $date_to,
                 'record' => $record,
                ]);
            }

            return new JsonResponse(json_encode(['status' => 'success', 'res1' => $result1, 'res2' => $result2, 'res3' => $result3]));
        }

        return $this->render('dashboard/tb/_top_bottom_statistics.html.twig', ['organization' => $Organization]);
    }

    /**
     * @Route("/dash/download/{type}/{value}/{date_from}/{to_date}/{ogid}", name="app_dashboard_download")
     */
    public function download($type, $value, $date_from, $to_date, $ogid)
    {
        $response = new StreamedResponse();
        $em = $this->getDoctrine()->getManager();
        $myCon = $em->getConnection();
        $date = date_create($to_date);
        date_add($date, date_interval_create_from_date_string('1 days'));
        $date_to = (date_format($date, 'Y-m-d'));
        if ('top' == $type) {
            if ('states' == $value) {
                $response->setCallback(function () use ($date_from, $date_to, $myCon, $ogid) {
                    $dql = <<<SQLMS
                        SELECT
                            ou.gu_id,
                            ou.ou_name AS ou_name,
                            max(COALESCE(oc, 0)) AS onboarded_count,
                            max(COALESCE(rc, 0)) AS registered_count,
                            sum(COALESCE(d.total_messages, 0)) AS total_messages,
                            CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as c1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as c2
                        FROM
                            report.drill_throughs_test AS d
                        JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                        JOIN gim.organization AS o ON o.id = ou.organization_id
                        JOIN (
                            SELECT
                                ou_id,
                                count(1) AS oc,
                                count(
                                    CASE registered
                                    WHEN 'Y' THEN
                                        1
                                    ELSE
                                        NULL
                                    END) AS rc
                            FROM
                                gim.employee AS e
                            GROUP BY
                                ou_id) AS etc ON etc.ou_id = ou.ou_id
                        INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                            AND nsh.is_state = TRUE
                        WHERE
                            o.gu_id = :ogid 
                            AND d.report_date >= :fromdate
                            AND d.report_date < :todate
                        GROUP BY
                            ou.ou_name,
                            ou.gu_id
                        ORDER BY
                            5 DESC
SQLMS;
                    $qrychat = $myCon->prepare($dql);
                    $qrychat->bindValue(':ogid', $ogid);
                    $qrychat->bindValue(':fromdate', $date_from);
                    $qrychat->bindValue(':todate', $date_to);
                    $qrychat->execute();
                    $topStates = $qrychat->fetchAll();

                    $handle = fopen('php://output', 'w+');

                    // Add the header of the CSV file
                    fputcsv($handle, ['OU', 'Onboarded', 'Registered', 'Total Messages', 'Total Messages/Onboarded', 'Total Messages/Registered'], ';');
                    foreach ($topStates as $row) {
                        fputcsv(
                                    $handle, // The file pointer
                                        [$row['ou_name'], $row['onboarded_count'], $row['registered_count'], $row['total_messages'], $row['c1'], $row['c2']], // The fields
                                        ';' // The delimiter
                                );
                    }
                    fclose($handle);
                });

                $response->setStatusCode(200);
                $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
                $response->headers->set('Content-Disposition', 'attachment; filename="Top_States.csv"');

                return $response;
            } elseif ('hogs' == $value) {
                $response->setCallback(function () use ($date_from, $date_to, $myCon, $ogid) {
                    $dql = <<<SQLMS
                        SELECT
                            ou.gu_id,
                            ou.ou_name AS ou_name,
                            max(COALESCE(oc, 0)) AS onboarded_count,
                            max(COALESCE(rc, 0)) AS registered_count,
                            sum(COALESCE(d.total_messages, 0)) AS total_messages,
                            CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as c1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as c2
                        FROM
                            report.drill_throughs_test AS d
                        JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                        JOIN gim.organization AS o ON o.id = ou.organization_id
                        JOIN (
                            SELECT
                                ou_id,
                                count(1) AS oc,
                                count(
                                    CASE registered
                                    WHEN 'Y' THEN
                                        1
                                    ELSE
                                        NULL
                                    END) AS rc
                            FROM
                                gim.employee AS e
                            GROUP BY
                                ou_id) AS etc ON etc.ou_id = ou.ou_id
                        INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                            AND nsh.is_hog = TRUE
                        WHERE
                            o.gu_id = :ogid 
                            AND d.report_date >= :fromdate
                            AND d.report_date < :todate
                        GROUP BY
                            ou.ou_name,
                            ou.gu_id
                        ORDER BY
                            5 DESC
SQLMS;
                    $qrychat = $myCon->prepare($dql);
                    $qrychat->bindValue(':ogid', $ogid);
                    $qrychat->bindValue(':fromdate', $date_from);
                    $qrychat->bindValue(':todate', $date_to);
                    $qrychat->execute();
                    $topHogs = $qrychat->fetchAll();
                    $handle = fopen('php://output', 'w+');

                    // Add the header of the CSV file
                    fputcsv($handle, ['OU', 'Onboarded', 'Registered', 'Total Messages', 'Total Messages/Onboarded', 'Total Messages/Registered'], ';');
                    foreach ($topHogs as $row) {
                        fputcsv(
                                $handle, // The file pointer
                                    [$row['ou_name'], $row['onboarded_count'], $row['registered_count'], $row['total_messages'], $row['c1'], $row['c2']], // The fields
                                    ';' // The delimiter
                            );
                    }
                    fclose($handle);
                });

                $response->setStatusCode(200);
                $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
                $response->headers->set('Content-Disposition', 'attachment; filename="Top_Hogs.csv"');

                return $response;
            } elseif ('users' == $value) {
                $response->setCallback(function () use ($date_from, $date_to, $myCon, $ogid) {
                    $sqlMS = <<<SQLMS
                    SELECT  e.employee_code, 
                            e.employee_name, 
                            e.email_address, 
                            e.designation_name, 
                            COALESCE(m.message_count, 0) as message_count, 
                            COALESCE(last_activity, '') as last_activity, 
                            e.gu_id,
                            e.registered,
                            e.ou_name
                    FROM 
                        (select ou.ou_name, a.ou_id, a.gu_id, a.id as eId,  a.employee_code as employee_code, name as employee_name, a.email as email_address, d.designation_name, a.photo, d.designation_code, d.sort_order, a.registered
                        from gim.employee a join gim.designation d on d.id=a.designation_code join gim.organization_unit ou on ou.ou_id=a.ou_id INNER JOIN gim.organization as o  ON o.id = ou.organization_id 
                        where is_offboarders = false AND o.gu_id = :guid AND a.employee_code not like 'load%') as e
                        LEFT JOIN 
                        (select emp_id, sum(message_count)::int as message_count, to_char(max(date_hour),'DD-MM-YYYY HH24:MI:SS') as last_activity
                        from report.message_activity_emp r join gim.organization_unit ou on ou.ou_id=r.ou_id INNER JOIN gim.organization as o ON o.id = ou.organization_id
                        where is_offboarders = false AND o.gu_id = :guid AND r.date_hour >= :fromdate AND r.date_hour < :todate group by emp_id) as m ON e.eId = m.emp_id
                        order by 5 DESC
SQLMS;
                    $qrychat = $myCon->prepare($sqlMS);
                    $qrychat->bindValue('guid', $ogid);
                    $qrychat->bindValue('fromdate', $date_from);
                    $qrychat->bindValue('todate', $date_to);
                    $qrychat->execute();
                    $topUsers = $qrychat->fetchAll();

                    $handle = fopen('php://output', 'w+');

                    // Add the header of the CSV file
                    fputcsv($handle, ['OU', 'Employee Code', 'Employee Name', 'Designation', 'Total Messages'], ';');
                    foreach ($topUsers as $row) {
                        fputcsv(
                                $handle, // The file pointer
                                [$row['ou_name'], $row['employee_code'], $row['employee_name'], $row['designation_name'], $row['message_count']], // The fields
                                ';' // The delimiter
                        );
                    }
                    fclose($handle);
                });

                $response->setStatusCode(200);
                $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
                $response->headers->set('Content-Disposition', 'attachment; filename="Top_Users.csv"');

                return $response;
            }
        } elseif ('bottom' == $type) {
            if ('states' == $value) {
                $response->setCallback(function () use ($date_from, $date_to, $myCon, $ogid) {
                    $dql = <<<SQLMS
                    SELECT
                        ou.gu_id,
                        ou.ou_name AS ou_name,
                        max(COALESCE(oc, 0)) AS onboarded_count,
                        max(COALESCE(rc, 0)) AS registered_count,
                        sum(COALESCE(d.total_messages, 0)) AS total_messages,
                        CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as C1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as C2
                    FROM
                        report.drill_throughs_test AS d
                    JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                    JOIN gim.organization AS o ON o.id = ou.organization_id
                    JOIN (
                        SELECT
                            ou_id,
                            count(1) AS oc,
                            count(
                                CASE registered
                                WHEN 'Y' THEN
                                    1
                                ELSE
                                    NULL
                                END) AS rc
                        FROM
                            gim.employee AS e
                        GROUP BY
                            ou_id) AS etc ON etc.ou_id = ou.ou_id
                    INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                        AND nsh.is_state = TRUE
                    WHERE
                        o.gu_id = :ogid 
                        AND d.report_date >= :fromdate
                        AND d.report_date < :todate
                    GROUP BY
                        ou.ou_name,
                        ou.gu_id
                    ORDER BY
                        total_messages ASC
SQLMS;
                    $qrychat = $myCon->prepare($dql);
                    $qrychat->bindValue(':ogid', $ogid);
                    $qrychat->bindValue(':fromdate', $date_from);
                    $qrychat->bindValue(':todate', $date_to);
                    $qrychat->execute();
                    $bottomStates = $qrychat->fetchAll();

                    $handle = fopen('php://output', 'w+');

                    // Add the header of the CSV file
                    fputcsv($handle, ['OU', 'Onboarded', 'Registered', 'Total Messages', 'Total Messages/Onboarded', 'Total Messages/Registered'], ';');
                    foreach ($bottomStates as $row) {
                        fputcsv(
                                    $handle, // The file pointer
                                        [$row['ou_name'], $row['onboarded_count'], $row['registered_count'], $row['total_messages'], $row['c1'], $row['c2']], // The fields
                                        ';' // The delimiter
                                );
                    }
                    fclose($handle);
                });

                $response->setStatusCode(200);
                $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
                $response->headers->set('Content-Disposition', 'attachment; filename="Bottom_States.csv"');

                return $response;
            } elseif ('hogs' == $value) {
                $response->setCallback(function () use ($date_from, $date_to, $myCon, $ogid) {
                    $dql = <<<SQLMS
                    SELECT
                        ou.gu_id,
                        ou.ou_name AS ou_name,
                        max(COALESCE(oc, 0)) AS onboarded_count,
                        max(COALESCE(rc, 0)) AS registered_count,
                        sum(COALESCE(d.total_messages, 0)) AS total_messages,
                        CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as C1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as C2
                    FROM
                        report.drill_throughs_test AS d
                    JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                    JOIN gim.organization AS o ON o.id = ou.organization_id
                    JOIN (
                        SELECT
                            ou_id,
                            count(1) AS oc,
                            count(
                                CASE registered
                                WHEN 'Y' THEN
                                    1
                                ELSE
                                    NULL
                                END) AS rc
                        FROM
                            gim.employee AS e
                        GROUP BY
                            ou_id) AS etc ON etc.ou_id = ou.ou_id
                    INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                        AND nsh.is_hog = TRUE
                    WHERE
                        o.gu_id = :ogid 
                        AND d.report_date >= :fromdate
                        AND d.report_date < :todate
                    GROUP BY
                        ou.ou_name,
                        ou.gu_id
                    ORDER BY
                        total_messages ASC
SQLMS;
                    $qrychat = $myCon->prepare($dql);
                    $qrychat->bindValue(':ogid', $ogid);
                    $qrychat->bindValue(':fromdate', $date_from);
                    $qrychat->bindValue(':todate', $date_to);
                    $qrychat->execute();
                    $bottomHogs = $qrychat->fetchAll();

                    $handle = fopen('php://output', 'w+');

                    // Add the header of the CSV file
                    fputcsv($handle, ['OU', 'Onboarded', 'Registered', 'Total Messages', 'Total Messages/Onboarded', 'Total Messages/Registered'], ';');
                    foreach ($bottomHogs as $row) {
                        fputcsv(
                                $handle, // The file pointer
                                    [$row['ou_name'], $row['onboarded_count'], $row['registered_count'], $row['total_messages'], $row['c1'], $row['c2']], // The fields
                                    ';' // The delimiter
                            );
                    }
                    fclose($handle);
                });

                $response->setStatusCode(200);
                $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
                $response->headers->set('Content-Disposition', 'attachment; filename="Bottom_Hogs.csv"');

                return $response;
            } elseif ('users' == $value) {
                $response->setCallback(function () use ($date_from, $date_to, $myCon, $ogid) {
                    $sqlMS = <<<SQLMS
                    SELECT  e.employee_code, 
                    e.employee_name, 
                    e.email_address, 
                    e.designation_name, 
                    COALESCE(m.message_count*1, 0) as message_count, 
                    COALESCE(last_activity, '') as last_activity, 
                    e.gu_id,
                    e.registered,
                    e.ou_name
                    FROM 
                        (select ou.ou_name, a.ou_id, a.gu_id, a.id as eId,  a.employee_code as employee_code, name as employee_name, a.email as email_address, d.designation_name, a.photo, d.designation_code, d.sort_order, a.registered
                        from gim.employee a join gim.designation d on d.id=a.designation_code join gim.organization_unit ou on ou.ou_id=a.ou_id INNER JOIN gim.organization as o  ON o.id = ou.organization_id 
                        where is_offboarders = false AND o.gu_id = :guid and  a.registered = 'Y' AND a.employee_code not like 'load%') as e
                        LEFT JOIN 
                        (select emp_id, sum(message_count)::int as message_count, to_char(max(date_hour),'DD-MM-YYYY HH24:MI:SS') as last_activity
                        from report.message_activity_emp r join gim.organization_unit ou on ou.ou_id=r.ou_id INNER JOIN gim.organization as o ON o.id = ou.organization_id
                        where is_offboarders = false AND o.gu_id = :guid AND r.date_hour >= :fromdate AND r.date_hour < :todate group by emp_id) as m ON e.eId = m.emp_id and m.message_count < 10
                        order by 5 ASC
    
SQLMS;
                    $qrychat = $myCon->prepare($sqlMS);
                    $qrychat->bindValue('guid', $ogid);
                    $qrychat->bindValue('fromdate', $date_from);
                    $qrychat->bindValue('todate', $date_to);
                    $qrychat->execute();
                    $bottomUsers = $qrychat->fetchAll();

                    $handle = fopen('php://output', 'w+');

                    // Add the header of the CSV file
                    fputcsv($handle, ['OU', 'Employee Code', 'Employee Name', 'Designation', 'Total Messages'], ';');
                    foreach ($bottomUsers as $row) {
                        fputcsv(
                                $handle, // The file pointer
                                [$row['ou_name'], $row['employee_code'], $row['employee_name'], $row['designation_name'], $row['message_count']], // The fields
                                ';' // The delimiter
                        );
                    }
                    fclose($handle);
                });

                $response->setStatusCode(200);
                $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
                $response->headers->set('Content-Disposition', 'attachment; filename="Bottom_Users.csv"');

                return $response;
            }
        }
    }

    /**
     * @Route("/dash/tbsp", name="app_dashboard_top_bottom_single_page")
     */
    public function topBottomSinglePage(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $ogid = $request->request->get('objid');
        if (!$ogid) {
            $session = new Session();
            $ogid = $session->get('lastobjid');
        }
        $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($ogid);

        $date_to = new \DateTimeImmutable('now');
        $date_from = $date_to->modify('-7 days');

        $myCon = $em->getConnection();

        $dql = <<<SQLMS
                SELECT
                    ou.gu_id,
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as C1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as C2,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN ROUND(sum(COALESCE(d.total_messages,0))*(CAST(max(COALESCE(rc, 0)) as FLOAT)/CAST(max(COALESCE(oc, 0)) as FLOAT))) ELSE 0 END as percapita
                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_state = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    8 DESC
                LIMIT 5
SQLMS;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $topStates = $qrychat->fetchAll();

        $dql = <<<SQLMS
                SELECT
                    ou.gu_id,
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as c1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as c2,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN ROUND(sum(COALESCE(d.total_messages,0))*(CAST(max(COALESCE(rc, 0)) as FLOAT)/CAST(max(COALESCE(oc, 0)) as FLOAT))) ELSE 0 END as percapita
                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_hog = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    8 DESC
                    limit 5
SQLMS;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $topHogs = $qrychat->fetchAll();

        $sqlMS = <<<SQLMS
                SELECT  e.employee_code, 
                        e.employee_name, 
                        e.email_address, 
                        e.designation_name, 
                        COALESCE(m.message_count, 0) as message_count, 
                        COALESCE(last_activity, '') as last_activity, 
                        e.gu_id,
                        e.registered,
                        e.ou_name
                FROM 
                    (select ou.ou_name, a.ou_id, a.gu_id, a.id as eId,  a.employee_code as employee_code, name as employee_name, a.email as email_address, d.designation_name, a.photo, d.designation_code, d.sort_order, a.registered
                    from gim.employee a join gim.designation d on d.id=a.designation_code join gim.organization_unit ou on ou.ou_id=a.ou_id INNER JOIN gim.organization as o  ON o.id = ou.organization_id 
                    where o.gu_id = :guid AND a.employee_code not like 'load%' ) as e
                    LEFT JOIN 
                    (select emp_id, sum(message_count)::int as message_count, to_char(max(date_hour),'DD-MM-YYYY HH24:MI:SS') as last_activity
                    from report.message_activity_emp r join gim.organization_unit ou on ou.ou_id=r.ou_id INNER JOIN gim.organization as o ON o.id = ou.organization_id
                    where is_offboarders = false AND o.gu_id = :guid AND r.date_hour >= :fromdate AND r.date_hour < :todate group by emp_id) as m ON e.eId = m.emp_id
                    order by 5 DESC
                    limit 5
SQLMS;
        $qrychat = $myCon->prepare($sqlMS);
        $qrychat->bindValue('guid', $ogid);
        $qrychat->bindValue('fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue('todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $topUsers = $qrychat->fetchAll();

        $dql = <<<SQLMS
                SELECT
                    ou.gu_id,
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as C1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as C2,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN ROUND(sum(COALESCE(d.total_messages,0))*(CAST(max(COALESCE(rc, 0)) as FLOAT)/CAST(max(COALESCE(oc, 0)) as FLOAT))) ELSE 0 END as percapita
                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_state = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    8 ASC
                LIMIT 5
SQLMS;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $bottomStates = $qrychat->fetchAll();

        $dql = <<<SQLMS
                SELECT
                    ou.gu_id,
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(oc, 0)) ELSE 0 END as C1,
                    CASE WHEN  max(COALESCE(rc, 0)) > 0 THEN sum(COALESCE(d.total_messages,0))/ max(COALESCE(rc, 0)) ELSE 0 END as C2,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN ROUND(sum(COALESCE(d.total_messages,0))*(CAST(max(COALESCE(rc, 0)) as FLOAT)/CAST(max(COALESCE(oc, 0)) as FLOAT))) ELSE 0 END as percapita         
                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_hog = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    8 ASC
                LIMIT 5
SQLMS;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $bottomHogs = $qrychat->fetchAll();

        return $this->render('dashboard/tb/single_page.html.twig', ['organization' => $Organization, 'period' => $date_from->format('d/m/Y').'  -  '.$date_to->format('d/m/Y'), 'topStates' => $topStates, 'topHogs' => $topHogs, 'topUsers' => $topUsers, 'bottomStates' => $bottomStates, 'bottomHogs' => $bottomHogs]);
    }

    /**
     * @Route("/dash/tbsp/{objid}", name="app_dashboard_top_bottom_single_page_direct")
     */
    public function topBottomSinglePageDirect(Request $request, $objid): Response
    {
        $em = $this->getDoctrine()->getManager();
        $ogid = $objid;
        if (!$ogid) {
            $session = new Session();
            $ogid = $session->get('lastobjid');
        }
        $Organization = $this->getDoctrine()->getRepository(Organization::class)->findOneByGuId($ogid);

        $date_to = new \DateTimeImmutable('now');
        $date_from = $date_to->modify('-7 days');

        $myCon = $em->getConnection();

        $dql = <<<SQLMS
                SELECT
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN ROUND(sum(COALESCE(d.total_messages,0))*(CAST(max(COALESCE(rc, 0)) as FLOAT)/CAST(max(COALESCE(oc, 0)) as FLOAT))) ELSE 0 END as percapita
                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_state = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    5 DESC
                LIMIT 5
SQLMS;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $topStates = $qrychat->fetchAll();

        $dql = <<<SQLMS
                SELECT
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN ROUND(sum(COALESCE(d.total_messages,0))*(CAST(max(COALESCE(rc, 0)) as FLOAT)/CAST(max(COALESCE(oc, 0)) as FLOAT))) ELSE 0 END as percapita
                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_hog = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    5 DESC
                    limit 5
SQLMS;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $topHogs = $qrychat->fetchAll();

        $sqlMS = <<<SQLMS
                SELECT  e.employee_code, 
                        e.employee_name, 
                        e.email_address, 
                        e.designation_name, 
                        COALESCE(m.message_count, 0) as message_count, 
                        e.ou_name
                FROM 
                    (select ou.ou_name, a.ou_id, a.gu_id, a.id as eId,  a.employee_code as employee_code, name as employee_name, a.email as email_address, d.designation_name, a.photo, d.designation_code, d.sort_order, a.registered
                    from gim.employee a join gim.designation d on d.id=a.designation_code join gim.organization_unit ou on ou.ou_id=a.ou_id INNER JOIN gim.organization as o  ON o.id = ou.organization_id 
                    where o.gu_id = :guid AND a.employee_code not like 'load%' ) as e
                    LEFT JOIN 
                    (select emp_id, sum(message_count)::int as message_count, to_char(max(date_hour),'DD-MM-YYYY HH24:MI:SS') as last_activity
                    from report.message_activity_emp r join gim.organization_unit ou on ou.ou_id=r.ou_id INNER JOIN gim.organization as o ON o.id = ou.organization_id
                    where is_offboarders = false AND o.gu_id = :guid AND r.date_hour >= :fromdate AND r.date_hour < :todate group by emp_id) as m ON e.eId = m.emp_id
                    order by 5 DESC
                    limit 5
SQLMS;
        $qrychat = $myCon->prepare($sqlMS);
        $qrychat->bindValue('guid', $ogid);
        $qrychat->bindValue('fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue('todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $topUsers = $qrychat->fetchAll();

        $dql = <<<SQLMS
                SELECT
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN ROUND(sum(COALESCE(d.total_messages,0))*(CAST(max(COALESCE(rc, 0)) as FLOAT)/CAST(max(COALESCE(oc, 0)) as FLOAT))) ELSE 0 END as percapita
                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_state = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    5 ASC
                LIMIT 5
SQLMS;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $bottomStates = $qrychat->fetchAll();

        $dql = <<<SQLMS
                SELECT
                    ou.ou_name AS ou_name,
                    max(COALESCE(oc, 0)) AS onboarded_count,
                    max(COALESCE(rc, 0)) AS registered_count,
                    sum(COALESCE(d.total_messages, 0)) AS total_messages,
                    CASE WHEN max(COALESCE(oc, 0)) > 0 THEN ROUND(sum(COALESCE(d.total_messages,0))*(CAST(max(COALESCE(rc, 0)) as FLOAT)/CAST(max(COALESCE(oc, 0)) as FLOAT))) ELSE 0 END as percapita         
                FROM
                    report.drill_throughs_test AS d
                JOIN gim.organization_unit AS ou ON d.ou_id = ou.ou_id
                JOIN gim.organization AS o ON o.id = ou.organization_id
                JOIN (
                    SELECT
                        ou_id,
                        count(1) AS oc,
                        count(
                            CASE registered
                            WHEN 'Y' THEN
                                1
                            ELSE
                                NULL
                            END) AS rc
                    FROM
                        gim.employee AS e
                    GROUP BY
                        ou_id) AS etc ON etc.ou_id = ou.ou_id
                INNER JOIN gim.masters_nic_states_hogs nsh ON ou.ou_id = nsh.ou_id
                    AND nsh.is_hog = TRUE
                WHERE
                    o.gu_id = :ogid 
                    AND d.report_date >= :fromdate
                    AND d.report_date < :todate
                GROUP BY
                    ou.ou_name,
                    ou.gu_id
                ORDER BY
                    5 ASC
                LIMIT 5
SQLMS;
        $qrychat = $myCon->prepare($dql);
        $qrychat->bindValue(':ogid', $ogid);
        $qrychat->bindValue(':fromdate', $date_from->format('Y-m-d'));
        $qrychat->bindValue(':todate', $date_to->modify('+1 days')->format('Y-m-d'));
        $qrychat->execute();
        $bottomHogs = $qrychat->fetchAll();
        $reportingPeriod = $date_from->format('d/m/Y').'  -  '.$date_to->format('d/m/Y');
        $apiData = ['organization' => $Organization->getOrganizationName(), 'reportingPeriod' => $reportingPeriod, 'topStates' => $topStates, 'topHOGs' => $topHogs, 'topUsers' => $topUsers, 'bottomStates' => $bottomStates, 'bottomHOGs' => $bottomHogs];
        $this->defaultValue->updateTBAPIData($apiData);
        return $this->render('dashboard/tb/single_page.html.twig', ['organization' => $Organization, 'period' => $reportingPeriod, 'topStates' => $topStates, 'topHogs' => $topHogs, 'topUsers' => $topUsers, 'bottomStates' => $bottomStates, 'bottomHogs' => $bottomHogs]);
    }

    /**
     * @Route("/api/tbsp/{objid}", name="app_dashboard_top_bottom_api")
     */
    public function tbAPI($objid): JsonResponse
    {
        if ($objid == "a12c8450-56c0-45a0-a6ce-addfc997e09a"){
            return new JsonResponse(json_decode($this->defaultValue->getTBAPIData()));
        } else {
            return new JsonResponse('Access Denied: Key Mismatch');
        }
    }

}
