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

namespace Surfnet\StepupU2fBundle\Dto;

use Surfnet\StepupU2fBundle\Validator\Constraints as U2fAssert;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterResponse
{
    /**
     * Success. Not used in errors but reserved.
     *
     * @see https://fidoalliance.org/specs/fido-u2f-v1.0-nfc-bt-amendment-20150514/fido-u2f-javascript-api.html#error-codes
     */
    const ERROR_CODE_OK = 0;

    /**
     * An error otherwise not enumerated here.
     */
    const ERROR_CODE_OTHER_ERROR = 1;

    /**
     * The request cannot be processed.
     */
    const ERROR_CODE_BAD_REQUEST = 2;

    /**
     * Client configuration is not supported.
     */
    const ERROR_CODE_CONFIGURATION_UNSUPPORTED = 3;

    /**
     * The presented device is not eligible for this request. For a registration request this may mean that the token is
     * already registered.
     */
    const ERROR_CODE_DEVICE_INELIGIBLE = 4;

    /**
     * Timeout reached before request could be satisfied.
     */
    const ERROR_CODE_TIMEOUT = 5;

    /**
     * @Assert\NotBlank(message="Register request error code may not be empty")
     * @Assert\Type("numeric", message="Register request error code must be numeric")
     *
     * @var numeric|null
     * @see https://fidoalliance.org/specs/fido-u2f-v1.0-nfc-bt-amendment-20150514/fido-u2f-javascript-api.html#error-codes
     */
    public $errorCode;

    /**
     * @U2fAssert\RegistrationDataConstraint
     *
     * @var string
     */
    public $registrationData;

    /**
     * @U2fAssert\ClientDataConstraint
     *
     * @var string
     */
    public $clientData;
}
