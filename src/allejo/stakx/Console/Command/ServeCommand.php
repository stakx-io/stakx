<?php

namespace allejo\stakx\Console\Command;

use allejo\stakx\PageViewRouter;
use allejo\stakx\Website;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Response;
use React\Http\Server;
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
            $server = new Server(function (ServerRequestInterface $request) use ($router, $website) {
                $path = $request->getUri()->getPath();
                $method = $request->getMethod();

                $pageView = $router->getRoute($path);

                if ($method !== 'GET' || $pageView === null)
                {
                    return new Response(404, ['Content-Type' => 'text/plain'], 'URL not found');
                }

                return new Response(200, ['Content-Type' => 'text/html'], $website->getCompiler()->compilePageView($pageView));
            });

            $socket = new \React\Socket\Server('0.0.0.0:8000', $loop);
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
