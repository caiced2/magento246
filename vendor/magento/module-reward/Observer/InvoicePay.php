<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer;

use Magento\Framework\Event\ObserverInterface;

class InvoicePay implements ObserverInterface
{
    /**
     * @var \Magento\Invitation\Model\InvitationFactory
     */
    protected $_invitationFactory;

    /**
     * Reward factory
     *
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * Core helper
     *
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     * @param \Magento\Invitation\Model\InvitationFactory $invitationFactory
     */
    public function __construct(
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Reward\Model\RewardFactory $rewardFactory,
        \Magento\Invitation\Model\InvitationFactory $invitationFactory
    ) {
        $this->moduleManager = $moduleManager;
        $this->_rewardFactory = $rewardFactory;
        $this->_invitationFactory = $invitationFactory;
    }

    /**
     * Update invitation points balance after referral's order completed
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    protected function _invitationToOrder(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->moduleManager->isEnabled('Magento_Invitation')) {
            $invoice = $observer->getEvent()->getInvoice();
            /* @var $invoice \Magento\Sales\Model\Order\Invoice */
            $order = $invoice->getOrder();
            /* @var $order \Magento\Sales\Model\Order */
            if ($order->getBaseTotalDue() > 0) {
                return $this;
            }
            $invitation = $this->_invitationFactory->create()->load($order->getCustomerId(), 'referral_id');
            if (!$invitation->getId() || !$invitation->getCustomerId()) {
                return $this;
            }
            $this->_rewardFactory->create()->setActionEntity(
                $invitation
            )->setCustomerId(
                $invitation->getCustomerId()
            )->setStore(
                $order->getStoreId()
            )->setAction(
                \Magento\Reward\Model\Reward::REWARD_ACTION_INVITATION_ORDER
            )->updateRewardPoints();
        }

        return $this;
    }

    /**
     * Update inviter balance if possible
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $invoice \Magento\Sales\Model\Order\Invoice */
        $invoice = $observer->getEvent()->getInvoice();
        if (!$invoice->getOrigData($invoice->getResource()->getIdFieldName())) {
            $this->_invitationToOrder($observer);
        }

        return $this;
    }
}
