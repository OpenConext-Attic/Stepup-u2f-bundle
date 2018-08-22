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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class VerifyDeviceAuthenticationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('errorCode', HiddenType::class, [
            'attr' => [ 'data-u2f-sign-response-field' => 'errorCode' ],
        ]);
        $builder->add('keyHandle', HiddenType::class, [
            'attr' => [ 'data-u2f-sign-response-field' => 'keyHandle' ],
        ]);
        $builder->add('signatureData', HiddenType::class, [
            'attr' => [ 'data-u2f-sign-response-field' => 'signatureData' ],
        ]);
        $builder->add('clientData', HiddenType::class, [
            'attr' => [ 'data-u2f-sign-response-field' => 'clientData' ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Surfnet\StepupU2fBundle\Dto\SignResponse',
            'sign_request' => null,
        ]);
        $resolver->setRequired(['sign_request']);
        $resolver->setAllowedTypes('sign_request', ['Surfnet\StepupU2fBundle\Dto\SignRequest']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        if (!isset($view->vars['attr'])) {
            $view->vars['attr'] = [];
        }

        $view->vars['attr']['id'] = 'surfnet-stepup-u2f-verify-device-authentication';
        $view->vars['attr']['data-u2f-sign-request'] = json_encode($options['sign_request']);
    }

    public function getBlockPrefix()
    {
        return 'surfnet_stepup_u2f_verify_device_authentication';
    }
}
