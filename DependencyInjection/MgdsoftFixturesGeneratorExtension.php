<?php


namespace MGDSoft\FixturesGeneratorBundle\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class MgdsoftFixturesGeneratorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(
            $this->getConfiguration($configs, $container),
            $configs
        );

        $container->setParameter('mgdsoft.fixtures_generator.template_lib', $config['template_lib']);

        $container->setParameter('mgdsoft.fixtures_generator.entity_path_default', $config['entity_path_default']);
        $container->setParameter('mgdsoft.fixtures_generator.template', $config['template']);
        $container->setParameter('mgdsoft.fixtures_generator.fixture_path_default', $config['fixture_path_default']);
        $container->setParameter('mgdsoft.fixtures_generator.php_cs_fixer', $config['php_cs_fixer']);

        $container->setParameter('mgdsoft.fixtures_generator.test.enabled', $config['test']['enabled']);
        $container->setParameter('mgdsoft.fixtures_generator.test.template', $config['test']['template']);
        $container->setParameter('mgdsoft.fixtures_generator.test.fixture_path_default', $config['test']['fixture_path_default']);

        $loader = new YamlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config/')));
        $loader->load('config.yml');
    }

}