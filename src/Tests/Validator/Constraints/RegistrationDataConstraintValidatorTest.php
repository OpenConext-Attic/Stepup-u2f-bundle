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
use Surfnet\StepupU2fBundle\Validator\Constraints\RegistrationDataConstraint;
use Surfnet\StepupU2fBundle\Validator\Constraints\RegistrationDataConstraintValidator;

final class RegistrationDataConstraintValidatorTest extends TestCase
{
    /**
     * @test
     * @group validation
     * @dataProvider validRegistrationData
     *
     * @param string $registrationData
     */
    public function it_accepts_valid_registration_data($registrationData)
    {
        $constraint = new RegistrationDataConstraint();

        $executionContext = m::mock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $executionContext->shouldReceive('addViolation')->never();

        $validator = new RegistrationDataConstraintValidator();
        $validator->initialize($executionContext);
        $validator->validate($registrationData, $constraint);
    }

    public function validRegistrationData()
    {
        return [
            ['BQQsJupwZXlYgDwNvctsqaVG4e5-zyUYSI8eqwJaK9cURtyB1jtLcFunvRI82GJ6JujEFhUGdvKuO7iHINX9SBpaQGVWCUz7hr2jcAZENw9LO5pJCx98ilsLSgF_I99_qq36TeXjq4tt7N29V7bnsPyWdmG6kY7rADYLqGyaHMvcYzswggIaMIIBBKADAgECAgQuQunNMAsGCSqGSIb3DQEBCzAuMSwwKgYDVQQDEyNZdWJpY28gVTJGIFJvb3QgQ0EgU2VyaWFsIDQ1NzIwMDYzMTAgFw0xNDA4MDEwMDAwMDBaGA8yMDUwMDkwNDAwMDAwMFowKTEnMCUGA1UEAwweWXViaWNvIFUyRiBFRSBTZXJpYWwgNzc2MTM3MTY1MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAESbo91Jw7oVvVuHWN79tJLiqMPj9wAsRNXdSDP5_AzkCdkTdK8FF68gBqujnC-3MbNnGgzlzp2sGEtWGVuXDNTKMSMBAwDgYKKwYBBAGCxAoBAwQAMAsGCSqGSIb3DQEBCwOCAQEDdg42lWXviOkEKATYj_pfvVuZk6MSRo-xdJYSlqU5zGWwYv0AfZmKqqlMymsD-atC0hC2VSV-2LfV-0cUbMQVkXST1f0RqwIglE6eu6ai_DhKcEbYJb3WECKgnAu4BSPnn1lkOdsL-U9VivlM4VHbel1I7qN_KJS_ZhcdDhQC9U5EZhRdShpwAd1lCpIMt-RtlvFFIh5xwlcO_8G7ekx0rm9fCbZcOoQDUpTi6UjlYyFQpRzrfAF1C1kuHxEg-2tpXdJqnmVXtxP1SN-u21LfFf2JUmkkfTu0rBCi2Qi2BiBcShTUGyni33XEcm0bdJ45d1P5csCf4N3tmUWm1Fj-2DBFAiEAmiCkrBAXbDiEqS15ZEThFF9R7YgCdb-ZlorxkIQ_ugkCID7KeptW-cPJrk94ct8Mm18KP7BHha6kI2fMd45VujZI'],
        ];
    }

    /**
     * @test
     * @group validation
     * @dataProvider invalidRegistrationData
     *
     * @param mixed $registrationData
     */
    public function it_rejects_invalid_registration_data($registrationData)
    {
        $constraint = new RegistrationDataConstraint();

        $executionContext = m::mock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $executionContext->shouldReceive('addViolation')->atLeast()->once();

        $validator = new RegistrationDataConstraintValidator();
        $validator->initialize($executionContext);
        $validator->validate($registrationData, $constraint);
    }

    public function invalidRegistrationData()
    {
        return [
            'empty string'   => [''],
            'garbage string' => ['Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338Garbage38338'],
            'too short'      => ['Garbage38338'],
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
