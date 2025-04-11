<?php

namespace App\Form\Portal;

use App\Entity\Portal\Upload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class UploadType extends AbstractType
{
    protected $csrf;

    public function __construct(CsrfTokenManagerInterface $csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * Builds the Upload Registration form.
     *
     * @param \Symfony\Component\Form\FormBuilder $builder
     * @param array                               $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $factory = $builder->getFormFactory();

        $builder->add('guId', HiddenType::class);
        $builder->add('appVersion', TextType::class);
        $builder->add('appBuildNo', TextType::class);
        $builder->add('appVersionNo', TextType::class);
        $builder->add('appReleaseNotes', TextAreaType::class);

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
            'csrf_protection' => true, 'data_class' => Upload::class,
             'em' => null, 'profile' => null,
        ));
    }

    /**
     * Gets the unique name of this form.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'Upload';
    }

}
