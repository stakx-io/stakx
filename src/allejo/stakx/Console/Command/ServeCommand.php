<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Console\Command;

use allejo\stakx\RuntimeStatus;
use allejo\stakx\Server\WebServer;
use allejo\stakx\Server\RouteMapper;
use allejo\stakx\Service;
use allejo\stakx\Website;
use React\EventLoop\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServeCommand extends BuildCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('serve');
        $this->setDescription('Start a web server serving the stakx website');

        $this->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'The port the local development server will be listening on', 8000);
        $this->addOption('bind', null, InputOption::VALUE_REQUIRED, 'The IP the local development server will bind to', '0.0.0.0');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->handleDeprecations($input, $output);
        $this->setRunTimeOptions($input);

        Service::setRuntimeFlag(RuntimeStatus::IN_SERVE_MODE);

        try
        {
            $this->configureConfigurationFile($input);
            $website = $this->getContainer()->get(Website::class);

            /** @var RouteMapper $router */
            $router = $this->getContainer()->get(RouteMapper::class);
            $router->setBaseUrl($website->getConfiguration()->getBaseUrl());

            $loop = Factory::create();
            $socket = new \React\Socket\Server(
                $input->getOption('bind') . ':' . $input->getOption('port'),
                $loop
            );

            $server = WebServer::create($router, $website->getCompiler());
            $server->listen($socket);

            $output->writeln('Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()));

            $loop->run();
        }
        catch (\Exception $e)
        {
            $output->writeln($e->getMessage());
        }
    }
}
