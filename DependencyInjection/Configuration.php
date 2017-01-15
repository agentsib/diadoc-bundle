<?php

namespace AgentSIB\DiadocBundle\DependencyInjection;

use AgentSIB\DiadocBundle\DependencyInjection\Factory\SignerProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    private $signerProvidersFactory = [];

    /**
     * Configuration constructor.
     * @param SignerProviderFactoryInterface[] $signerProvidersFactory
     */
    public function __construct(array $signerProvidersFactory)
    {
        $this->signerProvidersFactory = $signerProvidersFactory;
    }


    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('agentsib_diadoc');

        $connectionsPrototypeNode = $rootNode
            ->fixXmlConfig('connection')
            ->beforeNormalization()
                ->ifTrue(function ($v) { return is_array($v) && !array_key_exists('connections', $v) && array_key_exists('connection', $v); })
                ->then(function($v) {
                    $connection = $v['connection'];
                    $v['default_connection'] = isset($v['default_connection']) ? (string) $v['default_connection'] : 'default';
                    $v['connections'] = array($v['default_connection'] => $connection);
                    return $v;
                })
            ->end()
            ->children()
                ->arrayNode('connections')
                ->useAttributeAsKey('name', true)
                ->canBeUnset()
                ->prototype('array');

        $boxesPrototypeNode = $rootNode
            ->fixXmlConfig('box', 'boxes')
            ->beforeNormalization()
                ->ifTrue(function ($v) { return is_array($v) && !array_key_exists('boxes', $v) && array_key_exists('box', $v); })
                ->then(function($v) {
                    $box = $v['box'];
                    $v['default_box'] = isset($v['default_box']) ? (string) $v['default_box'] : 'default';
                    $v['boxes'] = array($v['default_box'] => $box);
                    return $v;
                })
            ->end()
            ->children()
                ->arrayNode('boxes')
                ->useAttributeAsKey('name', true)
                ->canBeUnset()
                ->prototype('array');

        $this->addConnectionsSection($connectionsPrototypeNode);
        $this->addBoxesSection($boxesPrototypeNode);

        $rootNode
            ->children()
            ->scalarNode('default_connection')->defaultValue('default')->end()
            ->end();
        $rootNode
            ->children()
            ->scalarNode('default_box')->defaultValue('default')->end()
            ->end();
        return $treeBuilder;
    }

    private function addBoxesSection(ArrayNodeDefinition $nodeDefinition)
    {
        $nodeDefinition
            ->children()
                ->scalarNode('id')->info('Box ID')->end()
                ->scalarNode('connection')->info('Connection name')->end()
            ->end();
    }

    private function addConnectionsSection(ArrayNodeDefinition $nodeDefinition)
    {
        $signerNode = $nodeDefinition
            ->children()
                ->scalarNode('ddauth')->isRequired()->info('Developer key')->end()
                ->scalarNode('certificate')->info('Certificate in DER format')->end()
                ->scalarNode('login')->info('Use login for authorization')->end()
                ->scalarNode('password')->info('Use password if authorized by login')->end()
                ->scalarNode('signer_service')->info('Signer service')->end()
                ->arrayNode('signer');

        $this->addSignerSection($signerNode);

        $nodeDefinition
            ->validate()
                ->ifTrue(function($v) {
                    return isset($v['login']) && isset($v['certificate']) && !empty($v['login']) && !empty($v['certificate']);
                })
                ->thenInvalid('You must choose between login or certificate')
            ->end()
            ->validate()
                ->ifTrue(function($v) {
                    return (!isset($v['login']) && !isset($v['certificate'])) || (empty($v['login']) && empty($v['certificate']));
                })
                ->thenInvalid('Set login or certificate')
            ->end()
            ->validate()
                ->ifTrue(function($v) {
                    return isset($v['signer_service']) && isset($v['signer']) && !empty($v['signer_service']) && !empty($v['signer']);
                })
                ->thenInvalid('You must choose between signer_service or signer')
            ->end()

            ->validate()
                ->ifTrue(function($v) {
                    return (!isset($v['signer_service']) && !isset($v['signer'])) || (empty($v['signer_service']) && empty($v['signer']));
                })
                ->thenInvalid('Set signer_service or signer')
            ->end();
    }

    private function addSignerSection(ArrayNodeDefinition $nodeDefinition)
    {
        foreach ($this->signerProvidersFactory as $factory) {
            $name = str_replace('-', '_', $factory->getName());
            $factoryNode = $nodeDefinition->children()->arrayNode($name);
            $factory->addConfiguration($factoryNode);
        }

        $nodeDefinition
            ->validate()
                ->ifTrue(function ($v) {return count($v) > 1;})
                ->thenInvalid('You cannot set multiple signers')
            ->end()
            ->validate()
                ->ifTrue(function ($v) {return count($v) === 0;})
                ->thenInvalid('You must set a signer definition')
            ->end();


    }


}
