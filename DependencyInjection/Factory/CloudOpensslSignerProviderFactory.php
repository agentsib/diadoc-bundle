<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\DiadocBundle\DependencyInjection\Factory;


use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class CloudOpensslSignerProviderFactory implements SignerProviderFactoryInterface
{
    public function create(ContainerBuilder $container, $sourceName, $config)
    {
        $definition = new DefinitionDecorator('agentsib_diadoc.signer_provider.prototype.cloud_openssl');
        $definition->replaceArgument(0, $config['url']);
        $definition->replaceArgument(1, $config['token']);
        $definition->replaceArgument(2, $config['curl_options']);

        $definition->addTag('agentsib_diadoc.signer', array(
            'name'  =>  $sourceName
        ));

        $serviceId = 'agentsib_diadoc.signer.' . $sourceName;

        $container->setDefinition($serviceId, $definition);

        return $serviceId;
    }


    public function getName()
    {
        return 'cloud_openssl';
    }

    public function addConfiguration(NodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('url')->info('URL to cloud service')->isRequired()->end()
                ->scalarNode('token')->info('Token for claud service')->defaultNull()->end()
                ->arrayNode('curl_options')->canBeUnset()->prototype('scalar')->end()->end()
            ->end();
    }
}