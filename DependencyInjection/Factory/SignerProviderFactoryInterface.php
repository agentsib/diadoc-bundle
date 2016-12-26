<?php
/**
 * User: ikovalenko
 */

namespace AgentSIB\DiadocBundle\DependencyInjection\Factory;


use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface SignerProviderFactoryInterface
{
    /**
     * @param ContainerBuilder $container
     * @param string           $sourceName
     * @param mixed            $config
     *
     * @return string The secret source service id
     */
    public function create(ContainerBuilder $container, $sourceName, $config);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param NodeDefinition $builder
     */
    public function addConfiguration(NodeDefinition $builder);
}