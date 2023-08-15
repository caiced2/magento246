<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Plugin\Catalog\Controller\Adminhtml\Category;

use \Magento\Catalog\Controller\Adminhtml\Category\Edit as EditController;

class EditPlugin
{
    /**
     * @var \Magento\VisualMerchandiser\Model\Position\Cache
     */
    protected $cache;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Magento\VisualMerchandiser\Model\Position\Cache $cache
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\VisualMerchandiser\Model\Position\Cache $cache,
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
        $this->cache = $cache;
    }

    /**
     * Register the cache key before controller is executed
     *
     * @param $subject EditController
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(EditController $subject)
    {
        $this->registry->register(
            \Magento\VisualMerchandiser\Model\Position\Cache::POSITION_CACHE_KEY,
            uniqid()
        );
    }
}
