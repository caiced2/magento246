<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Controller\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Reward\Helper\Data as RewardHelper;
use Magento\Reward\Model\PaymentDataImporter;

/**
 * Change Reward Points state controller.
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
     * @var RewardHelper
     */
    private $rewardHelper;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var PaymentDataImporter
     */
    private $paymentDataImporter;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @param RequestInterface $request
     * @param RedirectInterface $redirect
     * @param ResultFactory $resultFactory
     * @param RewardHelper $rewardHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param PaymentDataImporter $paymentDataImporter
     */
    public function __construct(
        RequestInterface $request,
        RedirectInterface $redirect,
        ResultFactory $resultFactory,
        RewardHelper $rewardHelper,
        CartRepositoryInterface $quoteRepository,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        PaymentDataImporter $paymentDataImporter
    ) {
        $this->request = $request;
        $this->redirect = $redirect;
        $this->resultFactory = $resultFactory;
        $this->rewardHelper = $rewardHelper;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->paymentDataImporter = $paymentDataImporter;
    }

    /**
     * Change 'Use Reward Points' mode for current Quote by provided parameter.
     *
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if ($this->customerSession->authenticate()
            && $this->rewardHelper->isEnabledOnFront()
            && $this->rewardHelper->getHasRates()
        ) {
            $useRewardPoints = (bool)$this->request->getPost('useBalance');
            $quote = $this->checkoutSession->getQuote();
            $this->paymentDataImporter->import($quote, $quote->getPayment(), $useRewardPoints);
            $this->quoteRepository->save($quote->collectTotals());
            $resultRedirect->setUrl($this->redirect->getRefererUrl());
        } else {
            $resultRedirect->setPath('customer/account/');
        }

        return $resultRedirect;
    }
}
