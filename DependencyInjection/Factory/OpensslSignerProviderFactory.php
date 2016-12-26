<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\DiadocBundle\DependencyInjection\Factory;


use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class OpensslSignerProviderFactory implements SignerProviderFactoryInterface
{
    public function create(ContainerBuilder $container, $sourceName, $config)
    {
        $definition = new DefinitionDecorator('agentsib_diadoc.signer_provider.prototype.openssl');
        $definition->replaceArgument(0, $config['ca']);
        $definition->replaceArgument(1, $config['certificate']);
        $definition->replaceArgument(2, $config['private_key']);
        $definition->replaceArgument(3, $config['bin']);

        $definition->addTag('agentsib_diadoc.signer', array(
            'name'  =>  $sourceName
        ));

        $serviceId = 'agentsib_diadoc.signer.' . $sourceName;

        $container->setDefinition($serviceId, $definition);

        return $serviceId;
    }


    public function getName()
    {
        return 'openssl';
    }

    public function addConfiguration(NodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('private_key')->info('Path to private key file in PEM format')->isRequired()->end()
                ->scalarNode('ca')->info('Path to CA file in PEM format')->isRequired()->end()
                ->scalarNode('certificate')->info('Path to certificate file in PEM format')->isRequired()->end()
                ->scalarNode('bin')->defaultValue('/use/bin/openssl')->end()
            ->end();
    }

}