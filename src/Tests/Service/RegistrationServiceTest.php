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
use Surfnet\StepupU2fBundle\Service\RegistrationService;

final class RegistrationServiceTest extends TestCase
{
    const APP_ID = 'https://gateway.surfconext.nl/u2f/app-id';

    /**
     * @test
     * @group registration
     */
    public function it_can_request_registration_of_a_u2f_device()
    {
        $yubicoRequest = new \u2flib_server\RegisterRequest('challenge', self::APP_ID);

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('getRegisterData')->once()->with()->andReturn([$yubicoRequest, []]);

        $service = new RegistrationService($u2f);

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

        $expectedRegistration = new \Surfnet\StepupU2fBundle\Dto\Registration();
        $expectedRegistration->publicKey = $publicId;
        $expectedRegistration->keyHandle = $keyHandle;

        $u2f = m::mock('u2flib_server\U2F');
        $u2f->shouldReceive('doRegister')
            ->once()
            ->with(m::anyOf($yubicoRequest), m::anyOf($response))
            ->andReturn($yubicoRegistration);

        $service = new RegistrationService($u2f);

        $this->assertEquals($expectedRegistration, $service->verifyRegistration($request, $response));
    }
}
