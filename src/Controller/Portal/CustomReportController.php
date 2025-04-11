<?php

namespace App\Controller\Portal;

use App\Services\ProfileWorkspace;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

class CustomReportController extends AbstractController implements \App\Interfaces\AuditableControllerInterface {

    public function __construct(ProfileWorkspace $profileWorkspace) {
        $this->profileWorkspace = $profileWorkspace;
    }

    /**
     * @Route("/sadmin/cr", name="sadmin_custom_reports")
     */
    public function index($status = null): Response {
        $em = $this->getDoctrine()->getManager();
        $selected_custom_report_guid = $_POST?$_POST['sbox_custom_report']:null;
        $custom_reports = $em->getRepository('App:Portal\CustomReport')->findBy(['isPublished' => true]);
        $selected_custom_report = $em->getRepository('App:Portal\CustomReport')->findOneByGuId($selected_custom_report_guid);
        return $this->render('portal/custom_reports/index.html.twig',[
            'selected_custom_report' => $selected_custom_report,
            'custom_reports' => $custom_reports
        ]);
    }
    
    /**
     * @Route("/sadmin/cr/generate", name="sadmin_custom_report_generate")
     */
    public function customReportGenerate(Request $request): Response {
        $em = $this->getDoctrine()->getManager();
        $custom_reports = $em->getRepository('App:Portal\CustomReport')->findBy(['isPublished' => true]);
        $myCon = $em->getConnection();
        $result_html = '';
        if($_POST != null)
        {
            $selected_custom_report_guid = $_POST?$_POST['sbox_custom_report']:null;
            $selected_custom_report = $em->getRepository('App:Portal\CustomReport')->findOneByGuId($selected_custom_report_guid);
            $is_download_only = $selected_custom_report?$selected_custom_report->getIsDownloadOnly():false;
            $result = '';
            if($is_download_only != true)
            {
                $sql = $selected_custom_report?$selected_custom_report->getReportSql():'';
                if($sql)
                {
                    $op_res = $myCon->prepare($sql);
                    $op_res->execute();
                    $result = $op_res->fetchAll();
                    $result_html = $this->array2Html($result, true);
                }
            }
            return $this->render('portal/custom_reports/_list.html.twig',[
                'selected_custom_report' => $selected_custom_report,
                'custom_reports' => $custom_reports,
                'result' => $result,
                'is_download_only' => $is_download_only,
                'result_html' => $result_html
            ]);
        }
        else{
            $selected_custom_report = '';
            return $this->render('portal/custom_reports/index.html.twig',[
                'selected_custom_report' => $selected_custom_report,
                'custom_reports' => $custom_reports
            ]);
        }
    }

    private function array2Html($array, $table = true) {
        $out = '';
        $i = 1;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!isset($tableHeader)) {
                    $tableHeader = '<th>#</th><th> ' .
                            ucwords(implode(' </th><th> ', str_replace('_', ' ', array_keys($value)))) .
                            ' </th>';
                }
                array_keys($value);
                $out .= '<tr>';
                $out .= "<td>$i</td>";
                $out .= $this->array2Html($value, false);
                $out .= '</tr>';
                $i++;
            } else {
                if (is_numeric($value)) {
                    $out .= "<td class='text-right'>$value</td>";
                } else {
                    $out .= "<td>$value</td>";
                }
            }
        }
        if ($table && $array) {
            return '<table class="table table-bordered table-stripped table_custom table-condensed">' . $tableHeader . $out . '</table>';
        } else {
            return $out;
        }
    }

    /**
     * @Route("/portal/cr/download", name="sadmin_custom_report_download")
     */
    public function download(Request $request): Response {
        $em = $this->getDoctrine()->getManager();
        $param = $request->request->get('csvDownload');
        $new_password = $param['new_password'];
        $confirm_password = $param['confirm_password'];
        if ($new_password !== $confirm_password) {
            $this->addFlash('danger', 'password mismatch');
        }
        $selected_custom_report = $param['custom_filter_param'];
        $selected_custom_report = $em->getRepository('App:Portal\CustomReport')->findOneByGuId($selected_custom_report);
        $tmp_folder = sys_get_temp_dir() . "/";
        $csv_file_name = $selected_custom_report->getReportName();
        $csv_file_name = preg_replace('/\s*/', '', $csv_file_name);
        $csv_file_name = strtolower($csv_file_name);

        if($selected_custom_report)
        {
            $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $csv_file_name_only = $csv_file_name."-".$uuid.".csv";
            $zip_file_name_with_path = $tmp_folder . $csv_file_name."-".$uuid. ".zip";
            $csv_file_name_with_path = $tmp_folder . $csv_file_name."-".$csv_file_name_only;
            $handle = fopen($csv_file_name_with_path, 'w+');

            $em = $this->getDoctrine()->getManager();
            $myCon = $em->getConnection();
            
            $sql = $selected_custom_report?$selected_custom_report->getReportSql():'';
            $op_res = $myCon->prepare($sql);
            $op_res->execute();
            $query_result = $op_res->fetchAll();
            $query = $sql;
            if($query_result){
                $key_arr = array();
                $keys = array_keys($query_result);
                foreach ($query_result as $key => $value) {
                    array_push($key_arr, $value);
                }
                $key_array = array();
                foreach ($key_arr as $key => $value) {
                    array_push($key_array, $value);
                }
                $keys = array_keys($value);
                fputcsv($handle, $keys);
                $key_arr = [];
                foreach ($key_array as $key => $value) {
                    foreach ($value as $ke => $val) {
                        array_push($key_arr, $val);
                    }
                    fputcsv(
                            $handle, // The file pointer
                            $key_arr, ',' // The delimiter
                    );
                    $key_arr = [];
                }
            } else
            {
                fputcsv($handle,['No data available']);
            }
            fclose($handle);
            $zip = new \ZipArchive;
            if ($zip->open($zip_file_name_with_path, ZipArchive::CREATE) === true) {
                $zip->setPassword('Nic*123');
                $zip->addFile($csv_file_name_with_path, $csv_file_name_only);
                $zip->setEncryptionName($csv_file_name_only, ZipArchive::EM_AES_256, $confirm_password);
                $zip->close();
            }
            return $this->file($zip_file_name_with_path);
        } else
        {
            $this->addFlash('danger', 'Unable to identify the report');
            return $this->redirect($this->generateUrl('sadmin_custom_reports'));
        }
    }
}