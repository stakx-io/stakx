<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Core;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends \Symfony\Component\Console\Application
{
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (extension_loaded('xdebug') && !getenv('COMPOSER_DISABLE_XDEBUG_WARN'))
        {
            $output->writeln('<fg=black;bg=yellow>You are running Stakx with xdebug enabled. This has a major impact on runtime performance.</>');
        }

        return parent::doRun($input, $output);
    }
}
