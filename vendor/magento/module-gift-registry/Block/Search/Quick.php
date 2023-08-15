<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Search;

/**
 * Gift registry quick search block
 */
class Quick extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\GiftRegistry\Model\TypeFactory
     */
    protected $typeFactory;

    /**
     * Gift registry data
     *
     * @var \Magento\GiftRegistry\Helper\Data
     */
    protected $_giftRegistryData = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\GiftRegistry\Helper\Data $giftRegistryData
     * @param \Magento\GiftRegistry\Model\TypeFactory $typeFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\GiftRegistry\Helper\Data $giftRegistryData,
        \Magento\GiftRegistry\Model\TypeFactory $typeFactory,
        array $data = []
    ) {
        $this->_giftRegistryData = $giftRegistryData;
        $this->typeFactory = $typeFactory;
        parent::__construct($context, $data);
    }

    /**
     * Check whether module is available
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @codeCoverageIgnore
     */
    public function getEnabled()
    {
        return $this->_giftRegistryData->isEnabled();
    }

    /**
     * Return available gift registry types collection
     *
     * @return \Magento\GiftRegistry\Model\ResourceModel\Type\Collection
     * @codeCoverageIgnore
     */
    public function getTypesCollection()
    {
        return $this->typeFactory->create()->getCollection()->addStoreData(
            $this->_storeManager->getStore()->getId()
        )->applyListedFilter()->applySortOrder();
    }

    /**
     * Select element for choosing registry type
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTypeSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            ['id' => 'quick_search_type_id', 'class' => 'select']
        )->setName(
            'params[type_id]'
        )->setOptions(
            $this->getTypesCollection()->toOptionArray(true)
        );
        return $select->getHtml();
    }

    /**
     * Return quick search form action url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getActionUrl()
    {
        return $this->getUrl('magento_giftregistry/search/results');
    }
}
