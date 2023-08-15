<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Unapplier for store credit.
 */
class Unapply extends Action implements HttpPostActionInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @param Context $context
     * @param CartRepositoryInterface $cartRepository
     * @param UserContextInterface $userContext
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        UserContextInterface $userContext
    ) {
        $this->cartRepository = $cartRepository;
        $this->userContext = $userContext;
        parent::__construct($context);
    }

    /**
     * Remove Store Credit from current quote.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        if ($this->userContext->getUserType() == UserContextInterface::USER_TYPE_GUEST) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('customer/account/');
        }

        $cartId = $this->getRequest()->getParam('cartId');

        /** @var CartInterface $quote */
        $quote = $this->cartRepository->get($cartId);
        if ($this->userContext->getUserId() !== ((int) $quote->getCustomer()->getId())) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $result->forward('noroute');
        }

        $this->unapply($quote);

        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData(true);
        return $result;
    }

    /**
     * Unapply store credit.
     *
     * @param CartInterface $quote
     * @return void
     */
    private function unapply(CartInterface $quote): void
    {
        $quote->setUseCustomerBalance(false);
        $quote->collectTotals();
        $quote->save();
    }
}
