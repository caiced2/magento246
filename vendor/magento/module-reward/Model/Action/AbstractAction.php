<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reward action model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Reward\Model\Action;

/**
 * @api
 * @since 100.0.2
 */
abstract class AbstractAction extends \Magento\Framework\DataObject
{
    /**
     * Reward Instance
     * @var \Magento\Reward\Model\Reward
     */
    protected $_reward;

    /**
     * Reward History Instance
     * @var \Magento\Reward\Model\Reward\History
     */
    protected $_history;

    /**
     * Entity Instance
     * @var \Magento\Framework\DataObject
     */
    protected $_entity;

    /**
     * Retrieve points delta for action
     *
     * @param int $websiteId
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codeCoverageIgnore
     */
    public function getPoints($websiteId)
    {
        return 0;
    }

    /**
     * Check whether rewards can be added for action
     *
     * @return bool
     */
    public function canAddRewardPoints()
    {
        if ($this->getEntity()) {
            $exist = $this->getHistory()->isExistHistoryUpdate(
                $this->getReward()->getCustomerId(),
                $this->getAction(),
                $this->getReward()->getWebsiteId(),
                $this->getEntity()->getId()
            );
        } else {
            $exist = false;
        }
        $exceeded = $this->isRewardLimitExceeded();
        return !$exist && !$exceeded;
    }

    /**
     * Check whether rewards limit is exceeded for action
     *
     * @return bool
     */
    public function isRewardLimitExceeded()
    {
        $limit = $this->getRewardLimit();
        if (!$limit) {
            return false;
        }
        $total = $this->getHistory()->getTotalQtyRewards(
            $this->getAction(),
            $this->getReward()->getCustomerId(),
            $this->getReward()->getWebsiteId()
        );

        if ($limit > $total) {
            return false;
        }
        return true;
    }

    /**
     * Return pre-configured limit of rewards for action
     * By default - without limitations
     *
     * @return int|string
     * @codeCoverageIgnore
     */
    public function getRewardLimit()
    {
        return 0;
    }

    /**
     * Estimate rewards available qty
     *
     * @return int|null
     */
    public function estimateRewardsQtyLimit()
    {
        $maxQty = (int)$this->getRewardLimit();
        if ($maxQty > 0) {
            $usedQty = (int)$this->getHistory()->getTotalQtyRewards(
                $this->getAction(),
                $this->getReward()->getCustomerId(),
                $this->getReward()->getWebsiteId()
            );
            return min(max($maxQty - $usedQty, 0), $maxQty);
        }
        return null;
    }

    /**
     * Return action message for history log
     *
     * @param array $args Additional history data
     * @return string
     */
    abstract public function getHistoryMessage($args = []);

    /**
     * Setter for $_reward
     *
     * @param \Magento\Reward\Model\Reward $reward
     * @return $this
     * @codeCoverageIgnore
     */
    public function setReward($reward)
    {
        $this->_reward = $reward;
        return $this;
    }

    /**
     * Getter for $_reward
     *
     * @return \Magento\Reward\Model\Reward
     * @codeCoverageIgnore
     */
    public function getReward()
    {
        return $this->_reward;
    }

    /**
     * Setter for $_history
     *
     * @param \Magento\Reward\Model\Reward\History $history
     * @return $this
     * @codeCoverageIgnore
     */
    public function setHistory($history)
    {
        $this->_history = $history;
        return $this;
    }

    /**
     * Getter for $_history
     *
     * @return \Magento\Reward\Model\Reward\History
     * @codeCoverageIgnore
     */
    public function getHistory()
    {
        return $this->_history;
    }

    /**
     * Setter for $_entity and assign entity Id to history
     *
     * @param \Magento\Framework\DataObject $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->_entity = $entity;
        if ($this->getHistory() instanceof \Magento\Framework\DataObject) {
            $this->getHistory()->setEntity($entity->getId());
        }
        return $this;
    }

    /**
     * Description goes here...
     *
     * @return \Magento\Framework\DataObject
     * @codeCoverageIgnore
     */
    public function getEntity()
    {
        return $this->_entity;
    }
}
