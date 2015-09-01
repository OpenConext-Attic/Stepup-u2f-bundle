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

use Surfnet\StepupU2fBundle\Dto\Registration;
use Surfnet\StepupU2fBundle\Dto\SignRequest;
use Surfnet\StepupU2fBundle\Dto\SignResponse;
use Surfnet\StepupU2fBundle\Exception\LogicException;
use u2flib_server\Error;
use u2flib_server\Registration as YubicoRegistration;
use u2flib_server\SignRequest as YubicoSignRequest;
use u2flib_server\U2F;

final class AuthenticationVerificationService
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
     * @param Registration $registration
     * @return SignRequest
     */
    public function requestAuthentication(Registration $registration)
    {
        $yubicoRegistration = new YubicoRegistration();
        $yubicoRegistration->keyHandle = $registration->keyHandle;
        $yubicoRegistration->publicKey = $registration->publicKey;

        /** @var YubicoSignRequest $yubicoRequest */
        list($yubicoRequest) = $this->u2fService->getAuthenticateData([$yubicoRegistration]);

        $request = new SignRequest();
        $request->version   = $yubicoRequest->version;
        $request->challenge = $yubicoRequest->challenge;
        $request->appId     = $yubicoRequest->appId;
        $request->keyHandle = $yubicoRequest->keyHandle;

        return $request;
    }

    /**
     * Request signing of a sign request. Does not support U2F's sign counter system.
     *
     * @param Registration $registration The registration that is to be signed.
     * @param SignRequest  $request The sign request that you requested earlier and was used to query the U2F device.
     * @param SignResponse $response The response that the U2F device gave in response to the sign request.
     * @return AuthenticationVerificationResult
     */
    public function verifyAuthentication(Registration $registration, SignRequest $request, SignResponse $response)
    {
        $yubicoRegistration = new YubicoRegistration();
        $yubicoRegistration->keyHandle = $registration->keyHandle;
        $yubicoRegistration->publicKey = $registration->publicKey;

        $yubicoRequest = new YubicoSignRequest();
        $yubicoRequest->version   = $request->version;
        $yubicoRequest->challenge = $request->challenge;
        $yubicoRequest->appId     = $request->appId;
        $yubicoRequest->keyHandle = $request->keyHandle;

        try {
            $this->u2fService->doAuthenticate([$yubicoRequest], [$yubicoRegistration], $response);
        } catch (Error $error) {
            switch ($error->getCode()) {
                case \u2flib_server\ERR_NO_MATCHING_REQUEST:
                    return AuthenticationVerificationResult::requestResponseMismatch();
                case \u2flib_server\ERR_NO_MATCHING_REGISTRATION:
                    return AuthenticationVerificationResult::responseRegistrationMismatch();
                case \u2flib_server\ERR_PUBKEY_DECODE:
                    return AuthenticationVerificationResult::publicKeyDecodingFailed();
                case \u2flib_server\ERR_AUTHENTICATION_FAILURE:
                    return AuthenticationVerificationResult::responseWasNotSignedByDevice();
                default:
                    throw new LogicException(
                        sprintf(
                            'The Yubico U2F service threw an exception with error code %d that should not occur ("%s")',
                            $error->getCode(),
                            $error->getMessage()
                        ),
                        $error
                    );
            }
        }

        return AuthenticationVerificationResult::success();
    }
}
