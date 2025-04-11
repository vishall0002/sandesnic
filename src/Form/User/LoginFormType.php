<?php

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class LoginFormType extends AbstractType
{
    protected $csrf;

    public function __construct(CsrfTokenManagerInterface $csrf)
    {
        $this->csrf = $csrf;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', TextType::class, array(
            'required' => false,
        ));
        $builder->add('password', PasswordType::class, array(
            'required' => false,
        ));
        $builder->add('captcha', CaptchaType::class, array(
            'reload' => true,
            'as_url' => true,
            'height' => 34,
        ));
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $formName = $form->getName();
            $this->csrf->getToken($formName);
            $this->csrf->removeToken($formName);
        });
    }

    public function getBlockPrefix()
    {
        return 'user';
    }

    public function configureOptions(OptionsResolver $options)
    {
        return array(
            'csrf_protection' => true, 'csrf_token_id' => 'form_intention', 'data_class' => 'App\Entity\Portal\User',
        );
    }
}
