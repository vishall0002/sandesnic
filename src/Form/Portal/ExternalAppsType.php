<?php

namespace App\Form\Portal;

use App\Entity\Portal\ExternalApps;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ExternalAppsType extends AbstractType
{
    protected $csrf;

    public function __construct(CsrfTokenManagerInterface $csrf)
    {
        $this->csrf = $csrf;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('appTitle');
        $builder->add('appName');
        $builder->add('appDescription');
        $builder->add('hmacKey');
        $builder->add('ipWhiteList');
        $builder->add('mobileNumber', TextType::class, 
                array('mapped' => false)
            );
        $builder->add('clientId', HiddenType::class);
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
            'csrf_protection' => true, /*'csrf_token_id' => 'form_intention',*/ 'data_class' => ExternalApps::class,
        ]);
    }
}
