<?php

namespace App\Form\Portal;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class EmployeeDeleteType extends AbstractType
{
    protected $csrf;
    protected $security;
    protected $router;

    public function __construct(RouterInterface $router, CsrfTokenManagerInterface $csrf, Security $security)
    {
        $this->csrf = $csrf;
        $this->security = $security;
        $this->router = $router;
    }

    /**
     * Builds the Employee Registration form.
     *
     * @param \Symfony\Component\Form\FormBuilder $builder
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder->add('deleteReason', EntityType::class, [
            'class' => 'App:Masters\DeleteReason',
            'choice_label' => 'reasonDescription',
            'placeholder' => 'Select Reason',
            'mapped' => false,
            'attr' => ['class' => 'searchable user-del-reason'],
            'query_builder' => function (EntityRepository $repository) {
                $qb = $repository->createQueryBuilder('dr')
                  ->where('dr.adminReason = :adminReason')
                  ->setParameter('adminReason', true);
                return $qb;
            },
        ]);
        
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true, 'data_class' => null,
            'tokenStorage' => null, 'em' => null,
        ]);
    }

    /**
     * Gets the unique name of this form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'delemployee';
    }
}
