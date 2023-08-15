<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Prepare order data based on current customer quote
 */
class CreateOrder extends \Magento\AdvancedCheckout\Controller\Adminhtml\Index implements HttpGetActionInterface
{
    /**
     * Redirect to order creation page based on current quote
     *
     * @return void|\Magento\Backend\Model\View\Result\Redirect
     * @throws LocalizedException
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->_authorization->isAllowed('Magento_Sales::create')) {
            throw new LocalizedException(__('You do not have access to this.'));
        }
        try {
            $this->_initData();
            if ($this->_redirectFlag) {
                return;
            }
            $activeQuote = $this->getCartModel()->getQuote();
            $activeQuote->assignCustomer($activeQuote->getCustomer());
            $quote = $this->getCartModel()->copyQuote($activeQuote);
            if ($quote->getId()) {
                $session = $this->_objectManager->get(\Magento\Sales\Model\AdminOrder\Create::class)->getSession();
                $session->setQuoteId($quote->getId())
                    ->setStoreId($quote->getStoreId())
                    ->setCustomerId($quote->getCustomerId());
                $itemIds = [];
                foreach ($activeQuote->getAllVisibleItems() as $item) {
                    $itemIds[] = $item->getId();
                }
                $session->setTransferredItems(['cart' => $itemIds]);
            }
            return $resultRedirect->setPath(
                'sales/order_create',
                [
                    'customer_id' => $this->_registry->registry('checkout_current_customer')->getId(),
                    'store_id' => $this->_registry->registry('checkout_current_store')->getId()
                ]
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $this->messageManager->addError(__('An error has occurred. See error log for details.'));
        }

        return $resultRedirect->setPath('checkout/*/error');
    }
}
