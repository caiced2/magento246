<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Plugin\Controller\Order;

use Closure;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Redirect to the order history page if order in status received.
 */
class CustomerRedirectPlugin
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param UserContextInterface $userContext
     * @param RedirectFactory $resultRedirectFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param RequestInterface $request
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        UserContextInterface $userContext,
        RedirectFactory $resultRedirectFactory,
        OrderRepositoryInterface $orderRepository,
        RequestInterface $request,
        DeploymentConfig $deploymentConfig
    ) {
        $this->userContext = $userContext;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->orderRepository = $orderRepository;
        $this->request = $request;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Order around execute plugin.
     *
     * @param ActionInterface $subject
     * @param Closure $proceed
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        ActionInterface $subject,
        Closure $proceed
    ): ResultInterface {
        if ($this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            $customerId = $this->userContext->getUserId();
            if ($customerId) {
                $orderId = $this->request->getParam('order_id');
                try {
                    $order = $this->orderRepository->get($orderId);
                } catch (NoSuchEntityException $exception) {
                    return $proceed();
                }

                if ($this->isOrderReceived($order)) {
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('sales/order/history');

                    return $resultRedirect;
                }
            }
        }

        return $proceed();
    }

    /**
     * Order with status received.
     *
     * @param Order $order
     * @return bool
     */
    private function isOrderReceived(Order $order): bool
    {
        if ($order->getStatus() !== OrderManagement::STATUS_RECEIVED) {
            return false;
        }

        return true;
    }
}
