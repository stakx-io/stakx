<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Command;

use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Utilities\StrUtils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WatchCommand extends BuildableCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('watch');
        $this->setDescription('Watch the source folder and rebuild the website as files change');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        try
        {
            $this->configureBuild($input);

            $output->writeln('<fg=black;bg=yellow>Heads up! You are using an experimental feature.</>');

            $this->website->watch();
        }
        catch (FileAwareException $e)
        {
            $output->writeln(StrUtils::interpolate(
                "Your website failed to build with the following error in file '{file}'{line}: {message}", array(
                    'file' => $e->getPath(),
                    'line' => (($l = $e->getLineNumber()) >= 0) ? ' on line ' . $l : '',
                    'message' => $e->getMessage()
                )
            ));
        }
        catch (\Exception $e)
        {
            $output->writeln(sprintf('Your website failed to build with the following error: %s',
                $e->getMessage()
            ));
        }

        return 1;
    }
}
