<?php

namespace App\Form\Portal;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\RouterInterface;


class EmployeeMessageType extends AbstractType
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
        $builder->add('subject', TextType::class);
        $builder->add('message', TextAreaType::class);
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
