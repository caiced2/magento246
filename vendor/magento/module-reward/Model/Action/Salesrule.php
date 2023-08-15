<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Action;

use Magento\Framework\App\ObjectManager;
use Magento\Reward\Model\ResourceModel\RewardFactory;
use Magento\Reward\Model\SalesRule\RewardPointCounter;

/**
 * Reward action for updating balance by salesrule
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Salesrule extends \Magento\Reward\Model\Action\AbstractAction
{
    /**
     * Quote instance, required for estimating checkout reward (rule defined static value)
     *
     * @var \Magento\Quote\Model\Quote|null
     */
    protected $_quote = null;

    /**
     * Reward factory
     *
     * @var RewardFactory
     * @deprecated 101.0.0 since it is not used anymore in the class
     */
    protected $_rewardFactory;

    /**
     * @var RewardPointCounter
     */
    private $rewardPointCounter;

    /**
     * @param RewardFactory $rewardFactory
     * @param array $data
     * @param RewardPointCounter|null $rewardPointCounter
     */
    public function __construct(
        RewardFactory $rewardFactory,
        array $data = [],
        RewardPointCounter $rewardPointCounter = null
    ) {
        $this->_rewardFactory = $rewardFactory;
        parent::__construct($data);

        $this->rewardPointCounter = $rewardPointCounter ?: ObjectManager::getInstance()->get(RewardPointCounter::class);
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
        $pointsDelta = 0;
        if ($this->_quote) {
            // known issue: no support for multishipping quote // copied  comment, not checked
            if ($this->_quote->getAppliedRuleIds()) {
                $ruleIds = array_unique(explode(',', $this->_quote->getAppliedRuleIds()));
                $pointsDelta = $this->rewardPointCounter->getPointsForRules($ruleIds);
            }
        }
        return $pointsDelta;
    }

    /**
     * Quote setter
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this
     * @codeCoverageIgnore
     */
    public function setQuote(\Magento\Quote\Model\Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    /**
     * Check whether rewards can be added for action
     *
     * @return bool
     */
    public function canAddRewardPoints()
    {
        return true;
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
        return __('Earned promotion extra points from order #%1', $incrementId);
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
}
