<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupU2fBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RegisterDeviceType extends AbstractType
{
    public function getName()
    {
        return 'surfnet_stepup_u2f_register_device';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Surfnet\StepupU2fBundle\Dto\RegisterResponse',
            'register_request' => null,
        ]);
        $resolver->setRequired(['register_request']);
        $resolver->setAllowedTypes('register_request', ['Surfnet\StepupU2fBundle\Dto\RegisterRequest']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        if (!isset($view->vars['attr'])) {
            $view->vars['attr'] = [];
        }

        $view->vars['attr']['id'] = 'surfnet-stepup-u2f-register-device';
        $view->vars['attr']['data-u2f-register-request'] = json_encode($options['register_request']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('errorCode', 'hidden', [
            'attr' => [ 'data-u2f-register-response-field' => 'errorCode' ],
        ]);
        $builder->add('registrationData', 'hidden', [
            'attr' => [ 'data-u2f-register-response-field' => 'registrationData' ],
        ]);
        $builder->add('clientData', 'hidden', [
            'attr' => [ 'data-u2f-register-response-field' => 'clientData' ],
        ]);
    }
}
