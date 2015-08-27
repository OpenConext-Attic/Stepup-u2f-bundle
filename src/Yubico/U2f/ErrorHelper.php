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

namespace Surfnet\StepupU2fBundle\Yubico\U2f;

use Surfnet\StepupU2fBundle\Exception\LogicException;
use Surfnet\StepupU2fBundle\Exception\Registration\ClientRegistrationException;
use Surfnet\StepupU2fBundle\Exception\Registration\PublicKeyDecodingRegistrationException;
use Surfnet\StepupU2fBundle\Exception\Registration\RegistrationException;
use Surfnet\StepupU2fBundle\Exception\Registration\ResponseNotSignedByDeviceException;
use Surfnet\StepupU2fBundle\Exception\Registration\UnmatchedRegistrationChallengeException;
use Surfnet\StepupU2fBundle\Exception\Registration\UntrustedDeviceException;
use u2flib_server\Error;

final class ErrorHelper
{
    /**
     * @param Error $error
     * @return RegistrationException|LogicException
     */
    public static function convertToRegistrationException(Error $error)
    {
        switch ($error->getCode()) {
            case \u2flib_server\ERR_UNMATCHED_CHALLENGE:
                return new UnmatchedRegistrationChallengeException(
                    'The response challenge does not match the request challenge',
                    $error
                );
            case \u2flib_server\ERR_ATTESTATION_SIGNATURE:
                return new ResponseNotSignedByDeviceException(
                    'The response\'s client data was not signed by the device',
                    $error
                );
            case \u2flib_server\ERR_ATTESTATION_VERIFICATION:
                return new UntrustedDeviceException(
                    'The attestation certificates could not attest the device\'s trustworthiness',
                    $error
                );
            case \u2flib_server\ERR_PUBKEY_DECODE:
                return new PublicKeyDecodingRegistrationException(
                    'The device\'s public key could not be decoded',
                    $error
                );
            case \u2flib_server\ERR_BAD_UA_RETURNING:
                return new ClientRegistrationException($error->getMessage(), $error);
            default:
                return new LogicException(
                    sprintf(
                        'An unexpected U2F exception with code %d was thrown during the U2F device registration ' .
                        'verification process',
                        $error->getCode()
                    ),
                    $error
                );
        }
    }
}
