<?php

namespace AppUserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Overrides the ProfileType from FOSUserBundle so admins can
 * edit profiles.
 */
class ProfileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->remove('username')
                ->add('email')
                ->add('fullname')
                ->add('institution')
                ->add('submit', 'submit', array('label' => 'Update'));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppUserBundle\Entity\User',
        ));
    }

    /**
     * Get the parent form.
     *
     * @return string
     */
    public function getParent()
    {
        return 'fos_user_profile';
    }

    /**
     * Get the form name.
     *
     * @return string
     */
    public function getName()
    {
        return 'appbundle_user_profile';
    }
}
