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

namespace Surfnet\StepupU2fBundle\Dto;

use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;

final class SignRequest implements JsonSerializable
{
    /**
     * @Assert\NotBlank(message="Sign request version may not be empty")
     * @Assert\Type("string", message="Sign request version must be a string")
     *
     * @var string
     */
    public $version;

    /**
     * @Assert\NotBlank(message="Sign request challenge may not be empty")
     * @Assert\Type("string", message="Sign request challenge must be a string")
     *
     * @var string
     */
    public $challenge;

    /**
     * @Assert\NotBlank(message="Sign request AppID may not be empty")
     * @Assert\Type("string", message="Sign request AppID must be a string")
     *
     * @var string
     */
    public $appId;

    /**
     * @Assert\NotBlank(message="Sign request key handle may not be empty")
     * @Assert\Type("string", message="Sign request key handle must be a string")
     *
     * @var string
     */
    public $keyHandle;

    public function jsonSerialize()
    {
        return [
            'version'   => $this->version,
            'challenge' => $this->challenge,
            'appId'     => $this->appId,
            'keyHandle' => $this->keyHandle,
        ];
    }
}
