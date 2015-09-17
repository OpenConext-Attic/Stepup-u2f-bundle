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

final class SignatureDataConstraintValidator extends ConstraintValidator
{
    const STRICT = true;

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof SignatureDataConstraint) {
            throw new LogicException('SignatureDataConstraintValidator can only validate SignatureDataConstraints');
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

        // See yubico/u2flib-server/src/u2flib_server/U2F.php:290
        if (strlen($decoded) < 6) {
            $this->context->addViolation($constraint->message, ['%reason%' => 'Too short'], $value);
            return;
        }
    }
}
