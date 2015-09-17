<?php

/**
 * Copyright 2015 SURFnet B.V.
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

namespace Surfnet\StepupU2fBundle\Tests\Validator\Constraints;

use Mockery as m;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupU2fBundle\Validator\Constraints\SignatureDataConstraint;
use Surfnet\StepupU2fBundle\Validator\Constraints\SignatureDataConstraintValidator;

final class SignatureDataConstraintValidatorTest extends TestCase
{
    /**
     * @test
     * @group validation
     * @dataProvider validSignatureData
     *
     * @param string $signatureData
     */
    public function it_accepts_valid_signature_data($signatureData)
    {
        $constraint = new SignatureDataConstraint();

        $executionContext = m::mock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $executionContext->shouldReceive('addViolation')->never();

        $validator = new SignatureDataConstraintValidator();
        $validator->initialize($executionContext);
        $validator->validate($signatureData, $constraint);
    }

    public function validSignatureData()
    {
        return [
            'Real-life data' => ['AQAAABQwRQIgGqVZuSx6ulGFl6L63oDLF3OTq0EiCHJNadAHixwTZDkCIQCDarpMiGQA6TFCYj2bslbjyIYg3nSHPCo21YIQOlb09Q'],
            'Long enough Base64 string' => ['BQQsJupwZXlYgDwNvctsqaVG4e5-zyUYSI8eqwJaK9'],
        ];
    }

    /**
     * @test
     * @group validation
     * @dataProvider invalidSignatureData
     *
     * @param mixed $signatureData
     */
    public function it_rejects_invalid_signature_data($signatureData)
    {
        $constraint = new SignatureDataConstraint();

        $executionContext = m::mock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $executionContext->shouldReceive('addViolation')->atLeast()->once();

        $validator = new SignatureDataConstraintValidator();
        $validator->initialize($executionContext);
        $validator->validate($signatureData, $constraint);
    }

    public function invalidSignatureData()
    {
        return [
            'empty string'   => [''],
            'garbage string' => ['G##arbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338'],
            'too short'      => ['BQQ'],
            'int (0)'        => [0],
            'int (1)'        => [1],
            'float'          => [1.1],
            'resource'       => [fopen('php://memory', 'w')],
            'object'         => [new \stdClass],
            'array'          => [array()],
            'bool'           => [false],
            'null'           => [null],
        ];
    }
}
