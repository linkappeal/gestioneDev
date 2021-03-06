<?php

// src/AppBundle/DependencyInjection/AppExtension.php

namespace AppBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AppExtension extends Extension\Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // ...
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        // ...
        $loader->load('admin.yml');
    }
}

