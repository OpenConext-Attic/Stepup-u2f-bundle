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

namespace Surfnet\StepupU2fBundle\Tests\Yubico\U2f;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupU2fBundle\Yubico\U2f\ErrorHelper;
use u2flib_server\Error;

final class ErrorHelperTest extends TestCase
{
    /**
     * @test
     * @group yubico-u2f
     * @dataProvider registrationErrors
     *
     * @param int    $errorCode
     * @param string $expectedExceptionClassName
     */
    public function it_converts_registration_errors_to_specific_exceptions_based_on_their_code(
        $errorCode,
        $expectedExceptionClassName
    ) {
        $actualException = ErrorHelper::convertToRegistrationException(new Error('message', $errorCode));

        $this->assertEquals($expectedExceptionClassName, get_class($actualException));
    }

    public function registrationErrors()
    {
        // Autoload U2F class to load the constants which are defined in its file.
        class_exists('u2flib_server\U2F');

        return [
            'ERR_UNMATCHED_CHALLENGE'      => [
                \u2flib_server\ERR_UNMATCHED_CHALLENGE,
                'Surfnet\StepupU2fBundle\Exception\Registration\UnmatchedRegistrationChallengeException',
            ],
            'ERR_ATTESTATION_SIGNATURE'    => [
                \u2flib_server\ERR_ATTESTATION_SIGNATURE,
                'Surfnet\StepupU2fBundle\Exception\Registration\ResponseNotSignedByDeviceException',
            ],
            'ERR_ATTESTATION_VERIFICATION' => [
                \u2flib_server\ERR_ATTESTATION_VERIFICATION,
                'Surfnet\StepupU2fBundle\Exception\Registration\UntrustedDeviceException',
            ],
            'ERR_PUBKEY_DECODE'            => [
                \u2flib_server\ERR_PUBKEY_DECODE,
                'Surfnet\StepupU2fBundle\Exception\Registration\PublicKeyDecodingRegistrationException',
            ],
            'ERR_BAD_UA_RETURNING'         => [
                \u2flib_server\ERR_BAD_UA_RETURNING,
                'Surfnet\StepupU2fBundle\Exception\Registration\ClientRegistrationException',
            ],
            'Non-existent error code'      => [1337, 'Surfnet\StepupU2fBundle\Exception\LogicException',],
        ];
    }
}
