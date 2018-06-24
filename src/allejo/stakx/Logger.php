<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * PSR-3 compliant console logger.
 *
 * This class is based entirely on Symfony's ConsoleLogger interface with minor modifications.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @see    http://www.php-fig.org/psr/psr-3/
 * @see    https://github.com/symfony/console/blob/master/Logger/ConsoleLogger.php
 */
class Logger extends AbstractLogger
{
    const INFO = 'info';
    const ERROR = 'error';

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array
     */
    private $verbosityLevelMap = array(
        LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ALERT     => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::CRITICAL  => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ERROR     => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::WARNING   => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::NOTICE    => OutputInterface::VERBOSITY_VERBOSE,
        LogLevel::INFO      => OutputInterface::VERBOSITY_VERY_VERBOSE,
        LogLevel::DEBUG     => OutputInterface::VERBOSITY_DEBUG,
    );

    /**
     * @var array
     */
    private $formatLevelMap = array(
        LogLevel::EMERGENCY => self::ERROR,
        LogLevel::ALERT     => self::ERROR,
        LogLevel::CRITICAL  => self::ERROR,
        LogLevel::ERROR     => self::ERROR,
        LogLevel::WARNING   => self::INFO,
        LogLevel::NOTICE    => self::INFO,
        LogLevel::INFO      => self::INFO,
        LogLevel::DEBUG     => self::INFO,
    );

    /**
     * ConsoleInterface constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Return the OutputInterface object.
     *
     * @return OutputInterface
     */
    public function getOutputInterface()
    {
        return $this->output;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array())
    {
        if (!isset($this->verbosityLevelMap[$level]))
        {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
        }

        $verbosity = $this->output->getVerbosity();

        if ($verbosity >= $this->verbosityLevelMap[$level])
        {
            $prefix = '';

            if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE)
            {
                $prefix = sprintf('[%s] ', date('H:i:s'));
            }

            $this->output->writeln(
                sprintf('<%1$s>%2$s[%3$s] %4$s</%1$s>',
                    $this->formatLevelMap[$level],
                    $prefix,
                    $level,
                    $this->interpolate($message, $context)
                )
            );
        }
    }

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string|array $messages The message as an array of lines of a single string
     * @param int          $options  A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is considered
     *                               the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function writeln($messages, $options = 0)
    {
        $this->output->writeln($messages, $options);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @author PHP Framework Interoperability Group
     *
     * @param string $message
     * @param array  $context
     *
     * @return string
     */
    private function interpolate($message, array $context)
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val)
        {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString')))
            {
                $replace[sprintf('{%s}', $key)] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
