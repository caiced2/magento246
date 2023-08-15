<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesArchive\Model\System\Config\Backend;

class Active extends \Magento\Config\Model\Config\Backend\Cache implements
    \Magento\Config\Model\Config\CommentInterface,
    \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @var \Magento\SalesArchive\Model\Archive
     */
    protected $_archive;

    /**
     * @var \Magento\SalesArchive\Model\ResourceModel\Order\Collection
     */
    protected $_orderCollection;

    /**
     * Cache tags to clean
     *
     * @var array
     */
    protected $_cacheTags = [\Magento\Backend\Block\Menu::CACHE_TAGS];

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\SalesArchive\Model\Archive $archive
     * @param \Magento\SalesArchive\Model\ResourceModel\Order\Collection $orderCollection
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\SalesArchive\Model\Archive $archive,
        \Magento\SalesArchive\Model\ResourceModel\Order\Collection $orderCollection,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_archive = $archive;
        $this->_orderCollection = $orderCollection;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Clean cache, value was changed
     *
     * @return $this
     */
    public function afterSave()
    {
        parent::afterSave();
        if ($this->isValueChanged() && !$this->getValue()) {
            $this->_archive->removeOrdersFromArchive();
        }
        return parent::afterSave();
    }

    /**
     * Get field comment
     *
     * @param string $currentValue
     * @return \Magento\Framework\Phrase|string
     */
    public function getCommentText($currentValue)
    {
        if ($currentValue) {
            $ordersCount = $this->_orderCollection->getSize();
            if ($ordersCount) {
                return __(
                    'There are %1 orders in this archive. '
                    . 'All of them will be moved to the regular table after the archive is disabled.',
                    $ordersCount
                );
            }
        }
        return '';
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [\Magento\Backend\Block\Menu::CACHE_TAGS];
    }
}
