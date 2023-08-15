<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Action;

use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Tax\Model\Config;

/**
 * Reward action for converting spent money to points
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class OrderExtra extends \Magento\Reward\Model\Action\AbstractAction
{
    /**
     * Quote instance, required for estimating checkout reward (order subtotal - discount)
     *
     * @var Quote
     */
    protected $_quote = null;

    /**
     * Reward data
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData = null;

    /**
     * @var \Magento\Tax\Model\Config|null
     */
    private $taxConfig = null;

    /**
     * Constructor
     *
     * By default is looking for first argument as array and assigns it as object
     * attributes This behavior may change in child classes
     *
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param array $data
     * @param \Magento\Tax\Model\Config|null $taxConfig
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Reward\Helper\Data $rewardData,
        array $data = [],
        \Magento\Tax\Model\Config $taxConfig = null
    ) {
        $this->taxConfig = $taxConfig;
        $this->_rewardData = $rewardData;
        parent::__construct($data);
    }

    /**
     * Return action message for history log
     *
     * @param array $args Additional history data
     * @return \Magento\Framework\Phrase
     */
    public function getHistoryMessage($args = [])
    {
        $incrementId = isset($args['increment_id']) ? $args['increment_id'] : '';
        return __('Earned points for order #%1', $incrementId);
    }

    /**
     * Setter for $_entity and add some extra data to history
     *
     * @param \Magento\Framework\DataObject $entity
     * @return $this
     * @codeCoverageIgnore
     */
    public function setEntity($entity)
    {
        parent::setEntity($entity);
        $this->getHistory()->addAdditionalData(['increment_id' => $this->getEntity()->getIncrementId()]);
        return $this;
    }

    /**
     * Quote setter
     *
     * @param Quote $quote
     * @return $this
     * @codeCoverageIgnore
     */
    public function setQuote(Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    /**
     * Retrieve points delta for action
     *
     * @param int $websiteId
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPoints($websiteId)
    {
        if (!$this->_rewardData->isOrderAllowed($this->getReward()->getWebsiteId())) {
            return 0;
        }
        if ($this->_quote) {
            $quote = $this->_quote;
            // known issue: no support for multishipping quote
            $address = $quote->getIsVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
            // use only money customer spend - shipping & tax
            if ($this->getTaxConfig()->priceIncludesTax($quote->getStoreId())) {
                $monetaryAmount = $quote->getBaseGrandTotal() -
                    $address->getBaseShippingInclTax();
            } else {
                $monetaryAmount = $quote->getBaseGrandTotal() -
                    $address->getBaseShippingAmount() -
                    $address->getBaseTaxAmount();
            }

            $monetaryAmount = $monetaryAmount < 0 ? 0 : $monetaryAmount;
        } else {
            if ($this->getTaxConfig()->priceIncludesTax($this->getEntity()->getStoreId())) {
                $monetaryAmount = $this->getEntity()->getBaseTotalPaid() -
                    $this->getEntity()->getBaseShippingInclTax();
            } else {
                $monetaryAmount = $this->getEntity()->getBaseTotalPaid() -
                    $this->getEntity()->getBaseShippingAmount() -
                    (
                        $this->getEntity()->getBaseTaxAmount() -
                        $this->getEntity()->getBaseTaxCanceled()
                    );
            }
        }
        $pointsDelta = $this->getReward()->getRateToPoints()->calculateToPoints((double)$monetaryAmount);
        return $pointsDelta;
    }

    /**
     * Check whether rewards can be added for action
     *
     * Checking for the history records is intentionally omitted
     *
     * @return bool
     */
    public function canAddRewardPoints()
    {
        return parent::canAddRewardPoints() && $this->_rewardData->isOrderAllowed($this->getReward()->getWebsiteId());
    }

    /**
     * Get tax config
     *
     * @return \Magento\Tax\Model\Config|mixed|null
     * @deprecated 101.0.2
     */
    private function getTaxConfig()
    {
        if ($this->taxConfig === null) {
            $this->taxConfig = ObjectManager::getInstance()->get(Config::class);
        }
        return $this->taxConfig;
    }
}
