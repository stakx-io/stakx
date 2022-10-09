<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Filesystem;

/**
 * @since 0.2.0
 */
class FileExplorerDefinition
{
    /** @var string[] */
    public array $includes = [];

    /** @var string[] */
    public array $excludes = [];

    public ?int $flags = null;

    public function __construct(public Folder $folder)
    {
    }
}
