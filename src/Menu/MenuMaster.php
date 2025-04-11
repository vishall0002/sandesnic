<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class MenuMaster
{
    private $factory;
    private $security;

    public function __construct(FactoryInterface $factory, Security $security)
    {
        $this->factory = $factory;
        $this->security = $security;
    }

    public function mainMenu(RequestStack $requestStack)
    {
        $loggedUser = $this->security->getUser();
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav');
        $menu->addChild('Menu', array('uri' => '#'))->setAttributes(['icon' => 'fa fa-indent', 'dropdown' => 'true'])->setChildrenAttribute('class', 'treeview-menu mb-0');
        if ($loggedUser and $loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $menu['Menu']->addChild('Organizations', array('route' => 'portal_o_index'));
            $menu['Menu']->addChild('Organization Types', array('route' => 'msr_out_index'));
            $menu['Menu']->addChild('Organization Units', array('route' => 'portal_ou_index'));
        }
        if ($loggedUser and $loggedUser->hasRole('ROLE_SUPER_ADMIN')) {
            $menu['Menu']->addChild('Designations', array('route' => 'portal_designation_index'));
            $menu['Menu']->addChild('Employee Levels', array('route' => 'portal_employee_level_index'));
        }
        $menu['Menu']->addChild('Members', array('route' => 'portal_emp_index'));

        return $menu;
    }
}
