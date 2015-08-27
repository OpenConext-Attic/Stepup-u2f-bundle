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

namespace Surfnet\StepupU2fBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @codeCoverageIgnore
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder;

        $rootNode = $treeBuilder->root('surfnet_stepup_u2f');
        $rootNode
            ->children()
                ->scalarNode('app_id')
                    ->info(
                        'This is the URL that identifies this logical application and from where the Trusted Facets ' .
                        'List is served'
                    )
                    ->isRequired()
                    ->validate()
                        ->ifTrue(
                            function ($appId) {
                                return !is_string($appId) || parse_url($appId, PHP_URL_SCHEME) !== 'https';
                            }
                        )
                        ->thenInvalid('surfnet_stepup_u2f.app_id must be a HTTPS URL')
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
