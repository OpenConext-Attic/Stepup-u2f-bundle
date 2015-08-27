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

namespace Surfnet\StepupU2fBundle\Service;

use Surfnet\StepupU2fBundle\Dto\RegisterRequest;
use Surfnet\StepupU2fBundle\Dto\RegisterResponse;
use Surfnet\StepupU2fBundle\Dto\Registration;
use Surfnet\StepupU2fBundle\Exception\Registration\ClientRegistrationException;
use Surfnet\StepupU2fBundle\Exception\Registration\PublicKeyDecodingRegistrationException;
use Surfnet\StepupU2fBundle\Exception\Registration\ResponseNotSignedByDeviceException;
use Surfnet\StepupU2fBundle\Exception\Registration\UnmatchedRegistrationChallengeException;
use Surfnet\StepupU2fBundle\Exception\Registration\UntrustedDeviceException;
use Surfnet\StepupU2fBundle\Yubico\U2f\ErrorHelper;
use u2flib_server\Error;
use u2flib_server\RegisterRequest as YubicoRegisterRequest;
use u2flib_server\U2F;

final class RegistrationService
{
    /**
     * @var \u2flib_server\U2F
     */
    private $u2fService;

    public function __construct(U2F $u2fService)
    {
        $this->u2fService = $u2fService;
    }

    /**
     * @return RegisterRequest
     */
    public function requestRegistration()
    {
        /** @var YubicoRegisterRequest $yubicoRequest */
        list($yubicoRequest) = $this->u2fService->getRegisterData();

        $request = new RegisterRequest();
        $request->version   = $yubicoRequest->version;
        $request->challenge = $yubicoRequest->challenge;
        $request->appId     = $yubicoRequest->appId;

        return $request;
    }

    /**
     * @param RegisterRequest  $request The register request that you requested earlier and was used to query the U2F
     *     device.
     * @param RegisterResponse $response The response that the U2F device gave in response to the register request.
     * @return Registration
     * @throws ClientRegistrationException
     * @throws UnmatchedRegistrationChallengeException
     * @throws PublicKeyDecodingRegistrationException
     * @throws UntrustedDeviceException
     * @throws ResponseNotSignedByDeviceException
     */
    public function verifyRegistration(RegisterRequest $request, RegisterResponse $response)
    {
        $yubicoRequest = new YubicoRegisterRequest($request->challenge, $request->appId);

        try {
            $yubicoRegistration = $this->u2fService->doRegister($yubicoRequest, $response, false);
        } catch (Error $error) {
            throw ErrorHelper::convertToRegistrationException($error);
        }

        $registration = new Registration();
        $registration->keyHandle = $yubicoRegistration->keyHandle;
        $registration->publicKey = $yubicoRegistration->publicKey;

        return $registration;
    }
}
