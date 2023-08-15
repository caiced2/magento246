<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Controller\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\CustomerBalance\Helper\Data as CustomerBalanceHelper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Change Store Credit state controller.
 */
class Change implements ActionInterface, HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var CustomerBalanceHelper
     */
    private $customerBalanceHelper;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @param RequestInterface $request
     * @param RedirectInterface $redirect
     * @param ResultFactory $resultFactory
     * @param CustomerBalanceHelper $customerBalanceHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     */
    public function __construct(
        RequestInterface $request,
        RedirectInterface $redirect,
        ResultFactory $resultFactory,
        CustomerBalanceHelper $customerBalanceHelper,
        CartRepositoryInterface $quoteRepository,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession
    ) {
        $this->request = $request;
        $this->redirect = $redirect;
        $this->resultFactory = $resultFactory;
        $this->customerBalanceHelper = $customerBalanceHelper;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    /**
     * Change 'Use Customer Balance' mode for current Quote by provided parameter.
     *
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if ($this->customerSession->authenticate() && $this->customerBalanceHelper->isEnabled()) {
            $useCustomerBalance = (bool)$this->request->getPost('useBalance');
            $quote = $this->checkoutSession->getQuote();
            $quote->setUseCustomerBalance($useCustomerBalance);
            $this->quoteRepository->save($quote->collectTotals());
            $resultRedirect->setUrl($this->redirect->getRefererUrl());
        } else {
            $resultRedirect->setPath('customer/account/');
        }

        return $resultRedirect;
    }
}
