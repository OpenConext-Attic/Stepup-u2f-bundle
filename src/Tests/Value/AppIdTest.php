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

namespace Surfnet\StepupU2fBundle\Tests\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupU2fBundle\Value\AppId;

final class AppIdTest extends TestCase
{
    /**
     * @test
     * @group value
     * @dataProvider validAppIds
     *
     * @param string $appId
     */
    public function it_accepts_valid_app_ids($appId)
    {
        new AppId($appId);
    }

    public function validAppIds()
    {
        return [
            'HTTPS, no path'        => ['https://domain.invalid'],
            'HTTPS, root path'      => ['https://domain.invalid/'],
            'HTTPS, path'           => ['https://domain.invalid/bleep/blorp/black-holes'],
            'HTTPS with port, path' => ['https://domain.invalid:10609/bleep/blorp/black-holes'],
        ];
    }

    /**
     * @test
     * @group value
     * @dataProvider invalidAppIds
     * @expectedException \Surfnet\StepupU2fBundle\Exception\InvalidArgumentException
     *
     * @param mixed $invalidAppId
     */
    public function it_rejects_invalid_app_ids($invalidAppId)
    {
        new AppId($invalidAppId);
    }

    public function invalidAppIds()
    {
        return [
            'whitespace preceding URL' => [' https://domain.invalid'],
            'whitespace after URL'     => ['https://domain.invalid '],
            'whitespace around URL'    => [' https://domain.invalid '],
            'HTTP URL'                 => ['http://domain.invalid'],
            'file URL'                 => ['file:///etc/hosts'],
            'int'                      => [1],
            'float'                    => [1.1],
            'resource'                 => [fopen('php://memory', 'w')],
            'object'                   => [new \stdClass],
            'array'                    => [array()],
            'bool'                     => [false],
        ];
    }
}
