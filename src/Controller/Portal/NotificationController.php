<?php

namespace App\Controller\Portal;

use App\Entity\Portal\Employee;
use App\Interfaces\AuditableControllerInterface;
use App\Services\EMailer;
use App\Services\GIMS;
use App\Services\ProfileWorkspace;
use Doctrine\DBAL\FetchMode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;


class NotificationController extends AbstractController implements AuditableControllerInterface
{
    private $profileWorkspace;
    private $emailer;
    private $gims;
    private $logger;

    public function __construct(LoggerInterface $broadcastLogger, EMailer $emailer, GIMS $gims, ProfileWorkspace $profileWorkspace)
    {
        $this->emailer = $emailer;
        $this->gims = $gims;
        $this->logger = $broadcastLogger;
        $this->profileWorkspace = $profileWorkspace;
    }

    /**
     * @Route("/send-message", name="portal_send_message")
     */
    public function sendMessage(Request $request)
    {
        $type = $request->request->get('type');
        $data = $request->request->get('data');
        //dump($data);die;
        $em = $this->getDoctrine()->getManager();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $loggedUser = $this->getUser();
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($loggedUser);
        $defApp = $this->getDoctrine()->getRepository('App:Portal\ExternalApps')->find(1);
        $apps = $em->createQueryBuilder('g')
                    ->select('ea.id, ea.appTitle')
                    ->from('App:Portal\EmployeeApps', 'a')
                    ->innerJoin('App:Portal\ExternalApps', 'ea', 'WITH', 'a.ExternalApps = ea.id')
                    ->where('a.employee = :employee')
                    ->getQuery()
                    ->setParameter('employee', $employee->getId())
                    ->getResult();
        $formView = $this->renderView('bases/_send_message.html.twig', [
            'type' => $type,
            'data' => json_decode($data),
            'apps' => $apps,
            'defApp' => $defApp,
        ]);

        return new JsonResponse($formView);
    }

    /**
     * @Route("/ntfy/email", name="portal_ntfy_email")
     */
    public function emailAction(Request $request)
    {
        // Selection Types o - for organization, ou - for organization unit, m - for members
        $em = $this->getDoctrine()->getManager();
        $guids = $request->request->get('guids');
        $type = $request->request->get('type');
        $app = $request->request->get('app');
        $append_name = $request->request->get('_bm_display_sn');
        $append_ouname = $request->request->get('_bm_display_oun');
        $loggedUser = $this->getUser();
        $sender_info = '';
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($loggedUser);
        if ($append_name) {
            $sender_info = $employee->getEmployeeName();
        }
        if ($append_ouname) {

            $unitname = 'Sandes Portal Organization Unit admin - '.$this->profileWorkspace->getOu()->getOUName();

            $sender_info = $sender_info.'<br/>'.$unitname;
        }

        $file_name = '/tmp/'.$request->request->get('frf');
        if ('/tmp/null' === $file_name) {
            $file_name = null;
        }
        $subject = 'Sandes Portal';
        $message = $request->request->get('message');
        if (!$subject || !$message) {
            return new JsonResponse(['status' => 'danger', 'message' => 'Subject/Message is mandatory!']);
        }

        $message = $message.'<br/>'.$sender_info;

        $myCon = $em->getConnection();

        if ('ministry' === $type) {
            $dql = 'SELECT o.gu_id FROM gim.masters_ministries as g INNER JOIN gim.organization as o ON g.id = o.ministry_id WHERE g.gu_id =  :param';
            $qryEmailOrgList = $myCon->prepare($dql);
            $qryEmailOrgList->bindValue(':param', $guids);
            $qryEmailOrgList->execute();
            $qryEmailOrgListResults = $qryEmailOrgList->fetchAll(FetchMode::NUMERIC);
            foreach ($qryEmailOrgListResults as $qryEmailOrgListResult) {
                $dql = 'SELECT e.email FROM gim.employee as e INNER JOIN gim.organization_unit as ou ON e.ou_id = ou.ou_id  INNER JOIN gim.organization as o ON ou.organization_id = o.id WHERE o.gu_id  = :param';
                $qryEmailEmployeeList = $myCon->prepare($dql);
                $qryEmailEmployeeList->bindValue(':param', $qryEmailOrgListResult[0]);
                $qryEmailEmployeeList->execute();
                $qryEmailEmployeeListResults = $qryEmailEmployeeList->fetchAll(FetchMode::NUMERIC);
                foreach ($qryEmailEmployeeListResults as $qryEmailEmployeeListResult) {
                    //dump('Sending Email '. $qryEmailEmployeeListResult[0] . '-Sub-'. $subject.'-Msg-'. $message. PHP_EOL);
                    $this->emailer->sendEmailGenericV5($qryEmailEmployeeListResult[0], $subject, $message,$file_name);
                }
            }

            return new JsonResponse(['status' => 'success', 'message' => 'E-Mails scheduled for delivery']);
        } elseif ('o' === $type) {
            $dql = 'SELECT e.email FROM gim.employee as e INNER JOIN gim.organization_unit as ou ON e.ou_id = ou.ou_id  INNER JOIN gim.organization as o ON ou.organization_id = o.id WHERE o.gu_id  = :param';
            $qryEmailList = $myCon->prepare($dql);
            $qryEmailList->bindValue(':param', $guids);
            $qryEmailList->execute();
            $qryEmailListResults = $qryEmailList->fetchAll(FetchMode::NUMERIC);
            foreach ($qryEmailListResults as $qryEmailListResult) {
                $this->emailer->sendEmailGenericV5($qryEmailListResult[0], $subject, $message,$file_name);
            }

            return new JsonResponse(['status' => 'success', 'message' => 'E-Mails scheduled for delivery']);
        } elseif ('g' === $type) {
            $dql = 'SELECT e.email FROM gim.employee as e INNER JOIN gim.group_member as gm ON e.id = gm.employee_id  INNER JOIN gim.group as g ON g.id = gm.group_id WHERE g.gu_id  = :param';
            $qryEmailList = $myCon->prepare($dql);
            $qryEmailList->bindValue(':param', $guids);
            $qryEmailList->execute();
            $qryEmailListResults = $qryEmailList->fetchAll(FetchMode::NUMERIC);
            foreach ($qryEmailListResults as $qryEmailListResult) {
                $this->emailer->sendEmailGenericV5($qryEmailListResult[0], $subject, $message,$file_name);
            }

            return new JsonResponse(['status' => 'success', 'message' => 'E-Mails scheduled for delivery']);
        } elseif ('ou' === $type) {
            $dql = 'SELECT e.email FROM gim.employee as e INNER JOIN gim.organization_unit as ou ON e.ou_id = ou.ou_id WHERE ou.gu_id =  :param';
            $qryEmailList = $myCon->prepare($dql);
            $qryEmailList->bindValue(':param', $guids);
            $qryEmailList->execute();
            $qryEmailListResults = $qryEmailList->fetchAll(FetchMode::NUMERIC);
            foreach ($qryEmailListResults as $qryEmailListResult) {
                $this->emailer->sendEmailGenericV5($qryEmailListResult[0], $subject, $message,$file_name);
            }

            return new JsonResponse(['status' => 'success', 'message' => 'E-Mails scheduled for delivery']);
        } elseif ('m' === $type) {
            $guidParams = explode(',', $guids);
            foreach ($guidParams as $guidParam) {
                $dql = 'SELECT e.email FROM gim.employee as e WHERE e.gu_id = :param';
                $qryEmailList = $myCon->prepare($dql);
                $qryEmailList->bindValue(':param', $guidParam);
                $qryEmailList->execute();
                $qryEmailListResult = $qryEmailList->fetch(FetchMode::NUMERIC);
                $this->emailer->sendEmailGenericV5($qryEmailListResult[0], $subject, $message,$file_name);
            }

            return new JsonResponse(['status' => 'success', 'message' => 'E-Mails scheduled for delivery']);
        } elseif ('mfb' === $type) {
            $EmployeeMessageEntity = $em->getRepository('App:Portal\EmployeeMessages')->findOneBy(['guId' => $guids]);
            $members = $EmployeeMessageEntity->getMembers();
            foreach ($members as $key => $memberEmail) {
                //    dump('Sending Email '. $memberEmail . '-Sub-'. $subject.'-Msg-'. $message. PHP_EOL);
                $this->emailer->sendEmailGenericV5($memberEmail, $subject, $message,$file_name);
            }

            return new JsonResponse(['status' => 'success', 'message' => 'E-Mails scheduled for delivery']);
        } else {
            return new JsonResponse(['status' => 'danger', 'message' => 'invalid selection type']);
        }
    }

    /**
     * @Route("/ntfy/gims", name="portal_ntfy_gims")
     */
    public function gimsAction(Request $request)
    {
        // Selection Types o - for organization, ou - for organization unit, m - for members
        $em = $this->getDoctrine()->getManager();
        $guids = $request->request->get('guids');
        $type = $request->request->get('type');

        $file_name = '/tmp/'.$request->request->get('frf');
        if ('/tmp/null' === $file_name) {
            $file_name = null;
        }
        $sender_app = $request->request->get('app');
        $subject = $request->request->get('subject');
        $message = str_replace(["\r", "\n"], '', trim(nl2br($request->request->get('message'))));

        $append_name = $request->request->get('_bm_display_sn');
        $append_ouname = $request->request->get('_bm_display_oun');
        $loggedUser = $this->getUser();
        $sender_info = '';
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($loggedUser);
        if ($append_name) {
            $sender_info = $employee->getEmployeeName();
        }
        if ($append_ouname) {
            $unitname = 'Sandes Portal Organization Unit <br/>'.$this->profileWorkspace->getOu()->getOUName();

            $sender_info = $sender_info.'<br/>'.$unitname;
        }

        $message = substr($message, 0, 4000);
        if (!$message) {
            return new JsonResponse(['status' => 'danger', 'message' => 'Hope matter is already entered, please re-check!']);
        }
      
        $message = $message.'<br/>'.$sender_info;

        $myCon = $em->getConnection();
        if ('ministry' === $type) {
            $dql = 'SELECT o.gu_id FROM gim.masters_ministries as g INNER JOIN gim.organization as o ON g.id = o.ministry_id WHERE g.gu_id = :param';
            $qryEmailList = $myCon->prepare($dql);
            $qryEmailList->bindValue(':param', $guids);
            $qryEmailList->execute();
            $qryEmailListResults = $qryEmailList->fetchAll(FetchMode::NUMERIC);
            foreach ($qryEmailListResults as $qryEmailListResult) {
                $return_data = $this->gims->sendOrganizationBroadCast($sender_app,  preg_replace('/\s+/', ' ', trim($message)), $qryEmailListResult[0], $file_name);
            }

            return new JsonResponse(['status' => $return_data['status'], 'message' => $return_data['message']]);
        } elseif ('o' === $type) {
            $return_data = $this->gims->sendOrganizationBroadCast($sender_app,  preg_replace('/\s+/', ' ', trim($message)), $guids, $file_name);

            return new JsonResponse(['status' => $return_data['status'], 'message' => $return_data['message']]);
        } elseif ('g' === $type) {
            $dql = 'SELECT g.name FROM gim.group as g WHERE g.gu_id  = :param';
            $qryEmailList = $myCon->prepare($dql);
            $qryEmailList->bindValue(':param', $guids);
            $qryEmailList->execute();
            $qryEmailListResults = $qryEmailList->fetchAll(FetchMode::NUMERIC);
            foreach ($qryEmailListResults as $qryEmailListResult) {
                $return_data = $this->gims->sendGroupBroadCast($sender_app,  preg_replace('/\s+/', ' ', trim($message)), $qryEmailListResult[0], $file_name);
            }

            return new JsonResponse(['status' => $return_data['status'], 'message' => $return_data['message']]);
        } elseif ('ou' === $type) {
            $dql = 'SELECT e.mobile_no FROM gim.employee as e INNER JOIN gim.organization_unit as ou ON e.ou_id = ou.ou_id WHERE ou.gu_id = :param';
            $qryEmailList = $myCon->prepare($dql);
            $qryEmailList->bindValue(':param', $guids);
            $qryEmailList->execute();
            $qryEmailListResults = $qryEmailList->fetchAll(FetchMode::NUMERIC);
            foreach ($qryEmailListResults as $qryEmailListResult) {
                $return_data = $this->gims->sendMulticast($sender_app,  preg_replace('/\s+/', ' ', trim($message)), '"'.$qryEmailListResult[0].'"', $file_name);
            }

            return new JsonResponse(['status' => $return_data['status'], 'message' => $return_data['message']]);
        } elseif ('m' === $type) {
            $guidParams = explode(',', $guids);
            $emailString = '';
            foreach ($guidParams as $guidParam) {
                $dql = 'SELECT e.mobile_no FROM gim.employee as e WHERE e.gu_id = :param';
                $qryEmailList = $myCon->prepare($dql);
                $qryEmailList->bindValue(':param', $guidParam);
                $qryEmailList->execute();
                $qryEmailListResult = $qryEmailList->fetch(FetchMode::NUMERIC);
                $emailString .= '"'.$qryEmailListResult[0].'",';
            }
            $return_data = $this->gims->sendMulticast($sender_app,  preg_replace('/\s+/', ' ', trim($message)), substr($emailString, 0, -1), $file_name);

            return new JsonResponse(['status' => $return_data['status'], 'message' => $return_data['message']]);
        } elseif ('mfb' === $type) {
            $EmployeeMessageEntity = $em->getRepository('App:Portal\EmployeeMessages')->findOneBy(['guId' => $guids]);

            $members = $EmployeeMessageEntity->getMembers();
            $emailString = '';
            foreach ($members as $key => $memberEmail) {
                $emailString .= '"'.$memberEmail.'",';
            }
            $return_data = $this->gims->sendMulticast($sender_app,  preg_replace('/\s+/', ' ', trim($message)),  substr($emailString, 0, -1), $file_name);

            return new JsonResponse(['status' => $return_data['status'], 'message' => $return_data['message']]);
        } else {
            return new JsonResponse(['status' => 'danger', 'message' => 'invalid selection type']);
        }
    }

    /**
     * @Route("/ntfy/upload/msgUploadSave", name="portal_msg_file_upload")
     */
    public function msgUploadSave(Request $request)
    {
        $fileBag = $request->files;
        $uploadedFile = $fileBag->get('file');
        $em = $this->getDoctrine()->getManager();
        if ($uploadedFile) {
            $fileName = $uploadedFile->getRealPath();
            $fileType = $uploadedFile->getMimeType();
            $fileOrginalName = $uploadedFile->getClientOriginalName();
            $ext = pathinfo($fileOrginalName, PATHINFO_EXTENSION);
            $uuid = \Ramsey\Uuid\Uuid::uuid4();
            // $file_new_name = $uuid->toString().'.'.$ext;
            $file_new_name = $fileOrginalName;
            $fileError = $uploadedFile->getError();
            if (UPLOAD_ERR_OK == $fileError || 0 == $fileError) {
                if ((false !== strpos($fileType, '/jpg')) || (false !== strpos($fileType, '/jpeg')) || (false !== strpos($fileType, '/png')) || (false !== strpos($fileType, '/pdf'))) {
                    $fileContent = file_get_contents($fileName);
                    // $uploaddir = $this->getParameter('kernel.project_dir').'/public/temp/';
                    $uploaddir = '/tmp/';
                    if (file_put_contents($uploaddir.$file_new_name, $fileContent)) {
                        $message = 'Successfully Uploaded!!';
                        $type = false;
                        $guId = $file_new_name;
                    } else {
                        $message = 'Uploaded but unable to move to folder!!';
                        $type = true;
                        $guId = '';
                    }
                    $result['error'] = $type;
                    $result['message'] = $message.$file_new_name;
                    $result['frf'] = $guId;

                    return new JsonResponse($result);
                } else {
                    $message = 'Uploaded but unable to move to folder!!';
                    $type = true;
                    $guId = '';
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
                echo json_encode([
                    'error' => true,
                    'message' => $message,
                ]);
            }
        } else {
            $result['error'] = true;
            $result['message'] = 'Select a file to upload';
        }
    }
}
