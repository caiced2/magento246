<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Controller\Adminhtml\Position;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\VisualMerchandiser\Controller\Adminhtml\Position;
use Magento\VisualMerchandiser\Model\Position\Cache;

class Get extends Position implements HttpPostActionInterface
{
    /**
     * Get products positions from cache
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $cacheKey = $this->getRequest()->getParam(Cache::POSITION_CACHE_KEY);
        $positions = json_encode($this->cache->getPositions($cacheKey));
        $resultJson->setData($positions);

        return $resultJson;
    }
}
