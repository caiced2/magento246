<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Plugin\Catalog\Controller\Adminhtml\Category;

use \Magento\Catalog\Controller\Adminhtml\Category\Add as AddController;

class AddPlugin
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Magento\VisualMerchandiser\Model\Position\Cache $cache
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * Register the cache key before controller is executed
     *
     * @param $subject AddController
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(AddController $subject)
    {
        $this->registry->register(
            \Magento\VisualMerchandiser\Model\Position\Cache::POSITION_CACHE_KEY,
            uniqid()
        );
    }
}
