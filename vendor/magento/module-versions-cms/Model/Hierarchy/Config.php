<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Model\Hierarchy;

use Magento\Framework\Config\Data\Scoped;
use Magento\Framework\Serialize\SerializerInterface;

class Config extends Scoped implements ConfigInterface
{
    /**
     * Menu layouts configuration
     *
     * @var array
     */
    protected $_contextMenuLayouts = null;

    /**
     * Scope priority loading scheme
     *
     * @var string[]
     */
    protected $_scopePriorityScheme = ['global'];

    /**
     * @param \Magento\VersionsCms\Model\Hierarchy\Config\Reader $reader
     * @param \Magento\Framework\Config\ScopeInterface $configScope
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param string|null $cacheId
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        \Magento\VersionsCms\Model\Hierarchy\Config\Reader $reader,
        \Magento\Framework\Config\ScopeInterface $configScope,
        \Magento\Framework\Config\CacheInterface $cache,
        $cacheId = 'menuHierarchyConfigCache',
        SerializerInterface $serializer = null
    ) {
        parent::__construct($reader, $configScope, $cache, $cacheId, $serializer);
    }

    /**
     * Return available Context Menu layouts output
     *
     * @return array
     */
    public function getAllMenuLayouts()
    {
        return $this->get();
    }

    /**
     * Return Context Menu layout by its name
     *
     * @param string $layoutName
     * @return \Magento\Framework\DataObject|bool
     */
    public function getContextMenuLayout($layoutName)
    {
        $menuLayouts = $this->get();
        return isset($menuLayouts[$layoutName]) ? $menuLayouts[$layoutName] : false;
    }
}
