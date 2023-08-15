<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\Item\Collection;

use Magento\GiftRegistry\Model\ResourceModel\Item\Collection;

/**
 * Filter Interface for Gift Registry items.
 */
interface FilterInterface
{
    /**
     * Filter Gift Registry items Collection.
     *
     * @param Collection $collection
     * @return void
     */
    public function execute(Collection $collection): void;
}
