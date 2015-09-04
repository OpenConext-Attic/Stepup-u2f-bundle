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
use Surfnet\StepupU2fBundle\Service\U2fService;
use Surfnet\StepupU2fBundle\Service\RegistrationVerificationResult;
use u2flib_server\Error;

final class U2fServiceRegistrationTest extends TestCase
{
    const APP_ID = 'https://gateway.surfconext.invalid/u2f/app-id';

    /**
     * @test
     * @group registration
     */
    public function it_can_request_registration_of_a_u2f_device()
    {
        $yubicoRequest = new \u2flib_server\RegisterRequest('challenge', self::APP_ID);

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('getRegisterData')->once()->with()->andReturn([$yubicoRequest, []]);

        $service = new U2fService($u2f);

        $expectedRequest = new \Surfnet\StepupU2fBundle\Dto\RegisterRequest();
        $expectedRequest->version   = 'U2F_V2';
        $expectedRequest->challenge = 'challenge';
        $expectedRequest->appId     = self::APP_ID;

        $this->assertEquals($expectedRequest, $service->requestRegistration());
    }

    /**
     * @test
     * @group registration
     */
    public function it_can_register_a_u2f_device()
    {
        $publicId = 'public-key';
        $keyHandle = 'key-handle';

        $yubicoRequest = new \u2flib_server\RegisterRequest('challenge', self::APP_ID);

        $yubicoRegistration = new \u2flib_server\Registration();
        $yubicoRegistration->publicKey = $publicId;
        $yubicoRegistration->keyHandle = $keyHandle;
        $yubicoRegistration->certificate = 'certificate';
        $yubicoRegistration->counter = 0;

        $request = new \Surfnet\StepupU2fBundle\Dto\RegisterRequest();
        $request->version   = 'U2F_V2';
        $request->challenge = 'challenge';
        $request->appId     = self::APP_ID;

        $response = new \Surfnet\StepupU2fBundle\Dto\RegisterResponse();
        $response->registrationData = 'registration-data';
        $response->clientData = 'client-data';

        $yubicoResponse = new \stdClass;
        $yubicoResponse->clientData    = $response->clientData;
        $yubicoResponse->registrationData = $response->registrationData;

        $expectedRegistration = new \Surfnet\StepupU2fBundle\Dto\Registration();
        $expectedRegistration->publicKey = $publicId;
        $expectedRegistration->keyHandle = $keyHandle;

        $expectedResult = RegistrationVerificationResult::success($expectedRegistration);

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doRegister')
            ->once()
            ->with(m::anyOf($yubicoRequest), m::anyOf($yubicoResponse))
            ->andReturn($yubicoRegistration);

        $service = new U2fService($u2f);

        $this->assertEquals($expectedResult, $service->verifyRegistration($request, $response));
    }

    /**
     * @test
     * @group registration
     * @dataProvider expectedVerificationErrors
     *
     * @param int $errorCode
     * @param RegistrationVerificationResult $expectedResult
     */
    public function it_handles_expected_u2f_registration_verification_errors(
        $errorCode,
        RegistrationVerificationResult $expectedResult
    ) {
        $yubicoRequest = new \u2flib_server\RegisterRequest('challenge', self::APP_ID);

        $request = new \Surfnet\StepupU2fBundle\Dto\RegisterRequest();
        $request->version   = 'U2F_V2';
        $request->challenge = 'challenge';
        $request->appId     = self::APP_ID;

        $response = new \Surfnet\StepupU2fBundle\Dto\RegisterResponse();
        $response->registrationData = 'registration-data';
        $response->clientData = 'client-data';

        $yubicoResponse = new \stdClass;
        $yubicoResponse->clientData    = $response->clientData;
        $yubicoResponse->registrationData = $response->registrationData;

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doRegister')
            ->once()
            ->with(m::anyOf($yubicoRequest), m::anyOf($yubicoResponse))
            ->andThrow(new Error('error', $errorCode));

        $service = new U2fService($u2f);

        $this->assertEquals($expectedResult, $service->verifyRegistration($request, $response));
    }

    public function expectedVerificationErrors()
    {
        // Autoload the U2F class to make sure the error constants are loaded which are also defined in the file.
        class_exists('u2flib_server\U2F');

        return [
            'responseChallengeDidNotMatchRequestChallenge' => [
                \u2flib_server\ERR_UNMATCHED_CHALLENGE,
                RegistrationVerificationResult::responseChallengeDidNotMatchRequestChallenge()
            ],
            'responseWasNotSignedByDevice' => [
                \u2flib_server\ERR_ATTESTATION_SIGNATURE,
                RegistrationVerificationResult::responseWasNotSignedByDevice()
            ],
            'deviceCannotBeTrusted' => [
                \u2flib_server\ERR_ATTESTATION_VERIFICATION,
                RegistrationVerificationResult::deviceCannotBeTrusted()
            ],
            'publicKeyDecodingFailed' => [
                \u2flib_server\ERR_PUBKEY_DECODE,
                RegistrationVerificationResult::publicKeyDecodingFailed()
            ],
        ];
    }

    /**
     * @test
     * @group registration
     * @dataProvider unexpectedVerificationErrors
     *
     * @param int $errorCode
     */
    public function it_throws_unexpected_u2f_registration_verification_errors($errorCode)
    {
        $yubicoRequest = new \u2flib_server\RegisterRequest('challenge', self::APP_ID);

        $request = new \Surfnet\StepupU2fBundle\Dto\RegisterRequest();
        $request->version   = 'U2F_V2';
        $request->challenge = 'challenge';
        $request->appId     = self::APP_ID;

        $response = new \Surfnet\StepupU2fBundle\Dto\RegisterResponse();
        $response->registrationData = 'registration-data';
        $response->clientData = 'client-data';

        $yubicoResponse = new \stdClass;
        $yubicoResponse->clientData    = $response->clientData;
        $yubicoResponse->registrationData = $response->registrationData;

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doRegister')
            ->once()
            ->with(m::anyOf($yubicoRequest), m::anyOf($yubicoResponse))
            ->andThrow(new Error('error', $errorCode));

        $service = new U2fService($u2f);

        $this->setExpectedExceptionRegExp('Surfnet\StepupU2fBundle\Exception\LogicException');
        $service->verifyRegistration($request, $response);
    }

    public function unexpectedVerificationErrors()
    {
        // Autoload the U2F class to make sure the error constants are loaded which are also defined in the file.
        class_exists('u2flib_server\U2F');

        return [
            [\u2flib_server\ERR_AUTHENTICATION_FAILURE],
            [\u2flib_server\ERR_BAD_RANDOM],
            [235789],
        ];
    }

    /**
     * @test
     * @group registration
     * @dataProvider deviceErrorCodes
     *
     * @param int $deviceErrorCode
     * @param string $errorMethod
     */
    public function it_handles_device_errors($deviceErrorCode, $errorMethod)
    {
        $request = new \Surfnet\StepupU2fBundle\Dto\RegisterRequest();
        $request->version   = 'U2F_V2';
        $request->challenge = 'challenge';
        $request->appId     = self::APP_ID;

        $response = new \Surfnet\StepupU2fBundle\Dto\RegisterResponse();
        $response->errorCode = $deviceErrorCode;

        $service = new U2fService(m::mock('u2flib_server\U2F'));
        $result = $service->verifyRegistration($request, $response);

        $this->assertTrue($result->$errorMethod(), "Registration result should report $errorMethod() to be true");
    }

    public function deviceErrorCodes()
    {
        return [
            'didDeviceReportABadRequest' => [
                \Surfnet\StepupU2fBundle\Dto\RegisterResponse::ERROR_CODE_BAD_REQUEST,
                'didDeviceReportABadRequest',
            ],
            'wasClientConfigurationUnsupported' => [
                \Surfnet\StepupU2fBundle\Dto\RegisterResponse::ERROR_CODE_CONFIGURATION_UNSUPPORTED,
                'wasClientConfigurationUnsupported',
            ],
            'wasDeviceAlreadyRegistered' => [
                \Surfnet\StepupU2fBundle\Dto\RegisterResponse::ERROR_CODE_DEVICE_INELIGIBLE,
                'wasDeviceAlreadyRegistered',
            ],
            'didDeviceTimeOut' => [
                \Surfnet\StepupU2fBundle\Dto\RegisterResponse::ERROR_CODE_TIMEOUT,
                'didDeviceTimeOut',
            ],
            'didDeviceReportAnUnknownError' => [
                \Surfnet\StepupU2fBundle\Dto\RegisterResponse::ERROR_CODE_OTHER_ERROR,
                'didDeviceReportAnUnknownError',
            ],
        ];
    }
}
