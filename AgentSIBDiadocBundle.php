<?php

namespace AgentSIB\DiadocBundle;

use AgentSIB\DiadocBundle\DependencyInjection\AgentSIBDiadocExtension;
use AgentSIB\DiadocBundle\DependencyInjection\Factory\CloudOpensslSignerProviderFactory;
use AgentSIB\DiadocBundle\DependencyInjection\Factory\OpensslSignerProviderFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AgentSIBDiadocBundle extends Bundle
{
    /**
     * @return AgentSIBDiadocExtension
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new AgentSIBDiadocExtension();
        }

        if ($this->extension) {
            return $this->extension;
        }
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $this->getContainerExtension();

        $extension->addSignerProviderFactory(new OpensslSignerProviderFactory());
        $extension->addSignerProviderFactory(new CloudOpensslSignerProviderFactory());
    }


}
