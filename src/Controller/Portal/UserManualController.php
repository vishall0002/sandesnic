<?php

namespace App\Controller\Portal;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class UserManualController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    /**
     * @Route("/um/", name="um_index")
     */
    public function indexAction()
    {
        return $this->render('portal/user_manual/index.html.twig');
    }
}
