<?php

namespace App\Form\Portal;

use App\Entity\Portal\Designation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Security;
use App\Form\DataTransformer\OrganizationTransformer;
use Doctrine\ORM\EntityRepository;

class DesignationType extends AbstractType
{
    protected $csrf;
    protected $security;
    private $transformerOrg;

    public function __construct(CsrfTokenManagerInterface $csrf, Security $security, OrganizationTransformer $transformerOrg)
    {
        $this->csrf = $csrf;
        $this->security = $security;
        $this->transformerOrg = $transformerOrg;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $organization = $options['org'];   
        $Ministry = $options['ministry'];  
        $builder->add('designationName');
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('organization', TextType::class,  array(
                'attr' => array('data-use' => 'Organization','class'=>'codefinder'),
            ));
        }
        else if($this->security->isGranted('ROLE_MINISTRY_ADMIN'))
        {
            $builder->add('organization', EntityType::class, array(
                'class' => 'App\Entity\Portal\Organization',
                'placeholder' => 'Select Organization',
                'attr' => array('class' => 'searchable oubox'),
                'query_builder' => function (EntityRepository $repository) use ($Ministry) {
                    $qb = $repository->createQueryBuilder('o')
                        ->Where('o.ministry = :ministry')
                        ->setParameter('ministry', $Ministry);
                    return $qb;
                },
                'attr' => array('class' => 'searchable')
            ));
        }
        else
        {
            $builder->add('organization', EntityType::class, array(
                'class' => 'App\Entity\Portal\Organization',
                'placeholder' => 'Select Organization ',
                'attr' => array('class' => 'searchable oubox'),
                'choice_attr' => function ($organization) {
                    return ['data-prefix' => strtolower($organization->getOrganizationCode())];
                },
            ));
        }
        $builder->add('guId', HiddenType::class);
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) 
        {
            $builder->get('organization')->addModelTransformer($this->transformerOrg);
        }
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
            'csrf_protection' => true, 'data_class' => Designation::class,
            'org' => null,
            'ministry' => null
        ]);
    }

    /**
     * Gets the unique name of this form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'Group';
    }
}
