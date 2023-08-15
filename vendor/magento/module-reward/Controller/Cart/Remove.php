<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Controller\Cart;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Reward\Helper\Data;

/**
 * Remove Reward Points payment
 */
class Remove extends Action implements HttpPostActionInterface
{
    /**
     * Dispatch request
     *
     * Only logged in users can use this functionality,
     * this function checks if user is logged in before all other actions
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_objectManager->get(\Magento\Customer\Model\Session::class)->authenticate()) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }
        return parent::dispatch($request);
    }

    /**
     * Remove Reward Points payment from current quote
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $isAjax = $this->getRequest()->isAjax();
        if (!$this->_objectManager->get(Data::class)->isEnabledOnFront()
            || !$this->_objectManager->get(Data::class)->getHasRates()
        ) {
            if ($isAjax) {
                return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([]);
            }

            return $this->_redirect('customer/account/');
        }

        $successMessage = 'You removed the reward points from this order.';
        $errorMessage = 'Reward points will not be used in this order.';
        $quote = $this->_objectManager->get(Session::class)->getQuote();
        $useRewardPoints = (bool)$quote->getUseRewardPoints();

        if ($useRewardPoints) {
            $quote->setUseRewardPoints(false)->collectTotals()->save();
        }

        if ($isAjax) {
            $response = [
                'errors' => !$useRewardPoints,
                'message' => $useRewardPoints ? $successMessage : $errorMessage,
            ];

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
        }

        if ($useRewardPoints) {
            $this->messageManager->addSuccessMessage(__($successMessage));
        } else {
            $this->messageManager->addErrorMessage(__($errorMessage));
        }

        return $this->_redirect('checkout/cart');
    }
}
