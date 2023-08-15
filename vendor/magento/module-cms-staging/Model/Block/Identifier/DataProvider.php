<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Model\Block\Identifier;

use Magento\Cms\Model\Block;
use Magento\Cms\Model\Block\DataProvider as CmsDataProvider;

/**
 * Class DataProvider
 */
class DataProvider extends CmsDataProvider
{
    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var Block $block */
        foreach ($items as $block) {
            $this->loadedData[$block->getId()] = [
                'block_id' => $block->getId(),
                'title' => $block->getTitle(),
            ];
        }

        return $this->loadedData;
    }
}
