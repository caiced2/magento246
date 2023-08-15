<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reward action to add points to inviter when his referral purchases order
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Reward\Model\Action;

class InvitationOrder extends \Magento\Reward\Model\Action\AbstractAction
{
    /**
     * Reward data
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData = null;

    /**
     * Constructor
     *
     * By default is looking for first argument as array and assigns it as object
     * attributes This behavior may change in child classes
     *
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param array $data
     */
    public function __construct(\Magento\Reward\Helper\Data $rewardData, array $data = [])
    {
        $this->_rewardData = $rewardData;
        parent::__construct($data);
    }

    /**
     * Retrieve points delta for action
     *
     * @param int $websiteId
     * @return int
     */
    public function getPoints($websiteId)
    {
        return (int)$this->_rewardData->getPointsConfig('invitation_order', $websiteId);
    }

    /**
     * Check whether rewards can be added for action
     *
     * @return bool
     */
    public function canAddRewardPoints()
    {
        $frequency = $this->_rewardData->getPointsConfig(
            'invitation_order_frequency',
            $this->getReward()->getWebsiteId()
        );
        if ($frequency == '*') {
            return !$this->isRewardLimitExceeded();
        } else {
            return parent::canAddRewardPoints();
        }
    }

    /**
     * Return pre-configured limit of rewards for action
     *
     * @return int|string
     * @codeCoverageIgnore
     */
    public function getRewardLimit()
    {
        return $this->_rewardData->getPointsConfig('invitation_order_limit', $this->getReward()->getWebsiteId());
    }

    /**
     * Return action message for history log
     *
     * @param array $args Additional history data
     * @return \Magento\Framework\Phrase
     */
    public function getHistoryMessage($args = [])
    {
        $email = isset($args['email']) ? $args['email'] : '';
        return __('The invitation to %1 converted into an order.', $email);
    }

    /**
     * Setter for $_entity and add some extra data to history
     *
     * @param \Magento\Invitation\Model\Invitation $entity
     * @return \Magento\Reward\Model\Action\AbstractAction
     * @codeCoverageIgnore
     */
    public function setEntity($entity)
    {
        parent::setEntity($entity);
        $this->getHistory()->addAdditionalData(['email' => $this->getEntity()->getEmail()]);
        return $this;
    }
}
