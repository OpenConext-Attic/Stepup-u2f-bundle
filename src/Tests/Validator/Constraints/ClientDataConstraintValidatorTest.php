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
use Surfnet\StepupU2fBundle\Validator\Constraints\ClientDataConstraint;
use Surfnet\StepupU2fBundle\Validator\Constraints\ClientDataConstraintValidator;

final class ClientDataConstraintValidatorTest extends TestCase
{
    /**
     * @test
     * @group validation
     * @dataProvider validClientData
     *
     * @param string $registrationData
     */
    public function it_accepts_valid_client_data($registrationData)
    {
        $constraint = new ClientDataConstraint();

        $executionContext = m::mock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $executionContext->shouldReceive('addViolation')->never();

        $validator = new ClientDataConstraintValidator();
        $validator->initialize($executionContext);
        $validator->validate($registrationData, $constraint);
    }

    public function validClientData()
    {
        return [
            'Real-life client data' => ['eyJ0eXAiOiJuYXZpZ2F0b3IuaWQuZmluaXNoRW5yb2xsbWVudCIsImNoYWxsZW5nZSI6IlplTVg3Z2V2SUNpOTBLSW5VanFRLVEiLCJvcmlnaW4iOiJodHRwczovL3NzLnUyZi5kZXY6NzAwMCIsImNpZF9wdWJrZXkiOiIifQ'],
            'Constructed client data' => [strtr(base64_encode(json_encode(['challenge' => 'test'])), '+/', '-_')],
        ];
    }

    /**
     * @test
     * @group validation
     * @dataProvider invalidClientData
     *
     * @param mixed $registrationData
     */
    public function it_rejects_invalid_client_data($registrationData)
    {
        $constraint = new ClientDataConstraint();

        $executionContext = m::mock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $executionContext->shouldReceive('addViolation')->atLeast()->once();

        $validator = new ClientDataConstraintValidator();
        $validator->initialize($executionContext);
        $validator->validate($registrationData, $constraint);
    }

    public function invalidClientData()
    {
        return [
            'empty string' => [''],
            'Corrupted real-life client data' => ['%$#E@#@eyJ0eXAiOiJuYXZpZ2F0b3IuaWQuZmluaXNoRW5yb2xsbWVudCIsImNoYWxsZW5nZSI6IlplTVg3Z2V2SUNpOTBLSW5VanFRLVEiLCJvcmlnaW4iOiJodHRwczovL3NzLnUyZi5kZXY6NzAwMCIsImNpZF9wdWJrZXkiOiIifQ'],
            'No challenge' => [strtr(base64_encode(json_encode(['missing' => 'challenge'])), '+/', '-_')],
            'int (0)'      => [0],
            'int (1)'      => [1],
            'float'        => [1.1],
            'resource'     => [fopen('php://memory', 'w')],
            'object'       => [new \stdClass],
            'array'        => [array()],
            'bool'         => [false],
            'null'         => [null],
        ];
    }
}
