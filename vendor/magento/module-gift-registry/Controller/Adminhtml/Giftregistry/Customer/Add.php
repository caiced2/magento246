<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Adminhtml\Giftregistry\Customer;

use Magento\Framework\Exception\LocalizedException;

class Add extends \Magento\GiftRegistry\Controller\Adminhtml\Giftregistry\Customer
{
    /**
     * Add quote items to gift registry
     *
     * @return void
     */
    public function execute()
    {
        if ($quoteIds = $this->getRequest()->getParam('products')) {
            $model = $this->_initEntity();
            try {
                $skippedItems = $model->addQuoteItems($quoteIds);
                if (count($quoteIds) - $skippedItems > 0) {
                    $this->messageManager->addSuccess(__('Shopping cart items have been added to gift registry.'));
                }
                if ($skippedItems) {
                    $this->messageManager->addNotice(
                        __('Virtual, Downloadable, and virtual Gift Card products cannot be added to gift registries.')
                    );
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('adminhtml/*/edit', ['id' => $model->getId()]);
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t add shopping cart items to the gift registry right now.')
                );
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
        }
        $this->_redirect('adminhtml/*/edit', ['id' => $model->getId()]);
    }
}
