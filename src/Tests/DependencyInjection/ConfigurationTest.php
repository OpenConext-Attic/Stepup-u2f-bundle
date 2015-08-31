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

namespace Surfnet\StepupU2fBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupU2fBundle\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /**
     * @test
     * @group bundle
     * @dataProvider validAppIds
     *
     * @param string $appId
     */
    public function it_accepts_a_valid_app_id($appId)
    {
        $this->assertConfigurationIsValid([['app_id' => $appId]]);
    }

    public function validAppIds()
    {
        return [
            'AppID without path' => ['https://gateway.surfconext.invalid'],
            'AppID with root path' => ['https://gateway.surfconext.invalid/'],
            'AppID with path' => ['https://gateway.surfconext.invalid/u2f-app-id'],
        ];
    }

    /**
     * @test
     * @group bundle
     * @dataProvider invalidAppIds
     *
     * @param mixed $appId
     * @param string $partOfExpectedMessage
     */
    public function it_accepts_a_invalid_app_id($appId, $partOfExpectedMessage)
    {
        $this->assertConfigurationIsInvalid([['app_id' => $appId]], $partOfExpectedMessage);
    }

    public function invalidAppIds()
    {
        return [
            'AppID over HTTP' => ['http://gateway.surfconext.invalid', 'HTTPS URL'],
            'AppID over FTP' => ['ftp://gateway.surfconext.invalid', 'HTTPS URL'],
            'integer' => [1, 'HTTPS URL'],
            'null' => [null, 'HTTPS URL'],
            'empty string' => ['', 'HTTPS URL'],
            'object' => [new \stdClass, 'Expected scalar'],
            'array' => [array(), 'Expected scalar'],
        ];
    }

    protected function getConfiguration()
    {
        return new Configuration();
    }
}
