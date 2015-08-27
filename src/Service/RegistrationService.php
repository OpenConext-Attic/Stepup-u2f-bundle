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
use Surfnet\StepupU2fBundle\Exception\LogicException;
use Surfnet\StepupU2fBundle\Exception\Registration\ClientRegistrationException;
use Surfnet\StepupU2fBundle\Exception\Registration\PublicKeyDecodingRegistrationException;
use Surfnet\StepupU2fBundle\Exception\Registration\RegistrationException;
use Surfnet\StepupU2fBundle\Exception\Registration\ResponseNotSignedByDeviceException;
use Surfnet\StepupU2fBundle\Exception\Registration\UnmatchedRegistrationChallengeException;
use Surfnet\StepupU2fBundle\Exception\Registration\UntrustedDeviceException;
use u2flib_server\Error;
use u2flib_server\RegisterRequest as YubicoRegisterRequest;
use u2flib_server\U2F;

final class RegistrationService
{
    /**
     * @var U2F
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
            throw $this->mapU2fErrorToRegistrationException($error);
        }

        $registration = new Registration();
        $registration->keyHandle = $yubicoRegistration->keyHandle;
        $registration->publicKey = $yubicoRegistration->publicKey;

        return $registration;
    }

    /**
     * @param Error $error
     * @return RegistrationException|LogicException
     */
    private function mapU2fErrorToRegistrationException(Error $error)
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
