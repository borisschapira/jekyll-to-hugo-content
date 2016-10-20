<?php

namespace ConsoleDI\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class Application extends \Symfony\Component\Console\Application
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param string $name    The name of the application
     * @param string $version The version of the application
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        $this->container = new ContainerBuilder();
        $loader = new XmlFileLoader($this->container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.xml');

        parent::__construct($name, $version);
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return Command[] An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        $commands = [];

        foreach ($this->container->findTaggedServiceIds('console.command') as $commandId => $command) {
            $commands[] = $this->container->get($commandId);
        }

        return $commands;
    }
}