<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\Item\Collection;

use InvalidArgumentException;
use Magento\GiftRegistry\Model\ResourceModel\Item\Collection;

/**
 * Class for filtering Gift Registry items Collection.
 */
class CompositeFilter implements FilterInterface
{
    /**
     * @var FilterInterface[]
     */
    private $filters;

    /**
     * @param FilterInterface[] $filters
     * @throws InvalidArgumentException
     */
    public function __construct(array $filters = [])
    {
        foreach ($filters as $filter) {
            if (!$filter instanceof FilterInterface) {
                throw new InvalidArgumentException(
                    __('GiftRegistry item filters must implement %1.', FilterInterface::class)
                );
            }
        }

        $this->filters = $filters;
    }

    /**
     * @inheritDoc
     */
    public function execute(Collection $collection): void
    {
        foreach ($this->filters as $filter) {
            $filter->execute($collection);
        }
    }
}
