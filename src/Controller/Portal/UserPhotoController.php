<?php

namespace App\Controller\Portal;

use App\Entity\UserPhoto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Interfaces\AuditableControllerInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\ProfileWorkspace;
use App\Services\ImageProcess;
use App\Entity\Portal\FileDetail;
use App\Entity\Portal\Employee;
use App\Services\XMPPGeneral;


/**
 * @Route("/upload")
 */
class UserPhotoController extends AbstractController implements AuditableControllerInterface
{
    private $profileWorkspace;
    private $metadata;
    private $xmppGeneral;
    private $imageProcess;

    public function __construct(ProfileWorkspace $profileWorkspace, \App\Services\PortalMetadata $metadata, ImageProcess $imageProcess, XMPPGeneral $xmpp)
    {
        $this->profileWorkspace = $profileWorkspace;
        $this->metadata = $metadata;
        $this->imageProcess = $imageProcess;
        $this->xmppGeneral = $xmpp;
    }

    /**
     * Creates a new UserPhoto entity.
     *
     * @Route("/create",name="user_photo_create")
     */
    public function createAction(Request $request)
    {
        $submittedToken = $request->request->get('token');

        // if (!$this->isCsrfTokenValid('form_intention', $submittedToken)) {
        //     return new JsonResponse(['status' => 'error', 'message' => 'Outdated request attempt, please try again via proper login']);
        // }
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $em = $this->getDoctrine()->getManager();
        $imageChunk = $request->request->get('img');
        $empGuId = $request->request->get('empGuId');
        if ('' != $imageChunk || null != $imageChunk) {
            $result = explode(',', $imageChunk);
            $imageData = $result[1];
        } else {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid/Incomplete file type/request']);
        }
        $minWidth = 150;
        $maxWidth = 250;
        $minHeight = 150;
        $maxHeight = 250;

        $decodedImageData = base64_decode($imageData);
        $imageDetails = getimagesize($imageChunk);
        $Imgsize = (int) ((strlen($imageChunk) * 3 / 4) - substr_count(substr($imageChunk, -2), '=')) / 1024;
        $mime = $imageDetails['mime'];
        $imageWidth = $imageDetails[0];
        $imageHeight = $imageDetails[1];
        if (10 < $Imgsize) {
            return new JsonResponse(['status' => 'error', 'message' => 'File size exceeds the allowed limit(10kb)'.$Imgsize]);
        }

        $allowdedTypes = array('image/jpeg', 'image/jpg', 'image/png');
        if (!in_array($mime, $allowdedTypes)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid Image Type.']);
        }
        if ($imageWidth > $maxWidth || $imageWidth < $minWidth || $imageHeight > $maxHeight || $imageHeight < $minHeight) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid File Dimesions.Please Upload 150x200 image']);
        }

        $employee = $em->getRepository('App:Portal\Employee')->findOneByGuId($empGuId);
        $fileDetail = $em->getRepository('App:Portal\FileDetail')->findOneById($employee->getPhoto());
        // Check whether this is default photos 0, 1 if so new file is required
        $photoId = $fileDetail->getId();

        // For each photo upload new photos will be created, there was issue with initial photos
        // if (4 === $photoId || 1 === $photoId) {
            $fileDetail = new FileDetail();
            $fileDetail->setCreatedDate(new \DateTime('now'));
        // }
        $uuid = \Ramsey\Uuid\Uuid::uuid4();
        $fileDetail->setFileHash($uuid->toString());
        $fileDetail->setFileData($decodedImageData);
        $thumb = $this->imageProcess->generateThumbnail($decodedImageData);
        $fileDetail->setThumbnail($thumb);
        $fileDetail->setContentTypeCode($em->getRepository('App:Portal\ContentType')->findOneByDescription($mime));
        $fileDetail->setFileType($em->getRepository('App:Portal\FileType')->findOneByCode('DP'));

        try {
            $em->persist($fileDetail);
            $employee->setPhoto($fileDetail);
            $em->persist($employee);

            $em->flush();
            $this->xmppGeneral->refreshProfileV5($employee->getGuId());

            return new JsonResponse(['status' => 'success', 'message' => 'Image Uploaded Successfully.']);
        } catch (Exception $ex) {
            return new JsonResponse(['status' => 'error', 'message' => 'Image Uploading Failed.']);
        }
    }

    /**
     * Edits an existing UserPhoto entity.
     */
    public function updateAction(Request $request, $id)
    {
        $submittedToken = $request->request->get('token');

        // if (!$this->isCsrfTokenValid('form_intention', $submittedToken)) {
        //     return new JsonResponse(['status' => 'error', 'message' => 'Outdated request attempt, please try again via proper login']);
        // }
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('form_intention');

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('App:UserPhoto')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find UserPhoto entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        $entity->setUpdateIp($request->getClientIp());
        $entity->setUpdateTime(new \DateTime());
        $entity->setUpdateUser($this->getUser());
        if ($editForm->isValid()) {
            $this->container->get('app.services')->setVerifierAndApproverStatusToFalse();
            $em->flush();

            return $this->redirect($this->generateUrl('albumsphotos_edit', array('id' => $id)));
        }

        return $this->render('App:UserPhoto:edit.html.twig', array(
                    'entity' => $entity,
                    'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * @Route("/photo", name="portal_upload_photo")
     */
    public function getFormView(Request $request)
    {
        $employeeGuId = $request->request->get('objid');
        $userPhoto = '';

        $em = $this->getDoctrine()->getManager();
        $employee = $em->getRepository('App:Portal\Employee')->findOneByGuId($employeeGuId);
        $fileDetail = $em->getRepository('App:Portal\FileDetail')->findOneById($employee->getPhoto());

        $csrf = $this->container->get('security.csrf.token_manager');
        $token = $csrf->refreshToken('yourkey');

        if ($fileDetail) {
            $userPhoto = base64_encode(stream_get_contents($fileDetail->getFileData()));
        }
        $formView = $this->renderView('UserPhoto/new.html.twig', array(
            'userPhoto' => $userPhoto,
            'employeeGuId' => $employeeGuId,
        ));

        return new JsonResponse($formView);
    }

    /**
     * @Route("/remove", name="portal_emp_remove_photo")
     */
    public function deleteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $jobseeker = $orJobSeeker = $this->get('app.profile.getworkspace')->getSelectedJobseeker();
        $entity = $em->getRepository('App:UserPhoto')->findOneByJobSeeker($jobseeker);
        if ($entity) {
            $em->remove($entity);
            $em->flush();

            return new JsonResponse(['status' => 'success', 'message' => 'Photo removed successfully']);
        }

        return new JsonResponse(['status' => 'error', 'message' => 'Unable to perform requested operation']);
    }

    /**
     * @Route("/genthumb", name="app_uphoto_genthumb")
     */
    public function generateThumb()
    {
        $em = $this->getDoctrine()->getManager();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $loggedUser = $this->getUser();
        $employee = $this->getDoctrine()->getRepository(Employee::class)->findOneByUser($loggedUser);
        $employeePhoto = $this->getDoctrine()->getRepository(FileDetail::class)->findOneById($employee->getPhoto());
        $encPhoto = $employeePhoto->getFileData();
        $thumb = $this->imageProcess->generateThumbnail(stream_get_contents($encPhoto));
        $employeePhoto->setThumbnail($thumb);
        $em->persist($employeePhoto);
        $em->flush();

        return new RedirectResponse($this->generateUrl('app_dashboard'));
    }
}
