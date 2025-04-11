<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController implements \App\Interfaces\AuditableControllerInterface
{
    public function indexAction()
    {
        return $this->render('App\User:Default:index.html.twig');
    }
}
