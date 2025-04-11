<?php

namespace App\Form\Portal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class EmployeeChangeRoleType extends AbstractType
{
    protected $csrf;

    public function __construct(CsrfTokenManagerInterface $csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $assignedRoles = $options['assignedRoles'];
        $availableRoles = $options['availableRoles'];

        $builder->add('employeeCode', TextType::class, array('attr' => array('readonly' => 'true')));
        $builder->add('assignedRoles', ChoiceType::class, array('choices' => $assignedRoles,
            'mapped' => false,
            'required' => false,
            'multiple' => true,
        ));

        $builder->add('availableRoles', ChoiceType::class, array('choices' => $availableRoles,
            'mapped' => false,
            'required' => false,
            'multiple' => true,
        ));

        $builder->add('save', SubmitType::class, array('label' => 'Select above role(s) and click here to add >> ', 'attr' => array('class' => 'btn btn-sm btn-success')));
        $builder->add('save1', SubmitType::class, array('label' => ' << Select above role(s) and click to remove', 'attr' => array('class' => 'btn btn-sm btn-danger')));
        $builder->add('reset', ResetType::class, array('label' => 'Reset Fields'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $formName = $form->getName();
            $this->csrf->getToken($formName);
            $this->csrf->removeToken($formName);
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => true, 'data_class' => 'App\Entity\Portal\Employee',
            'assignedRoles' => null, 'availableRoles' => null,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'EmployeeChangeRole';
    }
}
