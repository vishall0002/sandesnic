<?php

namespace App\Controller\Portal;

use App\Entity\Portal\Upload;
use App\Entity\Portal\Profile;
use App\Form\Portal\UploadType;
use App\Interfaces\AuditableControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\ProfileWorkspace;
use App\Services\PortalMetadata;

use App\Services\DefaultValue;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("")
 */
class UploadController extends AbstractController implements AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $defaultValue;
    

    public function __construct( DefaultValue $defVal, ProfileWorkspace $profileWorkspace, PortalMetadata $metadata)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->defaultValue = $defVal;
        
    }

    /**
     * @Route("/portal/upload/", name="portal_upload_index")
     */
    public function index(Request $request)
    {

        $dfConfig = ([['field_alias' => "appVersion", 'display_text' => "App Version", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
        ['field_alias' => "appType", 'display_text' => "App Type", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
        ['field_alias' => "appBuildNo", 'display_text' => "App BuildNo", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
        ['field_alias' => "appVersionNo", 'display_text' => "App VersionNo", 'operator_type' => ['ILIKE', '='], 'input_type' => "text", 'input_schema' => ''],
        ['field_alias' => "uploadDate", 'display_text' => "Upload Date", 'operator_type' => ['='], 'input_type' => "date", 'input_schema' => '']
    ]);

        return $this->render('portal/upload/index.html.twig', ['dfConfig' => $dfConfig]);
    }

    /**
         * @Route("/portal/upload/rnindex", name="portal_upload_rnindex")
         */
    public function rnindex(Request $request)
    {
        return $this->render('portal/upload/rnindex.html.twig');
    }

    /**
     * @Route("/portal/upload/rnlist", name="app_releasenoteslist")
     */
    public function rnlists(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        $objid=$request->request->get('objid');
        $type=$request->request->get('type');
        $view=$request->request->get('view');
        
        if ($objid==''&& $type=='') {
            $rnListAndroid = $em->getRepository("App:Portal\Upload")->findOneBy(['isCurrent' => 't','appType'=>'ANDROID']);
            $appIdAndroid=$rnListAndroid->getId();
            $appVersionNoAndroid=$rnListAndroid->getAppVersionNo();
            $appTypeAndroid=$rnListAndroid->getAppType();

            $rnListIos = $em->getRepository("App:Portal\Upload")->findOneBy(['isCurrent' => 't','appType'=>'IOS']);
            $appIdIos=$rnListIos->getId();
            $appVersionNoIOS=$rnListIos->getAppVersionNo();
            $appTypeIos=$rnListIos->getAppType();
        } else {
            if ($type=='ANDROID') {
                $rnListAndroid = $em->getRepository("App:Portal\Upload")->findOneBy(['guId'=>$objid, 'appType'=>$type]);
                $appIdAndroid=$rnListAndroid->getId();
                $appVersionNoAndroid=$rnListAndroid->getAppVersionNo();
                $appTypeAndroid=$rnListAndroid->getAppType();

                $rnListIos = $em->getRepository("App:Portal\Upload")->findOneBy(['isCurrent' => 't','appType'=>'IOS']);
                $appIdIos=$rnListIos->getId();
                $appVersionNoIOS=$rnListIos->getAppVersionNo();
                $appTypeIos=$rnListIos->getAppType();
            } elseif ($type=='IOS') {
                $rnListIos = $em->getRepository("App:Portal\Upload")->findOneBy(['guId'=>$objid, 'appType'=>$type]);
                $appIdIos=$rnListIos->getId();
                $appVersionNoIOS=$rnListIos->getAppVersionNo();
                $appTypeIos=$rnListIos->getAppType();

                $rnListAndroid = $em->getRepository("App:Portal\Upload")->findOneBy(['isCurrent' => 't','appType'=>'ANDROID']);
                $appIdAndroid=$rnListAndroid->getId();
                $appVersionNoAndroid=$rnListAndroid->getAppVersionNo();
                $appTypeAndroid=$rnListAndroid->getAppType();
            }
        }

        //ANDROID
        $qryAndroidOld = $em->createQueryBuilder('u')
                    ->select('u')
                    ->from('App:Portal\Upload', 'u')
                    ->where('u.id < :idNo')
                    ->andwhere('u.appVersionNo != :verNo')
                    ->andwhere('u.appType = :type')
                    ->andwhere('u.uploadDate is not null')
                    ->setParameter('idNo', $appIdAndroid)
                    ->setParameter('verNo', $appVersionNoAndroid)
                    ->setParameter('type', $appTypeAndroid)
                    ->orderby('u.id', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()->getResult();
        
        $qryAndroidNew = $em->createQueryBuilder('u')
                    ->select('u')
                    ->from('App:Portal\Upload', 'u')
                    ->where('u.id > :idNo')
                    ->andwhere('u.appVersionNo != :verNo')
                    ->andwhere('u.appType = :type')
                    ->andwhere('u.uploadDate is not null')
                    ->setParameter('idNo', $appIdAndroid)
                    ->setParameter('verNo', $appVersionNoAndroid)
                    ->setParameter('type', $appTypeAndroid)
                    ->orderby('u.id', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery()->getResult();

        //IOS
        $qryIosOld = $em->createQueryBuilder('u')
                    ->select('u')
                    ->from('App:Portal\Upload', 'u')
                    ->where('u.id < :idNo')
                   // ->andwhere('u.appVersionNo != :verNo')
                    ->andwhere('u.appType = :type')
                    ->andwhere('u.uploadDate is not null')
                    ->setParameter('idNo', $appIdIos)
                    //->setParameter('verNo', $appVersionNoIOS)
                    ->setParameter('type', $appTypeIos)
                    ->orderby('u.id', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()->getResult();

        $qryIosNew = $em->createQueryBuilder('u')
                    ->select('u')
                    ->from('App:Portal\Upload', 'u')
                    ->where('u.id > :idNo')
                    //->andwhere('u.appVersionNo != :verNo')
                    ->andwhere('u.appType = :type')
                    ->andwhere('u.uploadDate is not null')
                    ->setParameter('idNo', $appIdIos)
                    //->setParameter('verNo', $appVersionNoIOS)
                    ->setParameter('type', $appTypeIos)
                    ->orderby('u.id', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery()->getResult();

                 if($view==='view'){
                    return $this->render('portal/upload/_listReleaseNotesView.html.twig', array('rnCurrentListANDROID' => $rnListAndroid,'OldVersionNoANDROID'=>$qryAndroidOld,'NewVersionNoANDROID'=>$qryAndroidNew,'rnCurrentListIOS' => $rnListIos, 'OldVersionNoIOS'=>$qryIosOld,'NewVersionNoIOS'=>$qryIosNew));
                 }else{
                    return $this->render('portal/upload/_listReleaseNotes.html.twig', array('rnCurrentListANDROID' => $rnListAndroid,'OldVersionNoANDROID'=>$qryAndroidOld,'NewVersionNoANDROID'=>$qryAndroidNew,'rnCurrentListIOS' => $rnListIos, 'OldVersionNoIOS'=>$qryIosOld,'NewVersionNoIOS'=>$qryIosNew));
                 }
       
    }
    
    /**
    * @Route("/rn/{os}/{version}", name="portal_rn")
    */
    public function releaseNotes($os, $version)
    {
        $em = $this->getDoctrine()->getManager();
        $os = \strtoupper($os);
        $rn = $em->getRepository("App:Portal\Upload")->findBy(['appType' => $os, 'appVersionNo'=> $version], ['uploadDate' => 'DESC']);            
        $rnMax = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => $os], ['id' => 'DESC']);
        $maxId=$rnMax->getId();
        $maxVersionNo=$rnMax->getAppVersionNo();       

        $rnCurId = $em->getRepository("App:Portal\Upload")->findOneBy(['appType' => $os, 'appVersionNo'=> $version], ['id' => 'DESC']);
        if ($rnCurId){
            $curId=$rnCurId->getId();
        } else {
            $curId = 0;
        }

        return $this->render('default/release_notes_view.html.twig', array('releaseNotes' => $rn, 'appVersionNo'=> $version, 'appType' => $os, 'maxId'=> $maxId, 'curId'=> $curId, 'maxVersionNo'=> $maxVersionNo));
    }

    /**
     * @Route("/portal/upload/list", name="portal_upload_list")
     */
    public function lists(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        // $dql = "SELECT u FROM App:Portal\Upload u ORDER BY u.id DESC";
        // $query = $em->createQuery($dql);
        $filters = $request->request->get('custom_filter_param') ? $request->request->get('custom_filter_param') : $request->query->get('custom_filter_param');
        $dynamicFilters = json_decode(base64_decode($filters), true);
       
        $fieldAliases = ['uploadDate' => 'u.uploadDate', 'appVersion' => 'u.appVersion', 'appType' => 'u.appType', 'appBuildNo' => 'u.appBuildNo',
        'appVersionNo' => 'u.appVersionNo' ];

    $quer = $em->createQueryBuilder('u')
            ->select('u.id,u.guId,u.uploadDate,u.appVersion,u.appFileName,u.appManifestName,u.appType,u.appBuildNo,u.appVersionNo,u.appReleaseNotes,u.isCurrent,u.isDeleted,u.isBeta')
            ->from('App:Portal\Upload', 'u')
            ->addOrderBy('u.id', 'DESC');
    ;
    if ($dynamicFilters) {
        $counter = 0;
        foreach ($dynamicFilters as $k => $v) {
          
            if ($v['operator'] === 'ILIKE') {
                if($counter===0){
                    $quer->where($v['operator'] . "(" . $fieldAliases[$k] . ",:$k )=TRUE");
                }else{
                    $quer->andwhere($v['operator'] . "(" . $fieldAliases[$k] . ",:$k )=TRUE");
                }                
                $quer->setParameter($k, '%' . trim($v['fvalue']) . '%');
            } else {
                if($counter===0){
                    $quer->where($fieldAliases[$k] . " " . $v['operator'] . " :$k");
                }else{
                    $quer->andwhere($fieldAliases[$k] . " " . $v['operator'] . " :$k");
                } 
               
                $quer->setParameter($k, trim($v['fvalue']));
            }
            $counter++;
        }
    }
    $query= $quer->getQuery();
        $uploaddir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        $uploadsPaginated = $paginator->paginate($query->getResult(), $request->query->getInt('page', 1), 20);
        $uploadsPaginated->setUsedRoute('portal_upload_list');
        return $this->render('portal/upload/_list.html.twig', array('pagination' => $uploadsPaginated,
                    'uploaddir' => $uploaddir));
    }

    /**
     * @Route("/portal/upload/newapple",name="portal_upload_new_apple")
     */
    public function newApple(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $upload = new Upload();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $upload->setGuId($uuid->toString());
        $profile = $this->profileWorkspace->getProfile();

        $form = $this->createForm(UploadType::class, $upload, array('profile' => $profile, 'action' => $this->generateUrl('portal_upload_ins_apple'), 'attr' => array('id' => 'frmBaseModal'), 'em' => $em))->add('btnInsert', SubmitType::class, array('label' => 'Save'));

        return $this->render('portal/upload/_form_new_apple.html.twig', array(
                    'form' => $form->createView(),
                    'profile' => $profile,
                    'caption' => 'IOS App Upload',
        ));
    }

    /**
     * @Route("/portal/upload/newandroid",name="portal_upload_new_android")
     */
    public function newAndroid(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $upload = new Upload();
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $upload->setGuId($uuid->toString());
        $profile = $this->profileWorkspace->getProfile();

        $form = $this->createForm(UploadType::class, $upload, array('profile' => $profile, 'action' => $this->generateUrl('portal_upload_ins_android'), 'attr' => array('id' => 'frmBaseModal'), 'em' => $em))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
        return $this->render('portal/upload/_form_new_android.html.twig', array(
                    'form' => $form->createView(),
                    'profile' => $profile,
                    'caption' => 'Android App Upload',
        ));
    }

    /**
     * @Route("/portal/android/ins",name="portal_upload_ins_android")
     */
    public function AndroidInsert(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $upload = new Upload();
        $form = $this->createForm(UploadType::class, $upload, array('action' => $this->generateUrl('portal_upload_ins_android'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
        $profile = $this->profileWorkspace->getProfile();
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $upload->setAppType('ANDROID');
                $em->persist($upload);
                $em->flush();

                return new Response(json_encode(array('status' => 'success', 'message' => 'Saved Successfully', 'objId' => $upload->getGuId())));
            }
        }
        $formView = $this->renderView('portal/upload/_form_new_android.html.twig', array(
            'form' => $form->createView(),
            'profile' => $profile,
            'caption' => 'Android App Upload',
        ));

        return new Response(json_encode(array('form' => $formView, 'status' => 'error', 'message' => 'Updation unsuccessful')));
    }

    /**
     * @Route("/portal/apple/ins",name="portal_upload_ins_apple")
     */
    public function AppleInsert(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $upload = new Upload();
        $form = $this->createForm(UploadType::class, $upload, array('action' => $this->generateUrl('portal_upload_ins_apple'), 'attr' => array('id' => 'frmBaseModal')))->add('btnInsert', SubmitType::class, array('label' => 'Save'));
        $profile = $this->profileWorkspace->getProfile();
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $upload->setAppType('IOS');
                $em->persist($upload);
                $em->flush();

                return new Response(json_encode(array('status' => 'success', 'message' => 'Saved Successfully', 'objId' => $upload->getGuId())));
            }
        }
        $formView = $this->renderView('portal/upload/_form_new_apple.html.twig', array(
            'form' => $form->createView(),
            'profile' => $profile,
            'caption' => 'IOS App Upload',
        ));

        return new Response(json_encode(array('form' => $formView, 'status' => 'error', 'message' => 'Updation unsuccessful')));
    }

    /**
     * @Route("/portal/upload/scanUploadSave/{guid}", name="portal_upload_save")
     */
    public function scanUploadSave(Request $request, $guid)
    {
        $fileBag = $request->files;
        $uploadedFile = $fileBag->get('file');
        $em = $this->getDoctrine()->getManager();
        $upload = $em->getRepository("App:Portal\Upload")->findOneByGuId($guid);
        if ($uploadedFile) {
            $fileName = $uploadedFile->getRealPath();
            $fileType = $uploadedFile->getMimeType();
            $fileOrginal = $_FILES['file']['name'];
            $fileError = $uploadedFile->getError();
            if ($fileError == UPLOAD_ERR_OK) {
                if ((strpos($fileType, '/java-archive') !== false) || (strpos($fileType, '/x-ios-app') !== false) || (strpos($fileType, '/ipa') !== false) || (strpos($fileType, '/zip') !== false)) {
//                if (strpos($fileType, '/pdf') !== false) {
                    //Processes your file here
                    $fileContent = file_get_contents($fileName);
                    //save to folder
                    $uploaddir = $this->getParameter('kernel.project_dir') . '/public/uploads/'; //this is your local directory
                    $checkIfExists = $em->getRepository("App:Portal\Upload")->findOneByAppFileName($fileOrginal);
                    if ($checkIfExists) {
                        return new JsonResponse(['error' => true, 'message' => 'File already exists!']);
                    }
                    if (file_put_contents($uploaddir . $fileOrginal, $fileContent)) {
                        if (strpos($fileType, '/java-archive') === true){
                            file_put_contents($uploaddir . "gims_public.apk", $fileContent);
                        }
                        $message = "Successfully Uploaded!!";
                        $type = false;
                        $upload->setUploadDate(new \DateTime('now'));
                        $upload->setAppFileName($fileOrginal);
                        $em->flush();
                    } else {
                        $message = "Uploaded but unable to move to folder!!";
                        $type = true;
                    }
                    $result['error'] = $type;
                    $result['message'] = $message;
                    return new JsonResponse($result);
                } else {
                    $result['error'] = true;
                    $result['message'] = 'File Type should be Ipa/Apk ';
                }
            } else {
                switch ($fileError) {
                    case UPLOAD_ERR_INI_SIZE:
                        $message = 'Error: The uploaded file exceeds the upload_max_filesize directive in php.ini';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $message = 'Error: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $message = 'Error: The uploaded file was only partially uploaded. ';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $message = 'Error: No file was uploaded.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $message = 'Error: Missing a temporary folder.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $message = 'Error: Failed to write file to disk. ';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $message = 'Error: A PHP extension stopped the file upload.';
                        break;
                    default:$message = 'Error: Unknown upload error.';
                        break;
                }
                echo json_encode(array(
                    'error' => true,
                    'message' => $message,
                ));
            }
        } else {
            $result['error'] = true;
            $result['message'] = 'Select a file to upload';
        }
        return new JsonResponse($result);
    }

//    /**
//     * @Route("/portal/upload/uploadmanifest/{guid}", name="portal_upload_manifest")
//     */
//    public function uploadManifest(Request $request, $guid) {
//        $fileBag = $request->files;
//        $uploadedFile = $fileBag->get('file');
//        $em = $this->getDoctrine()->getManager();
//        $upload = $em->getRepository("App:Portal\Upload")->findOneByGuId($guid);
//        if ($uploadedFile) {
//            $fileName = $uploadedFile->getRealPath();
//            $fileType = $uploadedFile->getMimeType();
//            $fileOrginal = $_FILES['file']['name'];
//            $fileError = $uploadedFile->getError();
//            if ($fileError == UPLOAD_ERR_OK) {
//                if ((strpos($fileType, '/ipa') !== false) || (strpos($fileType, '/xml') !== false) || (strpos($fileType, '/x-ios-app') !== false)) {
//                    $xml = simplexml_load_file($uploadedFile);
//                    $basePathUrl = $request->getSchemeAndHttpHost();
//                    $ipaPath = $basePathUrl . $this->generateUrl('app_dashboard_download_ios_app_ipa');
//                    //app path
//                    $xml->dict->array->dict->array->dict[0]->string[1] = $ipaPath;
//                    //small image path
//                    $xml->dict->array->dict->array->dict[1]->string[1] = $basePathUrl . '/img/logo_app.png';
//                    //full image path
//                    $xml->dict->array->dict->array->dict[2]->string[1] = $basePathUrl . '/img/logo_app.png';
//                    //convert xml to string
//                    $fileContent = $xml->asXML();
//                    //Processes your file here
    ////                    $fileContent = file_get_contents($fileName);
//                    //save to folder
//                    $uploaddir = $this->getParameter('kernel.project_dir') . '/public/uploads/'; //this is your local directory
//                    $checkIfExists = $em->getRepository("App:Portal\Upload")->findOneByAppManifestName($fileOrginal);
//                    if ($checkIfExists) {
//                        return new JsonResponse(['error' => true, 'message' => 'File already exists!']);
//                    }
//                    if (file_put_contents($uploaddir . $fileOrginal, $fileContent)) {
//                        $message = "Successfully Uploaded!!";
//                        $type = false;
//                        $upload->setUploadDate(new \DateTime('now'));
//                        $upload->setAppManifestName($fileOrginal);
//                        $em->flush();
//                    } else {
//                        $message = "Uploaded but unable to move to folder!!";
//                        $type = true;
//                    }
//                    $result['error'] = $type;
//                    $result['message'] = $message;
//                    return new JsonResponse($result);
//                } else {
//                    $result['error'] = true;
//                    $result['message'] = 'File Type should be Ipa ';
//                }
//            } else {
//                switch ($fileError) {
//                    case UPLOAD_ERR_INI_SIZE:
//                        $message = 'Error: The uploaded file exceeds the upload_max_filesize directive in php.ini';
//                        break;
//                    case UPLOAD_ERR_FORM_SIZE:
//                        $message = 'Error: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
//                        break;
//                    case UPLOAD_ERR_PARTIAL:
//                        $message = 'Error: The uploaded file was only partially uploaded. ';
//                        break;
//                    case UPLOAD_ERR_NO_FILE:
//                        $message = 'Error: No file was uploaded.';
//                        break;
//                    case UPLOAD_ERR_NO_TMP_DIR:
//                        $message = 'Error: Missing a temporary folder.';
//                        break;
//                    case UPLOAD_ERR_CANT_WRITE:
//                        $message = 'Error: Failed to write file to disk. ';
//                        break;
//                    case UPLOAD_ERR_EXTENSION:
//                        $message = 'Error: A PHP extension stopped the file upload.';
//                        break;
//                    default:$message = 'Error: Unknown upload error.';
//                        break;
//                }
//                echo json_encode(array(
//                    'error' => true,
//                    'message' => $message,
//                ));
//            }
//        } else {
//            $result['error'] = true;
//            $result['message'] = 'Select a file to upload';
//        }
//        return new JsonResponse($result);
//    }
    
    /**
     * @Route("/portal/downloadFile/{guid}/{type}", name="portal_download_file")
     */
    public function downloadFile($guid, $type = null)
    {
        $em = $this->getDoctrine()->getManager();
        $upload = $em->getRepository("App:Portal\Upload")->findOneByGuId($guid);
        $filename = $upload->getAppFileName();
        if ($type == 'manifest') {
            $filename = $upload->getAppManifestName();
        }
        $uploaddir = $this->getParameter('kernel.project_dir') . '/public/uploads/';

        $content = file_get_contents($uploaddir . $filename);

        $response = new Response();

        //set headers
        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename);

        $response->setContent($content);
        return $response;
    }

    /**
     * @Route("/portal/setiscurrent", name="portal_upload_set_iscurrent")
     */
    public function setiscurrent(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $upload = $em->getRepository("App:Portal\Upload")->findOneByGuId($objid);
        $appType = $upload->getAppType();
        $checkIfExists = $em->getRepository("App:Portal\Upload")->findOneBy(['isCurrent' => true, 'appType' => $appType]);
        if ($checkIfExists) {
            $checkIfExists->setIsCurrent(false);
        }
        $upload->setIsCurrent(true);
        $em->flush();
        return new JsonResponse(['type' => "success", 'message' => 'Is Current Set Successfully!']);
    }
    /**
     * @Route("/portal/setisbeta", name="portal_upload_set_isbeta")
     */
    public function setisbeta(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $upload = $em->getRepository("App:Portal\Upload")->findOneByGuId($objid);
        $appType = $upload->getAppType();
        $checkIfExists = $em->getRepository("App:Portal\Upload")->findOneBy(['isBeta' => true, 'appType' => $appType]);
        if ($checkIfExists) {
            $checkIfExists->setIsBeta(false);
        }
        $upload->setIsBeta(true);
        $em->flush();
        return new JsonResponse(['type' => "success", 'message' => 'Is Beta Set Successfully!']);
    }

    /**
     * @Route("/portal/upload_delete", name="portal_upload_delete")
     */
    public function deleteUpload(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $upload = $em->getRepository("App:Portal\Upload")->findOneByGuId($objid);
        if ($upload->getIsCurrent()) {
            return new JsonResponse(['type' => "danger", 'message' => 'Unable to remove active record!']);
        }
        $fileName = $upload->getAppFileName();
        try {
            if ($fileName) {
                $uploaddir = $this->getParameter('kernel.project_dir') . '/public/uploads/';
                $file_to_delete = $uploaddir . $fileName;
                if (unlink($file_to_delete)) {
                    $em->remove($upload);
                }
            } else {
                $em->remove($upload);
            }
            $em->flush();
            return new JsonResponse(['type' => "success", 'message' => 'Deleted Successfully!']);
        } catch (throwable $ex) {
            return new JsonResponse(['type' => "danger", 'message' => 'Unable to remove current record!']);
        }
    }

    /**
     * @Route("/portal/upload/edit", name="portal_upload_edit")
     */
    public function edit(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $objid = $request->request->get('objid');
        $upload = $em->getRepository("App:Portal\Upload")->findOneByGuId($objid);
        $profile = $this->profileWorkspace->getProfile();
        $form = $this->createForm(UploadType::class, $upload, array('profile' => $profile, 'action' => $this->generateUrl('portal_upload_upd', array('objid' => $objid)), 'attr' => array('id' => 'frmBaseModal'), 'em' => $em))->add('btnUpdate', SubmitType::class, array('label' => 'Save'));
        if ($upload->getAppType() === 'ANDROID') {
            return $this->render('portal/upload/_form_edit_android.html.twig', array(
                        'form' => $form->createView(),
                        'profile' => $profile,
                        'objid' => $objid,
                        'caption' => 'Android App Upload',
            ));
        } else {
            return $this->render('portal/upload/_form_edit_apple.html.twig', array(
                        'form' => $form->createView(),
                        'profile' => $profile,
                        'objid' => $objid,
                        'caption' => 'IOS App Upload',
            ));
        }
    }

    /**
     * @Route("/portal/upload/upd",name="portal_upload_upd")
     */
    public function update(Request $request)
    {
        $objid = $request->query->get('objid');
        $em = $this->getDoctrine()->getManager();
        $upload = $em->getRepository("App:Portal\Upload")->findOneByGuId($objid);
        $profile = $this->profileWorkspace->getProfile();
        $form = $this->createForm(UploadType::class, $upload, array('profile' => $profile, 'action' => $this->generateUrl('portal_upload_upd'), 'attr' => array('id' => 'frmBaseModal'), 'em' => $em))->add('btnUpdate', SubmitType::class, array('label' => 'Save'));
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->getConnection()->beginTransaction();
                try {
                    $em->persist($upload);
                    $em->flush();
                    $em->getConnection()->commit();
                    return new Response(json_encode(array('status' => 'success', 'message' => 'The details has been updated successfully !!')));
                } catch (\Exception $ex) {
                    $em->getConnection()->rollback();

                    return new Response(json_encode(array('status' => 'error', 'message' => 'An error has been occurred ' . $ex->getMessage())));
                }
            } else {
                if ($upload->getAppType() == "ANDROID") {
                    $formView = $this->renderView('portal/upload/_form_edit_android.html.twig', array(
                        'form' => $form->createView(),
                        'profile' => $profile,
                        'caption' => 'Android App Upload',
                    ));
                } else {
                    $formView = $this->renderView('portal/upload/_form_edit_apple.html.twig', array(
                        'form' => $form->createView(),
                        'profile' => $profile,
                        'caption' => 'IOS App Upload',
                    ));
                }

                return new Response(json_encode(array('form' => $formView, 'status' => 'error' . $form->getErrors())));
            }
        }
        if ($upload->getAppType() == "ANDROID") {
            $formView = $this->renderView('portal/upload/_form_edit_android.html.twig', array(
                'form' => $form->createView(),
                'profile' => $profile,
                'caption' => 'Android App Upload',
            ));
        } else {
            $formView = $this->renderView('portal/upload/_form_edit_apple.html.twig', array(
                'form' => $form->createView(),
                'profile' => $profile,
                'caption' => 'IOS App Upload',
            ));
        }

        return new Response(json_encode(array('form' => $formView, 'status' => 'New')));
    }


  
}
