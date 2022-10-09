<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Templating\Twig\Extension;

use Twig\TwigFunction;

class FileFunction extends AbstractFilesystemTwigExtension implements TwigFunctionInterface
{
    public function __invoke($filePath)
    {
        parent::__invoke($filePath);

        return file_get_contents($this->path);
    }

    public static function get()
    {
        return new TwigFunction('file', new self());
    }
}
