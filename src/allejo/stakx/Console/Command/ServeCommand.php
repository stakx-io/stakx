<?php

namespace allejo\stakx\Console\Command;

use allejo\stakx\Server\DevServer;
use allejo\stakx\Server\PageViewRouter;
use allejo\stakx\Website;
use React\EventLoop\Factory;
use Symfony\Component\Console\Input\InputInterface;
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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->handleDeprecations($input, $output);
        $this->setRunTimeOptions($input);

        try
        {
            $this->configureConfigurationFile($input);
            $website = $this->getContainer()->get(Website::class);

            /** @var PageViewRouter $router */
            $router = $this->getContainer()->get(PageViewRouter::class);

            $loop = Factory::create();
            $socket = new \React\Socket\Server('0.0.0.0:8000', $loop);

            $server = DevServer::create($router, $website->getCompiler());
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
