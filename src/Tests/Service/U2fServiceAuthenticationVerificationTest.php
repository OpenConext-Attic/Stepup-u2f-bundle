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
use Surfnet\StepupU2fBundle\Dto\Registration;
use Surfnet\StepupU2fBundle\Dto\SignRequest;
use Surfnet\StepupU2fBundle\Dto\SignResponse;
use Surfnet\StepupU2fBundle\Service\AuthenticationVerificationResult;
use Surfnet\StepupU2fBundle\Service\U2fService;
use Surfnet\StepupU2fBundle\Value\AppId;
use u2flib_server\Error;
use u2flib_server\Registration as YubicoRegistration;
use u2flib_server\SignRequest as YubicoSignRequest;

final class U2fServiceAuthenticationVerificationTest extends TestCase
{
    const APP_ID = 'https://gateway.surfconext.invalid/u2f/app-id';

    /**
     * @test
     * @group authentication
     */
    public function it_can_request_signing_of_a_sign_request()
    {
        $keyHandle = 'key-handle';
        $challenge = 'challenge';
        $publicKey = 'public-key';

        $yubicoRequest            = new YubicoSignRequest();
        $yubicoRequest->version   = \u2flib_server\U2F_VERSION;
        $yubicoRequest->appId     = self::APP_ID;
        $yubicoRequest->keyHandle = $keyHandle;
        $yubicoRequest->challenge = $challenge;

        $yubicoRegistration            = new YubicoRegistration();
        $yubicoRegistration->publicKey = $publicKey;
        $yubicoRegistration->keyHandle = $keyHandle;

        $registration            = new Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = $publicKey;

        $expectedRequest            = new SignRequest();
        $expectedRequest->version   = \u2flib_server\U2F_VERSION;
        $expectedRequest->challenge = $challenge;
        $expectedRequest->appId     = self::APP_ID;
        $expectedRequest->keyHandle = $keyHandle;

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('getAuthenticateData')->once()->with([$yubicoRegistration])->andReturn([$yubicoRequest]);

        $service = new U2fService(new AppId(self::APP_ID), $u2f);

        $this->assertEquals($expectedRequest, $service->requestAuthentication($registration));
    }

    /**
     * @test
     * @group authentication
     */
    public function it_can_verify_a_signing_response()
    {
        $publicKey  = 'public-key';
        $keyHandle = 'key-handle';
        $challenge = 'challenge';

        $yubicoRequest            = new YubicoSignRequest();
        $yubicoRequest->version   = \u2flib_server\U2F_VERSION;
        $yubicoRequest->appId     = self::APP_ID;
        $yubicoRequest->keyHandle = $keyHandle;
        $yubicoRequest->challenge = $challenge;

        $yubicoRegistration            = new YubicoRegistration();
        $yubicoRegistration->publicKey = $publicKey;
        $yubicoRegistration->keyHandle = $keyHandle;

        $registration            = new Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = $publicKey;

        $request            = new SignRequest();
        $request->version   = \u2flib_server\U2F_VERSION;
        $request->challenge = $challenge;
        $request->appId     = self::APP_ID;
        $request->keyHandle = $keyHandle;

        $response                = new SignResponse();
        $response->keyHandle     = $keyHandle;
        $response->clientData    = 'client-data';
        $response->signatureData = 'signature-data';

        $yubicoResponse                = new \stdClass;
        $yubicoResponse->keyHandle     = $response->keyHandle;
        $yubicoResponse->signatureData = $response->signatureData;
        $yubicoResponse->clientData    = $response->clientData;

        $expectedResult = AuthenticationVerificationResult::success($registration);

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doAuthenticate')
            ->once()
            ->with(m::anyOf([$yubicoRequest]), m::anyOf([$yubicoRegistration]), m::anyOf($yubicoResponse))
            ->andReturn($yubicoRegistration);

        $service = new U2fService(new AppId(self::APP_ID), $u2f);

        $this->assertEquals($expectedResult, $service->verifyAuthentication($registration, $request, $response));
    }

    /**
     * @test
     * @group authentication
     * @dataProvider expectedVerificationErrors
     *
     * @param int $errorCode
     * @param AuthenticationVerificationResult $expectedResult
     */
    public function it_handles_expected_u2f_registration_verification_errors(
        $errorCode,
        AuthenticationVerificationResult $expectedResult
    ) {
        $keyHandle = 'key-handle';
        $challenge = 'challenge';
        $publicKey = 'public-key';

        $yubicoRequest            = new YubicoSignRequest();
        $yubicoRequest->keyHandle = $keyHandle;
        $yubicoRequest->appId     = self::APP_ID;
        $yubicoRequest->version   = \u2flib_server\U2F_VERSION;
        $yubicoRequest->challenge = $challenge;

        $yubicoRegistration            = new YubicoRegistration();
        $yubicoRegistration->keyHandle = $keyHandle;
        $yubicoRegistration->publicKey = $publicKey;

        $registration            = new Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = $publicKey;

        $request            = new SignRequest();
        $request->version   = \u2flib_server\U2F_VERSION;
        $request->challenge = $challenge;
        $request->appId     = self::APP_ID;
        $request->keyHandle = $keyHandle;

        $response                = new SignResponse();
        $response->clientData    = 'client-data';
        $response->keyHandle     = $keyHandle;
        $response->signatureData = 'signature-data';

        $yubicoResponse                = new \stdClass;
        $yubicoResponse->keyHandle     = $response->keyHandle;
        $yubicoResponse->signatureData = $response->signatureData;
        $yubicoResponse->clientData    = $response->clientData;

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doAuthenticate')
            ->once()
            ->with(m::anyOf([$yubicoRequest]), m::anyOf([$yubicoRegistration]), m::anyOf($yubicoResponse))
            ->andThrow(new Error('error', $errorCode));

        $service = new U2fService(new AppId(self::APP_ID), $u2f);

        $this->assertEquals($expectedResult, $service->verifyAuthentication($registration, $request, $response));
    }

    public function expectedVerificationErrors()
    {
        // Autoload the U2F class to make sure the error constants are loaded which are also defined in the file.
        class_exists('u2flib_server\U2F');

        return [
            'requestResponseMismatch' => [
                \u2flib_server\ERR_NO_MATCHING_REQUEST,
                AuthenticationVerificationResult::requestResponseMismatch()
            ],
            'responseRegistrationMismatch' => [
                \u2flib_server\ERR_NO_MATCHING_REGISTRATION,
                AuthenticationVerificationResult::responseRegistrationMismatch()
            ],
            'responseWasNotSignedByDevice' => [
                \u2flib_server\ERR_AUTHENTICATION_FAILURE,
                AuthenticationVerificationResult::responseWasNotSignedByDevice()
            ],
            'publicKeyDecodingFailed' => [
                \u2flib_server\ERR_PUBKEY_DECODE,
                AuthenticationVerificationResult::publicKeyDecodingFailed()
            ],
        ];
    }

    /**
     * @test
     * @group authentication
     * @dataProvider unexpectedVerificationErrors
     *
     * @param int $errorCode
     */
    public function it_throws_unexpected_u2f_registration_verification_errors($errorCode)
    {
        $challenge = 'challenge';
        $keyHandle = 'key-handle';
        $publicKey = 'public-key';

        $yubicoRequest            = new YubicoSignRequest();
        $yubicoRequest->challenge = $challenge;
        $yubicoRequest->keyHandle = $keyHandle;
        $yubicoRequest->appId     = self::APP_ID;
        $yubicoRequest->version   = \u2flib_server\U2F_VERSION;

        $yubicoRegistration            = new YubicoRegistration();
        $yubicoRegistration->keyHandle = $keyHandle;
        $yubicoRegistration->publicKey = $publicKey;

        $registration            = new Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = $publicKey;

        $request            = new SignRequest();
        $request->version   = \u2flib_server\U2F_VERSION;
        $request->challenge = $challenge;
        $request->appId     = self::APP_ID;
        $request->keyHandle = $keyHandle;

        $response                = new SignResponse();
        $response->clientData    = 'client-data';
        $response->keyHandle     = $keyHandle;
        $response->signatureData = 'signature-data';

        $yubicoResponse                = new \stdClass;
        $yubicoResponse->keyHandle     = $response->keyHandle;
        $yubicoResponse->signatureData = $response->signatureData;
        $yubicoResponse->clientData    = $response->clientData;

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doAuthenticate')
            ->once()
            ->with(m::anyOf([$yubicoRequest]), m::anyOf([$yubicoRegistration]), m::anyOf($yubicoResponse))
            ->andThrow(new Error('error', $errorCode));

        $service = new U2fService(new AppId(self::APP_ID), $u2f);

        $this->setExpectedExceptionRegExp('Surfnet\StepupU2fBundle\Exception\LogicException');
        $service->verifyAuthentication($registration, $request, $response);
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

    /**
     * @test
     * @group authentication
     * @dataProvider expectedDeviceErrors
     *
     * @param int $deviceErrorCode
     */
    public function it_handles_expected_device_errors($deviceErrorCode)
    {
        $keyHandle = 'key-handle';
        $challenge = 'challenge';

        $request            = new SignRequest();
        $request->version   = \u2flib_server\U2F_VERSION;
        $request->challenge = $challenge;
        $request->appId     = self::APP_ID;
        $request->keyHandle = $keyHandle;

        $response                = new SignResponse();
        $response->errorCode     = $deviceErrorCode;
        $response->clientData    = 'client-data';
        $response->keyHandle     = $keyHandle;
        $response->signatureData = 'signature-data';

        $registration            = new Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = 'public-key';

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doAuthenticate')->never();

        $service = new U2fService(new AppId(self::APP_ID), $u2f);

        $expectedResult = AuthenticationVerificationResult::deviceReportedError($deviceErrorCode);
        $this->assertEquals($expectedResult, $service->verifyAuthentication($registration, $request, $response));
    }

    public function expectedDeviceErrors()
    {
        // Autoload the U2F class to make sure the error constants are loaded which are also defined in the file.
        class_exists('u2flib_server\U2F');

        return [
            'ERROR_CODE_OTHER_ERROR' => [
                SignResponse::ERROR_CODE_OTHER_ERROR,
            ],
            'ERROR_CODE_BAD_REQUEST' => [
                SignResponse::ERROR_CODE_BAD_REQUEST,
            ],
            'ERROR_CODE_CONFIGURATION_UNSUPPORTED' => [
                SignResponse::ERROR_CODE_CONFIGURATION_UNSUPPORTED,
            ],
            'ERROR_CODE_DEVICE_INELIGIBLE' => [
                SignResponse::ERROR_CODE_DEVICE_INELIGIBLE,
            ],
            'ERROR_CODE_TIMEOUT' => [
                SignResponse::ERROR_CODE_TIMEOUT,
            ],
        ];
    }

    /**
     * @test
     * @group authentication
     */
    public function it_rejects_the_registration_when_app_ids_dont_match()
    {
        $keyHandle = 'key-handle';

        $request            = new SignRequest();
        $request->version   = \u2flib_server\U2F_VERSION;
        $request->challenge = 'challenge';
        $request->appId     = 'https://hacker.invalid/epp-aai-die';
        $request->keyHandle = $keyHandle;

        $response                = new SignResponse();
        $response->clientData    = 'client-data';
        $response->keyHandle     = $keyHandle;
        $response->signatureData = 'signature-data';

        $registration            = new Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = 'public-key';

        $service = new U2fService(new AppId(self::APP_ID), m::mock('u2flib_server\U2F'));

        $this->assertEquals(
            AuthenticationVerificationResult::appIdMismatch(),
            $service->verifyAuthentication($registration, $request, $response)
        );
    }

    /**
     * @test
     * @group authentication
     */
    public function it_checks_the_sign_responses_counter_against_the_registrations_counter()
    {
        $keyHandle = 'key-handle';

        $request            = new SignRequest();
        $request->version   = \u2flib_server\U2F_VERSION;
        $request->challenge = 'challenge';
        $request->appId     = self::APP_ID;
        $request->keyHandle = $keyHandle;

        $response                = new SignResponse();
        $response->clientData    = 'client-data';
        $response->keyHandle     = $keyHandle;
        $response->signatureData = 'signature-data';

        $registration            = new Registration();
        $registration->keyHandle = $keyHandle;
        $registration->publicKey = 'public-key';

        $u2fService = m::mock('u2flib_server\U2F');
        $u2fService->shouldReceive('doAuthenticate')->andThrow(
            new Error('Counter too low.', \u2flib_server\ERR_COUNTER_TOO_LOW)
        );

        $service = new U2fService(new AppId(self::APP_ID), $u2fService);

        $this->assertEquals(
            AuthenticationVerificationResult::signCounterTooLow(),
            $service->verifyAuthentication($registration, $request, $response)
        );
    }
}
