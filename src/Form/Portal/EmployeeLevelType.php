<?php

namespace App\Form\Portal;

use App\Entity\Portal\employeeLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class EmployeeLevelType extends AbstractType
{
    protected $csrf;

    public function __construct(CsrfTokenManagerInterface $csrf)
    {
        $this->csrf = $csrf;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('employeeLevelCode');
        $builder->add('employeeLevelName');
        $builder->add('organization', EntityType::class, array(
            'class' => 'App\Entity\Portal\Organization',
            'placeholder' => 'Select Organization ',
            'attr' => array('class' => 'searchable'),
        ));
        $builder->add('guId', HiddenType::class);
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
            'csrf_protection' => true, 'data_class' => employeeLevel::class,
        ]);
    }
}
