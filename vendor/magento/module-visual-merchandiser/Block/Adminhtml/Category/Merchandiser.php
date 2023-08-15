<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Block\Adminhtml\Category;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\VisualMerchandiser\Model\Position\Cache;

/**
 * @api
 * @since 100.1.0
 */
class Merchandiser extends Template
{
    /**
     * @var Registry
     * @since 100.1.0
     */
    protected $_coreRegistry;

    /**
     * @var Cache
     * @since 100.1.0
     */
    protected $_positionCache;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Cache $cache
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Cache $cache,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_positionCache = $cache;
        parent::__construct($context, $data);
    }

    /**
     * Get dialog URL
     *
     * @return string
     * @since 100.1.0
     */
    public function getDialogUrl()
    {
        return $this->getUrl(
            'merchandiser/*/addproduct',
            [
                'cache_key' => $this->getPositionCacheKey(),
                'componentJson' => true
            ]
        );
    }

    /**
     * Get save positions URL
     *
     * @return string
     * @since 100.1.0
     */
    public function getSavePositionsUrl()
    {
        return $this->getUrl('merchandiser/position/save');
    }

    /**
     * Get products positions URL
     *
     * @return string
     * @since 100.1.0
     */
    public function getProductsPositionsUrl()
    {
        return $this->getUrl('merchandiser/position/get');
    }

    /**
     * Get category id
     *
     * @return mixed
     * @since 100.1.0
     */
    public function getCategoryId()
    {
        return $this->getRequest()->getParam('id');
    }

    /**
     * Get position cache key
     *
     * @return string
     * @since 100.1.0
     */
    public function getPositionCacheKey()
    {
        return $this->_coreRegistry->registry($this->getPositionCacheKeyName());
    }

    /**
     * Get position cache key name
     *
     * @return string
     * @since 100.1.0
     */
    public function getPositionCacheKeyName()
    {
        return Cache::POSITION_CACHE_KEY;
    }

    /**
     * Get position data JSON
     *
     * @return string
     * @since 100.1.0
     */
    public function getPositionDataJson()
    {
        return json_encode($this->_positionCache->getPositions($this->getPositionCacheKey()));
    }
}
