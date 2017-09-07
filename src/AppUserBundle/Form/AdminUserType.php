<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppUserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Special-purpose form type for administering users.
 */
class AdminUserType extends AbstractType
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
                ->add('notify', 'checkbox', array(
                    'label' => 'Notify user when institutions go silent',
                    'required' => false,
                ))
                ->add('enabled', 'checkbox', array(
                    'label' => 'Account Enabled',
                    'required' => false,
                ))
                ->add('roles', 'choice', array(
                    'label' => 'Roles',
                    'choices' => array(
                        'ROLE_ADMIN' => 'Admin',
                    ),
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false,
                ))
        ;
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
     * Get the name of the form.
     *
     * @return string
     */
    public function getName()
    {
        return 'appbundle_user';
    }
}
