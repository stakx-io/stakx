<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\DataItem;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired whenever a new DataItem is registered. This event grants access to the DataItem allowing any
 * necessary modifications.
 *
 * @since 0.2.0
 */
class DataItemAdded extends Event
{
    final public const NAME = 'dataitem.item.added';

    private $dataItem;

    public function __construct(&$dataItem)
    {
        $this->dataItem = &$dataItem;
    }

    public function &getDataItem(): DataItem
    {
        return $this->dataItem;
    }
}
