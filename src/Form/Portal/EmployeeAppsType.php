<?php

namespace App\Form\Portal;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\RouterInterface;


class EmployeeAppsType extends AbstractType
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
        // $builder->add('ExternalApps', ChoiceType::class, array(
        //     'placeholder' => 'Select Organization Unit ',
        //     'attr' => array('class' => 'searchable sbox-parentou'),
        // ));
        // $builder->add('ExternalApps', EntityType::class, array(
        //     'class' => 'App:Portal\ExternalApps',
        //     'choice_label' => 'appTitle',
        //     'placeholder' => 'Select Employer Apps ',
        //     'attr' => array('class' => 'searchable externalApp'),
        // ));
        $builder->add('ExternalApps', EntityType::class, array(
            'class' => 'App:Portal\ExternalApps',
            'placeholder' => '--------select --------',
            'choice_label' => 'appTitle',
            'attr' => array('class' => 'searchable externalApp'),
            'query_builder' => function (EntityRepository $repository) {
                $qb = $repository->createQueryBuilder('c')
                        ->where('c.allowPortalMessaging=true')
                        ->addOrderBy('c.appTitle');
                return $qb;
            },
        ));    
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
