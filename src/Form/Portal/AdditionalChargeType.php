<?php

namespace App\Form\Portal;

use App\Forms\Portal\EventSubscriber\UnassignedSeatSubscriber;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdditionalChargeType extends AbstractType
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
     * Builds the Additional Charge Registration form.
     *
     * @param \Symfony\Component\Form\FormBuilder $builder
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $factory = $builder->getFormFactory();
        $userOffice = $options['userOffice'];
        
        $employeeOffice = $options['employeeOffice'];
        
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('office', EntityType::class, [
                'class' => 'App:Portal\Office',
                'choice_label' => 'OfficeName',
                'placeholder' => 'Select Office',
            ]);
        } elseif ($this->security->isGranted('ROLE_ADS_DEPARTMENT')) {
            $builder->add('office', EntityType::class, [
                'class' => 'App:Portal\Office',
                'choice_label' => 'OfficeName',
                'query_builder' => function (EntityRepository $repository) {
                    $qb = $repository->createQueryBuilder('d')
                        ->where('d.id = :office')
                        ->setParameter('office', $userOffice->getId());

                    return $qb;
                },
            ]);
        } elseif ($this->security->isGranted('ROLE_OFFICE_ADMIN')) {
            $builder->add('office', EntityType::class, [
                'class' => 'App:Portal\Office',
                'choice_label' => 'OfficeName',
                'query_builder' => function (EntityRepository $repository) {
                    $qb = $repository->createQueryBuilder('d')
                        ->where('d.id = :office')
                        ->setParameter('office', $userOffice->getId());

                    return $qb;
                },
            ]);
        }
        $builder->add('roles', ChoiceType::class, [
            'choices' => [
                'Officer' => '1',
                'Clerk / Assistant' => 2,
            ],
            'mapped' => false,
            'expanded' => true,
            'multiple' => false,
        ]);
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            if (null == $builder->getData()) {
                $builder->addEventSubscriber(new UnassignedSeatSubscriber($factory));
            } else {
                $builder->addEventSubscriber(new UnassignedSeatSubscriber($factory));
            }
        } else {
            $office = $this->userOffice->getId();
            $builder->add('seat', 'entity', ['class' => 'App:Portal\Seat',
                    'placeholder' => '-- Select Seat--', 'label' => 'Seat',
                    'choice_value' => 'getSeatNameWithEmployee',
                    'query_builder' => function (EntityRepository $er) use ($office) {
                        $qb = $er->createQueryBuilder('s')
                                ->where('s.office = :office')
                                ->andWhere('s.employee IS NULL')
                                ->setParameter('office', $office);

                        return $qb;
                    },
            ]);
        }
        $builder->add('isAdditional', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, ['required' => false, 'label' => 'Is Additional Charge',
            'attr' => ['checked' => 'checked'], ]);
        $builder->add('save', SubmitType::class, ['attr' => ['class' => 'btn-primary']]);
        $builder->add('reset', ResetType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true, 'csrf_token_id' => 'form_intention', 'data_class' => 'App\Entity\Portal\Profile',
            'userOffice' => null, 'tokenStorage' => null, 'employeeOffice' => null,
        ]);
    }

    /**
     * Mandatory in Symfony2
     * Gets the unique name of this form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'AdditionalCharge';
    }
}
