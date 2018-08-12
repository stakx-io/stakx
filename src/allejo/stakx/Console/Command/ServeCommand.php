<?php

namespace allejo\stakx\Console\Command;

use allejo\stakx\Server\PageViewRouter;
use allejo\stakx\Server\RouteDispatcher;
use allejo\stakx\Website;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
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
            $dispatcher = RouteDispatcher::create($router, $website->getCompiler());

            $server = new Server(function (ServerRequestInterface $request) use ($dispatcher) {
                $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

                switch ($routeInfo[0])
                {
                    case \FastRoute\Dispatcher::FOUND:
                        $params = isset($routeInfo[2]) ? $routeInfo[2] : [];
                        return $routeInfo[1]($request, ...array_values($params));

                    default:
                        return RouteDispatcher::return404();
                }
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
