<?php

namespace AppUserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Override the PasswordType form so admins can change passwords.
 */
class PasswordType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('submit', 'submit', array('label' => 'Update'));
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
     * Get the parent form name.
     *
     * @return string
     */
    public function getParent()
    {
        return 'fos_user_change_password';
    }

    /**
     * Get the name of the form.
     *
     * @return string
     */
    public function getName()
    {
        return 'appbundle_user_password';
    }
}
