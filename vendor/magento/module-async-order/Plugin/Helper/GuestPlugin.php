<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Plugin\Helper;

use Closure;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Helper\Guest;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Redirect guest to the home page if order is in status received.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GuestPlugin
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var DeploymentConfig
     */

    private $deploymentConfig;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param DeploymentConfig $deploymentConfig
     * @param StoreManagerInterface $storeManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        OrderRepositoryInterface $orderRepository,
        DeploymentConfig $deploymentConfig,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ManagerInterface $messageManager
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->orderRepository = $orderRepository;
        $this->deploymentConfig = $deploymentConfig;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->messageManager = $messageManager;
    }

    /**
     * Order load from post around plugin for guest.
     *
     * @param Guest $subject
     * @param Closure $proceed
     * @param RequestInterface $request
     * @return Redirect
     * @throws FileSystemException
     * @throws RuntimeException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundLoadValidOrder(
        Guest $subject,
        Closure $proceed,
        RequestInterface $request
    ) {
        if ($this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            try {
                $incrementId = $request->getPostValue('oar_order_id');
                if ($incrementId) {
                    $searchCriteria = $this->searchCriteriaBuilder
                        ->addFilter('increment_id', $incrementId)
                        ->addFilter('store_id', $this->storeManager->getStore()->getId())
                        ->addFilter('status', OrderManagement::STATUS_RECEIVED)
                        ->create();
                    $records = $this->orderRepository->getList($searchCriteria)->getItems();

                    // redirect to home page if there are an order with increment_id in STATUS_RECEIVED
                    if ($records) {
                        $this->messageManager->addErrorMessage(
                            __('Order information is not available yet. Try again in a couple of minutes.')
                        );
                        $resultRedirect = $this->resultRedirectFactory->create();
                        $resultRedirect->setPath('sales/guest/form');
                        return $resultRedirect;
                    }
                }
            } catch (\Exception $exception) {
                return $proceed($request);
            }
        }

        return $proceed($request);
    }
}
