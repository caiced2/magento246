<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerFinance\Helper;

use \Magento\ImportExport\Model\Export as ModelExport;

/**
 * CustomerFinance Data Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Reward data
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardHelper;

    /**
     * Customer balance data
     *
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Helper\Data $rewardHelper
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Helper\Data $rewardHelper,
        \Magento\CustomerBalance\Helper\Data $customerBalanceHelper
    ) {
        $this->_rewardHelper = $rewardHelper;
        $this->_customerBalanceHelper = $customerBalanceHelper;
        $this->_storeManager = $storeManager;
        parent::__construct(
            $context
        );
    }

    /**
     * Is reward points enabled
     *
     * @return bool
     */
    public function isRewardPointsEnabled()
    {
        if ($this->_moduleManager->isEnabled('Magento_Reward')) {
            return $this->_rewardHelper->isEnabled();
        }
        return false;
    }

    /**
     * Is store credit enabled
     *
     * @return bool
     */
    public function isCustomerBalanceEnabled()
    {
        if ($this->_moduleManager->isEnabled('Magento_CustomerBalance')) {
            return $this->_customerBalanceHelper->isEnabled();
        }
        return false;
    }

    /**
     * Extend parameters to filter reward_points and store_credit on all websites
     *
     * @param array $params
     * @return void
     */
    public function populateParams(&$params)
    {
        if (!isset($params[ModelExport::FILTER_ELEMENT_GROUP])) {
            return;
        }
        foreach ($params[ModelExport::FILTER_ELEMENT_GROUP] as $paramName => $data) {
            unset($params[ModelExport::FILTER_ELEMENT_GROUP][$paramName]);
            foreach ($this->_storeManager->getWebsites() as $website) {
                $params[ModelExport::FILTER_ELEMENT_GROUP][$website->getCode() . '_' . $paramName] = $data;
            }
        }
    }
}
