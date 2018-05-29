<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Event;

use allejo\stakx\Document\DataItem;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired whenever a new DataItem is registered. This event grants access to the DataItem allowing any
 * necessary modifications.
 *
 * @since 0.2.0
 */
class DataItemAdded extends Event
{
    const NAME = 'dataitem.item.added';

    private $dataItem;

    public function __construct(&$dataItem)
    {
        $this->dataItem = &$dataItem;
    }

    /**
     * @return DataItem
     */
    public function &getDataItem()
    {
        return $this->dataItem;
    }
}
