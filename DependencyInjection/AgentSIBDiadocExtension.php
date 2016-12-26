<?php

namespace AgentSIB\DiadocBundle\DependencyInjection;

use AgentSIB\DiadocBundle\DependencyInjection\Factory\SignerProviderFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AgentSIBDiadocExtension extends Extension
{
    /**
     * @var SignerProviderFactoryInterface[]
     */
    private $signerProvidersFactory = [];

    public function addSignerProviderFactory(SignerProviderFactoryInterface $factory)
    {
        $this->signerProvidersFactory[$factory->getName()] = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->loadDiadocConnections($config['connections'], $container);

        if (!isset($config['default_connection'])) {
            $config['default_connection'] = key($config['connections']);
        }

        $defaultConnectionId = sprintf('agentsib_diadoc.connection.%s', $config['default_connection']);

        if (!$container->hasDefinition($defaultConnectionId)) {
            throw new ServiceNotFoundException($defaultConnectionId);
        }

        $container->setAlias('agentsib_diadoc.connection', $defaultConnectionId);

    }

    public function loadDiadocConnections(array $config, ContainerBuilder $container)
    {
        foreach ($config as $connectionName => $connectionConfig) {
            $serviceId = sprintf('agentsib_diadoc.connection.%s', $connectionName);

            $connectionDefinition = new DefinitionDecorator('agentsib_diadoc.connection.prototype');
            $connectionDefinition->replaceArgument(0, $connectionConfig['ddauth']);

            if (isset($connectionConfig['signer_service'])) {
                $connectionDefinition->replaceArgument(1, new Reference($connectionConfig['signer_service']));
            } else {
                $signerService = $this->loadSignerProvider($connectionName, $connectionConfig['signer'], $container);
                $connectionDefinition->replaceArgument(1, new Reference($signerService));
            }

            if (isset($connectionConfig['login'])) {
                $connectionDefinition->addMethodCall('authenticateLogin', [$connectionConfig['login'], $connectionConfig['password']]);
            } else {
                $connectionDefinition->addMethodCall('authenticateCertificate', [$connectionConfig['certificate']]);
            }

            $container->setDefinition($serviceId, $connectionDefinition);
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($this->signerProvidersFactory);
    }

    public function loadSignerProvider($connectionName, array $config, ContainerBuilder $container)
    {
        $factoryName = key($config);
        $factoryConfig = $config[$factoryName];
        $factory = $this->signerProvidersFactory[$factoryName];

        return $factory->create($container, $connectionName, $factoryConfig);
    }


    public function getAlias()
    {
        return 'agentsib_diadoc';
    }


}
