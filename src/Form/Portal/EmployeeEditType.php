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

class EmployeeEditType extends AbstractType
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
        $ou = $options['ou'];
        
        $em = $options['em'];
        $factory = $builder->getFormFactory();

        
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('organizationUnit', EntityType::class, [
                'class' => 'App:Portal\OrganizationUnit',
                'choice_label' => 'OUName',
                'placeholder' => 'Select Organization Unit',
                'attr' => ['class' => 'searchable'],
            ]);
            $builder->add('designation', EntityType::class, [
            'class' => 'App:Portal\Designation',
            'choice_label' => 'DesignationName',
            'placeholder' => 'Select Designation',
            'attr' => ['class' => 'searchable'],
            'query_builder' => function (EntityRepository $repository) use ($ou) {
                $qb = $repository->createQueryBuilder('g')
                  ->addOrderBy('g.designationName');

                return $qb;
            },
            ]);
            $builder->add('employeeLevelID', EntityType::class, [
            'class' => 'App:Portal\EmployeeLevel',
            'choice_label' => 'employeeLevelName',
            'placeholder' => 'Select Employee Level',
            'attr' => ['class' => 'searchable'],
            'query_builder' => function (EntityRepository $repository) use ($ou) {
                $qb = $repository->createQueryBuilder('g')
                  ->addOrderBy('g.levelNumber');

                return $qb;
            },
            ]);
        } else {
            $builder->add('designation', EntityType::class, [
                'class' => 'App:Portal\Designation',
                'choice_label' => 'DesignationName',
                'placeholder' => 'Select Designation',
               'attr' => ['class' => 'searchable'],
                'query_builder' => function (EntityRepository $repository) use ($ou) {
                    $qb = $repository->createQueryBuilder('g')
                      ->where('g.organization = :ou')
                      ->addOrderBy('g.designationName')
                      ->setParameter('ou', $ou->getOrganization());

                    return $qb;
                },
            ]);
            $builder->add('employeeLevelID', EntityType::class, [
                'class' => 'App:Portal\EmployeeLevel',
                'choice_label' => 'employeeLevelName',
                'placeholder' => 'Select Designation',
                'attr' => ['class' => 'searchable'],
                'query_builder' => function (EntityRepository $repository) use ($ou) {
                    $qb = $repository->createQueryBuilder('g')
                      ->where('g.organization = :ou')
                      ->addOrderBy('g.levelNumber')
                      ->setParameter('ou', $ou->getOrganization());

                    return $qb;
                },
            ]);
        }
        $builder->add('guId', HiddenType::class);
        $builder->add('employeeCode', TextType::class);
        $builder->add('employeeName', TextType::class);
        $builder->add('gender', EntityType::class, [
            'class' => 'App:Masters\Gender',
            'choice_label' => 'gender',
            'placeholder' => 'Select Gender',
            'attr' => ['class' => 'searchable'],
        ]);
        $builder->add('emailAddress', EmailType::class);
        $builder->add('mobileNumber', TextType::class);
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
            'csrf_protection' => true, 'data_class' => 'App\Entity\Portal\Employee',
            'ou' => null, 'tokenStorage' => null, 'em' => null,
        ]);
    }

    /**
     * Gets the unique name of this form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'Employee';
    }
}
