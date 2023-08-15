<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\System\Config\Backend;

/**
 * Backend model for "Reward Points Lifetime"
 *
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class Expiration extends \Magento\Framework\App\Config\Value
{
    const XML_PATH_EXPIRATION_DAYS = 'magento_reward/general/expiration_days';

    /**
     * Core config collection
     * @var \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory
     */
    protected $_configFactory;

    /**
     * Reward history factory
     *
     * @var \Magento\Reward\Model\ResourceModel\Reward\HistoryFactory
     */
    protected $_historyFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $configFactory
     * @param \Magento\Reward\Model\ResourceModel\Reward\HistoryFactory $historyFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $configFactory,
        \Magento\Reward\Model\ResourceModel\Reward\HistoryFactory $historyFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_config = $config;
        $this->_configFactory = $configFactory;
        $this->_historyFactory = $historyFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Update history expiration date to simplify frontend calculations
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();
        if (!$this->isValueChanged()) {
            return $this;
        }

        $websiteIds = [];
        if ($this->getScope() == \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES) {
            $websiteIds = [$this->_storeManager->getWebsite($this->getScopeCode())->getId()];
        } else {
            $collection = $this->_configFactory->create()->addFieldToFilter(
                'path',
                self::XML_PATH_EXPIRATION_DAYS
            )->addFieldToFilter(
                'scope',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES
            );
            $websiteScopeIds = [];
            foreach ($collection as $item) {
                $websiteScopeIds[] = $item->getScopeId();
            }
            foreach ($this->_storeManager->getWebsites() as $website) {
                /* @var $website \Magento\Store\Model\Website */
                if (!in_array($website->getId(), $websiteScopeIds)) {
                    $websiteIds[] = $website->getId();
                }
            }
        }
        if (count($websiteIds) > 0) {
            $this->_historyFactory->create()->updateExpirationDate($this->getValue(), $websiteIds);
        }

        return $this;
    }

    /**
     * The same as _beforeSave, but executed when website config extends default values
     *
     * @return $this
     */
    public function beforeDelete()
    {
        parent::beforeDelete();
        if ($this->getScope() == \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES) {
            $default = (string)$this->_config->getValue(self::XML_PATH_EXPIRATION_DAYS, 'default');
            $websiteIds = [$this->_storeManager->getWebsite($this->getScopeCode())->getId()];
            $this->_historyFactory->create()->updateExpirationDate($default, $websiteIds);
        }
        return $this;
    }
}
