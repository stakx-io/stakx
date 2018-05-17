<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Command;

use allejo\stakx\Exception\FileAwareException;
use allejo\stakx\Utilities\StrUtils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends BuildableCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('build');
        $this->setDescription('Builds the stakx website');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        try
        {
            $this->website->build();

            $output->writeln(sprintf('Your site built successfully! It can be found at: %s',
                $this->website->getConfiguration()->getTargetFolder() . DIRECTORY_SEPARATOR
            ));

            return 0;
        }
        catch (FileAwareException $e)
        {
            $output->writeln(StrUtils::interpolate(
                "Your website failed to build with the following error in file '{file}'{line}: {message}", [
                    'file' => $e->getPath(),
                    'line' => (($l = $e->getLineNumber()) >= 0) ? ' on line ' . $l : '',
                    'message' => $e->getMessage(),
                ]
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
