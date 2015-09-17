<?php

/**
 * Copyright 2015 SURFnet bv
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

namespace Surfnet\StepupU2fBundle\Validator\Constraints;

use Surfnet\StepupU2fBundle\Exception\LogicException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @see https://fidoalliance.org/specs/fido-u2f-v1.0-nfc-bt-amendment-20150514/fido-u2f-raw-message-formats.html#registration-response-message-success
 */
final class RegistrationDataConstraintValidator extends ConstraintValidator
{
    const RESERVED_BYTE_OFFSET = 1;
    const STRICT = true;

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof RegistrationDataConstraint) {
            throw new LogicException(
                'RegistrationDataConstraintValidator can only validate RegistrationDataConstraints'
            );
        }

        if (!is_string($value)) {
            $this->context->addViolation($constraint->message, ['%reason%' => 'Not a string'], $value);
            return;
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), self::STRICT);

        if ($decoded === false) {
            $this->context->addViolation($constraint->message, ['%reason%' => 'Not base64 decodable'], $value);
            return;
        }

        // Unpack into an array of unsigned characters (8-bits each, ie. bytes).
        $bytes = array_values(unpack('C*', $decoded));

        // We navigate over the characters using an offset, determining the string length we expect.
        // The registration data starts with a reserved byte (0x05).
        $offset = self::RESERVED_BYTE_OFFSET;
        // We then expect the public key. First, load the PUBKEY_LEN constant by auto-loading \u2flib_serverf\U2F.
        class_exists('u2flib_server\U2F');
        $offset += \u2flib_server\PUBKEY_LEN;

        if (!isset($bytes[$offset])) {
            $this->addNotEnoughBytesViolation($constraint, $value);
            return;
        }

        // We then expect the key handle length and key handle itself.
        $offset += 1 + $bytes[$offset];

        if (!isset($bytes[$offset + 3])) {
            $this->addNotEnoughBytesViolation($constraint, $value);
            return;
        }

        // We then skip two bytes and expect two bytes that form a number that specifies the public key length.
        $certificateLength = 4 + ($bytes[$offset + 2] << 8) + $bytes[$offset + 3];
        $minimumLength = $offset + $certificateLength;

        // The calculated minimum length excludes the signature, thus if the data length equals the minimum length, the
        // signature is missing.
        if (count($bytes) <= $minimumLength) {
            $this->addNotEnoughBytesViolation($constraint, $value);
            return;
        }
    }

    /**
     * @param RegistrationDataConstraint $constraint
     * @param                            $value
     */
    private function addNotEnoughBytesViolation(RegistrationDataConstraint $constraint, $value)
    {
        $this->context->addViolation($constraint->message, ['%reason%' => 'Not enough bytes'], $value);
    }
}
