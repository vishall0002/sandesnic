<?php

namespace App\Form\Portal;

use App\Entity\Portal\Designation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\RouterInterface;

class EmployeeTransferType extends AbstractType
{
    protected $csrf;
    protected $router;
    public function __construct(RouterInterface $router,CsrfTokenManagerInterface $csrf)
    {
        $this->csrf = $csrf;
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('ministry', EntityType::class, array(
            'class' => 'App\Entity\Masters\Ministry',
            'placeholder' => 'Select Ministry ',
            'attr' => array('class' => 'searchable sbox-trministry', 'data-path' => $this->router->generate('portal_o_get_os_by_ministry')),
        ));
        $builder->add('organization', ChoiceType::class, array(
            'placeholder' => 'Select Organization ',
            'attr' => array('class' => 'searchable sbox-tro', 'data-path' => $this->router->generate('portal_ou_get_ous_by_o')),
        ));
        $builder->add('organizationUnit', ChoiceType::class, array(
            'placeholder' => 'Select Organization Unit ',
            'attr' => array('class' => 'searchable sbox-parentou'),
        ));
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $formName = $form->getName();
            $this->csrf->getToken($formName);
            $this->csrf->removeToken($formName);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
