<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Model\Creditmemo;

use Magento\CustomerBalance\Model\Balance\History;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Customer balance refund.
 */
class Balance
{
    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param BalanceFactory $balanceFactory
     * @param StoreManagerInterface $storeManager
     * @param OrderRepositoryInterface|null $orderRepository
     */
    public function __construct(
        BalanceFactory $balanceFactory,
        StoreManagerInterface $storeManager,
        ?OrderRepositoryInterface $orderRepository = null
    ) {
        $this->balanceFactory = $balanceFactory;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository ?: ObjectManager::getInstance()->get(OrderRepositoryInterface::class);
    }

    /**
     * Save refunded customer balance.
     *
     * @param Creditmemo $creditmemo
     * @return void
     * @throws NoSuchEntityException
     */
    public function save(Creditmemo $creditmemo) :void
    {
        $order = $creditmemo->getOrder();
        $order->setBsCustomerBalTotalRefunded(
            $order->getBsCustomerBalTotalRefunded() + $creditmemo->getBsCustomerBalTotalRefunded()
        );
        $order->setCustomerBalTotalRefunded(
            $order->getCustomerBalTotalRefunded() + $creditmemo->getCustomerBalTotalRefunded()
        );
        $order->setBaseCustomerBalanceRefunded(
            $order->getBaseCustomerBalanceRefunded() + $creditmemo->getBaseCustomerBalanceRefunded()
        );
        $customerBalanceRefunded = $creditmemo->getCustomerBalanceRefunded();
        $order->setCustomerBalanceRefunded(
            $order->getCustomerBalanceRefunded() + $customerBalanceRefunded
        );
        $this->orderRepository->save($order);
        $status = $order->getConfig()->getStateDefaultStatus($order->getState());
        $comment = __(
            'We refunded %1 to Store Credit',
            $order->getBaseCurrency()->formatTxt($customerBalanceRefunded)
        );
        $order->addCommentToStatusHistory($comment, $status, false);
        $websiteId = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();

        $this->balanceFactory->create()
            ->setCustomerId($order->getCustomerId())
            ->setWebsiteId($websiteId)
            ->setAmountDelta($creditmemo->getBsCustomerBalTotalRefunded())
            ->setHistoryAction(History::ACTION_REFUNDED)
            ->setOrder($order)
            ->setCreditMemo($creditmemo)
            ->save();
    }
}
