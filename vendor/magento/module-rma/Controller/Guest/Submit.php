<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Rma\Controller\Guest;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Helper\Guest;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Status\HistoryFactory;
use Magento\Rma\Model\RmaFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\Rma\Status\History;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Magento\Rma\Helper\Data;

/**
 * Controller class Submit. Contains logic of request, responsible for return creation for Guest user
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Submit extends Action implements HttpPostActionInterface
{
    /**
     * @var RmaFactory
     */
    private $rmaModelFactory;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var HistoryFactory
     */
    private $statusHistoryFactory;

    /**
     * @var Data
     */
    private $rmaHelper;

    /**
     * @var Guest
     */
    private $salesGuestHelper;

    /**
     * Submit constructor.
     *
     * @param Context $context
     * @param RmaFactory $rmaModelFactory
     * @param OrderRepository $orderRepository
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     * @param HistoryFactory $statusHistoryFactory
     * @param Data|null $rmaHelper
     * @param Guest|null $salesGuestHelper
     */
    public function __construct(
        Context $context,
        RmaFactory $rmaModelFactory,
        OrderRepository $orderRepository,
        LoggerInterface $logger,
        DateTime $dateTime,
        HistoryFactory $statusHistoryFactory,
        ?Data $rmaHelper = null,
        ?Guest $salesGuestHelper = null
    ) {
        $this->rmaModelFactory = $rmaModelFactory;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->statusHistoryFactory = $statusHistoryFactory;
        parent::__construct($context);

        $this->rmaHelper = $rmaHelper ?: $this->_objectManager->create(Data::class);
        $this->salesGuestHelper = $salesGuestHelper ?: $this->_objectManager->create(Guest::class);
    }

    /**
     * Goods return requests entrypoint
     */
    public function execute()
    {
        $result = $this->salesGuestHelper->loadValidOrder($this->_request);
        if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
            return $result;
        }

        $orderId = (int)$this->getRequest()->getParam('order_id');
        $post = $this->getRequest()->getPostValue();

        if ($post && !empty($post['items'])) {
            try {
                if (!$this->rmaHelper->canCreateRma($orderId)) {
                    $this->messageManager->addErrorMessage(
                        'We can\'t create a return transaction for order #%1.',
                        $orderId
                    );

                    return $this->resultRedirectFactory->create()->setPath('*/*/create', ['order_id' => $orderId]);
                }

                $order = $this->orderRepository->get($orderId);
                /** @var $rmaModel Rma */
                $rmaModel = $this->buildRma($order, $post);
                /** @var \Magento\Framework\Stdlib\DateTime\DateTime $coreDate */

                $result = $rmaModel->saveRma($post);
                if (!$result) {
                    return $this->resultRedirectFactory->create()->setPath('*/*/create', ['order_id' => $orderId]);
                }
                /** @var $statusHistory History */
                $statusHistory = $this->statusHistoryFactory->create();
                $statusHistory->setRmaEntityId($result->getId());
                $statusHistory->sendNewRmaEmail();
                $statusHistory->saveSystemComment();
                if (isset($post['rma_comment']) && !empty($post['rma_comment'])) {
                    /** @var $statusHistory History */
                    $comment = $this->statusHistoryFactory->create();
                    $comment->setRmaEntityId($result->getId());
                    $comment->saveComment($post['rma_comment'], true, false);
                }
                $this->messageManager->addSuccessMessage(__('You submitted Return #%1.', $rmaModel->getIncrementId()));
                $url = $this->_url->getUrl('*/*/returns');
                return $this->resultRedirectFactory->create()->setUrl($this->_redirect->success($url));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('We can\'t create a return right now. Please try again later.')
                );
                $this->logger->critical($e);
                return $this->resultRedirectFactory->create()->setPath('*/*/create', ['order_id' => $orderId]);
            }
        }
    }

    /**
     * Triggers save order and create history comment process
     *
     * @param Order $order
     * @param array $post
     * @return RmaInterface
     */
    private function buildRma(Order $order, array $post): RmaInterface
    {
        /** @var RmaInterface $rmaModel */
        $rmaModel = $this->rmaModelFactory->create();

        $rmaModel->setData(
            [
                'status' => Status::STATE_PENDING,
                'date_requested' => $this->dateTime->gmtDate(),
                'order_id' => $order->getId(),
                'order_increment_id' => $order->getIncrementId(),
                'store_id' => $order->getStoreId(),
                'customer_id' => $order->getCustomerId(),
                'order_date' => $order->getCreatedAt(),
                'customer_name' => $order->getCustomerName(),
                'customer_custom_email' => $post['customer_custom_email'],
            ]
        );

        return $rmaModel;
    }
}
