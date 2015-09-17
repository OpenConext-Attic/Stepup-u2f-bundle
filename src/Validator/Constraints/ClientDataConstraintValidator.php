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

final class ClientDataConstraintValidator extends ConstraintValidator
{
    const STRICT = true;

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ClientDataConstraint) {
            throw new LogicException('ClientDataConstraintValidator can only validate ClientDataConstraints');
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

        $clientData = json_decode($decoded);
        $jsonLastError = json_last_error();

        if ($jsonLastError) {
            $jsonErrorReason = $this->getJsonErrorMessage($jsonLastError);
            $this->context->addViolation($constraint->message, ['%reason%' => $jsonErrorReason], $value);
            return;
        }

        if (!isset($clientData->challenge)) {
            $this->context->addViolation(
                $constraint->message,
                ['%reason%' => 'Challenge not present on client data'],
                $value
            );
            return;
        }
    }

    /**
     * @param int $errorCode
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getJsonErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case JSON_ERROR_NONE:
                return 'JSON_ERROR_NONE';
            case JSON_ERROR_DEPTH:
                return 'JSON_ERROR_DEPTH';
            case JSON_ERROR_STATE_MISMATCH:
                return 'JSON_ERROR_STATE_MISMATCH';
            case JSON_ERROR_CTRL_CHAR:
                return 'JSON_ERROR_CTRL_CHAR';
            case JSON_ERROR_SYNTAX:
                return 'JSON_ERROR_SYNTAX';
            case JSON_ERROR_UTF8:
                return 'JSON_ERROR_UTF8';
            case constant('JSON_ERROR_RECURSION'):
                return 'JSON_ERROR_RECURSION';
            case constant('JSON_ERROR_INF_OR_NAN'):
                return 'JSON_ERROR_INF_OR_NAN';
            case constant('JSON_ERROR_UNSUPPORTED_TYPE'):
                return 'JSON_ERROR_UNSUPPORTED_TYPE';
            default:
                throw new LogicException(sprintf('Unknown JSON decoding error code %d encountered', $errorCode));
        }
    }
}
