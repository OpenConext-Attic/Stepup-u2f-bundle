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

namespace Surfnet\StepupU2fBundle\Tests\Service;

use Mockery as m;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupU2fBundle\Service\SigningService;
use Surfnet\StepupU2fBundle\Service\SigningVerificationResult;
use u2flib_server\Error;

final class SigningServiceTest extends TestCase
{
    const APP_ID = 'https://gateway.surfconext.nl/u2f/app-id';

    /**
     * @test
     * @group signing
     */
    public function it_can_request_signing_of_a_sign_request()
    {
        $keyHandle = 'key-handle';
        $challenge = 'challenge';
        $publicKey = 'public-key';

        $yubicoRequest = new \u2flib_server\SignRequest();
        $yubicoRequest->version = \u2flib_server\U2F_VERSION;
        $yubicoRequest->appId = self::APP_ID;
        $yubicoRequest->keyHandle = $keyHandle;
        $yubicoRequest->challenge = $challenge;

        $yubicoRegistration = new \u2flib_server\Registration();
        $yubicoRegistration->publicKey = $publicKey;
        $yubicoRegistration->keyHandle = $keyHandle;

        $registration = new \Surfnet\StepupU2fBundle\Dto\Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = $publicKey;

        $expectedRequest = new \Surfnet\StepupU2fBundle\Dto\SignRequest();
        $expectedRequest->version   = \u2flib_server\U2F_VERSION;
        $expectedRequest->challenge = $challenge;
        $expectedRequest->appId     = self::APP_ID;
        $expectedRequest->keyHandle = $keyHandle;

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('getAuthenticateData')->once()->with([$yubicoRegistration])->andReturn([$yubicoRequest]);

        $service = new SigningService($u2f);

        $this->assertEquals($expectedRequest, $service->requestSigning($registration));
    }

    /**
     * @test
     * @group signing
     */
    public function it_can_verify_a_signing_response()
    {
        $publicKey  = 'public-key';
        $keyHandle = 'key-handle';
        $challenge = 'challenge';

        $yubicoRequest = new \u2flib_server\SignRequest();
        $yubicoRequest->version = \u2flib_server\U2F_VERSION;
        $yubicoRequest->appId = self::APP_ID;
        $yubicoRequest->keyHandle = $keyHandle;
        $yubicoRequest->challenge = $challenge;

        $yubicoRegistration = new \u2flib_server\Registration();
        $yubicoRegistration->publicKey = $publicKey;
        $yubicoRegistration->keyHandle = $keyHandle;

        $registration = new \Surfnet\StepupU2fBundle\Dto\Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = $publicKey;

        $request = new \Surfnet\StepupU2fBundle\Dto\SignRequest();
        $request->version   = \u2flib_server\U2F_VERSION;
        $request->challenge = $challenge;
        $request->appId     = self::APP_ID;
        $request->keyHandle = $keyHandle;

        $response = new \Surfnet\StepupU2fBundle\Dto\SignResponse();
        $response->keyHandle = $keyHandle;
        $response->clientData = 'client-data';
        $response->signatureData = 'signature-data';

        $expectedResult = SigningVerificationResult::success();

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doAuthenticate')
            ->once()
            ->with(m::anyOf([$yubicoRequest]), m::anyOf([$yubicoRegistration]), m::anyOf($response))
            ->andReturn($yubicoRegistration);

        $service = new SigningService($u2f);

        $this->assertEquals($expectedResult, $service->verifySigning($registration, $request, $response));
    }

    /**
     * @test
     * @group signing
     * @dataProvider expectedVerificationErrors
     *
     * @param int $errorCode
     * @param SigningVerificationResult $expectedResult
     */
    public function it_handles_expected_u2f_registration_verification_errors(
        $errorCode,
        SigningVerificationResult $expectedResult
    ) {
        $keyHandle = 'key-handle';
        $challenge = 'challenge';
        $publicKey = 'public-key';

        $yubicoRequest = new \u2flib_server\SignRequest();
        $yubicoRequest->keyHandle = $keyHandle;
        $yubicoRequest->appId     = self::APP_ID;
        $yubicoRequest->version   = \u2flib_server\U2F_VERSION;
        $yubicoRequest->challenge = $challenge;

        $yubicoRegistration = new \u2flib_server\Registration();
        $yubicoRegistration->keyHandle = $keyHandle;
        $yubicoRegistration->publicKey = $publicKey;

        $registration = new \Surfnet\StepupU2fBundle\Dto\Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = $publicKey;

        $request = new \Surfnet\StepupU2fBundle\Dto\SignRequest();
        $request->version   = \u2flib_server\U2F_VERSION;
        $request->challenge = $challenge;
        $request->appId     = self::APP_ID;
        $request->keyHandle = $keyHandle;

        $response = new \Surfnet\StepupU2fBundle\Dto\SignResponse();
        $response->clientData = 'client-data';
        $response->keyHandle = $keyHandle;
        $response->signatureData = 'signature-data';

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doAuthenticate')
            ->once()
            ->with(m::anyOf([$yubicoRequest]), m::anyOf([$yubicoRegistration]), m::anyOf($response))
            ->andThrow(new Error('error', $errorCode));

        $service = new SigningService($u2f);

        $this->assertEquals($expectedResult, $service->verifySigning($registration, $request, $response));
    }

    public function expectedVerificationErrors()
    {
        // Autoload the U2F class to make sure the error constants are loaded which are also defined in the file.
        class_exists('u2flib_server\U2F');

        return [
            'requestResponseMismatch' => [
                \u2flib_server\ERR_NO_MATCHING_REQUEST,
                SigningVerificationResult::requestResponseMismatch()
            ],
            'responseRegistrationMismatch' => [
                \u2flib_server\ERR_NO_MATCHING_REGISTRATION,
                SigningVerificationResult::responseRegistrationMismatch()
            ],
            'responseWasNotSignedByDevice' => [
                \u2flib_server\ERR_AUTHENTICATION_FAILURE,
                SigningVerificationResult::responseWasNotSignedByDevice()
            ],
            'publicKeyDecodingFailed' => [
                \u2flib_server\ERR_PUBKEY_DECODE,
                SigningVerificationResult::publicKeyDecodingFailed()
            ],
        ];
    }

    /**
     * @test
     * @group signing
     * @dataProvider unexpectedVerificationErrors
     *
     * @param int $errorCode
     */
    public function it_throws_unexpected_u2f_registration_verification_errors($errorCode)
    {
        $challenge = 'challenge';
        $keyHandle = 'key-handle';
        $publicKey = 'public-key';

        $yubicoRequest = new \u2flib_server\SignRequest();
        $yubicoRequest->challenge = $challenge;
        $yubicoRequest->keyHandle = $keyHandle;
        $yubicoRequest->appId     = self::APP_ID;
        $yubicoRequest->version   = \u2flib_server\U2F_VERSION;

        $yubicoRegistration = new \u2flib_server\Registration();
        $yubicoRegistration->keyHandle = $keyHandle;
        $yubicoRegistration->publicKey = $publicKey;

        $registration = new \Surfnet\StepupU2fBundle\Dto\Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = $publicKey;

        $request = new \Surfnet\StepupU2fBundle\Dto\SignRequest();
        $request->version   = \u2flib_server\U2F_VERSION;
        $request->challenge = $challenge;
        $request->appId     = self::APP_ID;
        $request->keyHandle = $keyHandle;

        $response = new \Surfnet\StepupU2fBundle\Dto\SignResponse();
        $response->clientData = 'client-data';
        $response->keyHandle = $keyHandle;
        $response->signatureData = 'signature-data';

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doAuthenticate')
            ->once()
            ->with(m::anyOf([$yubicoRequest]), m::anyOf([$yubicoRegistration]), m::anyOf($response))
            ->andThrow(new Error('error', $errorCode));

        $service = new SigningService($u2f);

        $this->setExpectedExceptionRegExp('Surfnet\StepupU2fBundle\Exception\LogicException');
        $service->verifySigning($registration, $request, $response);
    }

    public function unexpectedVerificationErrors()
    {
        // Autoload the U2F class to make sure the error constants are loaded which are also defined in the file.
        class_exists('u2flib_server\U2F');

        return [
            [\u2flib_server\ERR_ATTESTATION_VERIFICATION],
            [\u2flib_server\ERR_BAD_RANDOM],
            [235789],
        ];
    }
}
