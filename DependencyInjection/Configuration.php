<?php

namespace MGDSoft\FixturesGeneratorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();

        $tb
            ->root('mgdsoft_fixtures_creator')
                ->children()
                    ->scalarNode('entity_path_default')->defaultValue("App\\Entity")->end()
                    ->scalarNode('template')->defaultValue(__DIR__ .'/../Generator/templates/basic.tpl')->end()
                    ->scalarNode('template_lib')->defaultValue(__DIR__ .'/../Generator/templates/lib.tpl')->end()
                    ->scalarNode('fixture_path_default')->defaultValue('%kernel.root_dir%/DataFixtures/ORM')->end()
                    ->scalarNode('php_cs_fixer')->defaultValue('php-cs-fixer')->end()

                    ->arrayNode('test')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('template')->defaultValue(__DIR__ .'/../Generator/templates/basic_test.tpl')->end()
                            ->booleanNode('enabled')->defaultValue(true)->end()
                            ->scalarNode('fixture_path_default')->defaultValue('%kernel.root_dir%/../tests/Fixtures/General')->end()
                        ->end()
                    ->end()

                ->end()
            ->end()
        ;

        return $tb;
    }

}
