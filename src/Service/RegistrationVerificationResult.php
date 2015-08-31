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
use Surfnet\StepupU2fBundle\Exception\LogicException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final class RegistrationVerificationResult
{
    /**
     * Registration was a success.
     */
    const STATUS_SUCCESS = 0;

    /**
     * The response challenge did not match the request challenge.
     */
    const STATUS_UNMATCHED_REGISTRATION_CHALLENGE = 1;

    /**
     * The response was signed by another party than the device, indicating it was tampered with.
     */
    const STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE = 2;

    /**
     * The device has not been manufactured by a trusted party.
     */
    const STATUS_UNTRUSTED_DEVICE = 3;

    /**
     * The decoding of the device's public key failed.
     */
    const STATUS_PUBLIC_KEY_DECODING_FAILED = 4;

    /**
     * @var int
     */
    private $status;

    /**
     * @var Registration|null
     */
    private $registration;

    /**
     * @param Registration $registration
     * @return RegistrationVerificationResult
     */
    public static function success(Registration $registration)
    {
        $result = new self(self::STATUS_SUCCESS);
        $result->registration = $registration;

        return $result;
    }

    /**
     * @return RegistrationVerificationResult
     */
    public static function responseChallengeDidNotMatchRequestChallenge()
    {
        return new self(self::STATUS_UNMATCHED_REGISTRATION_CHALLENGE);
    }

    /**
     * @return RegistrationVerificationResult
     */
    public static function responseWasNotSignedByDevice()
    {
        return new self(self::STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE);
    }

    /**
     * @return RegistrationVerificationResult
     */
    public static function deviceCannotBeTrusted()
    {
        return new self(self::STATUS_UNTRUSTED_DEVICE);
    }

    /**
     * @return RegistrationVerificationResult
     */
    public static function publicKeyDecodingFailed()
    {
        return new self(self::STATUS_PUBLIC_KEY_DECODING_FAILED);
    }

    /**
     * @param int $status
     */
    private function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function wasSuccessful()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * @return Registration|null
     */
    public function getRegistration()
    {
        if (!$this->wasSuccessful()) {
            throw new LogicException('The registration was unsuccessful and the registration data is not available');
        }

        return $this->registration;
    }

    /**
     * @return bool
     */
    public function didResponseChallengeNotMatchRequestChallenge()
    {
        return $this->status === self::STATUS_UNMATCHED_REGISTRATION_CHALLENGE;
    }

    /**
     * @return bool
     */
    public function wasResponseNotSignedByDevice()
    {
        return $this->status === self::STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE;
    }

    /**
     * @return bool
     */
    public function canDeviceNotBeTrusted()
    {
        return $this->status === self::STATUS_UNTRUSTED_DEVICE;
    }

    /**
     * @return bool
     */
    public function didPublicKeyDecodingFail()
    {
        return $this->status === self::STATUS_PUBLIC_KEY_DECODING_FAILED;
    }
}
