<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Controller\Index;

use Magento\Catalog\Model\Product\Exception as ProductException;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Add quote items to gift registry action.
 */
class Cart extends \Magento\GiftRegistry\Controller\Index implements HttpPostActionInterface
{
    /**
     * Add quote items to customer active gift registry
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $count = 0;
        try {
            $entity = $this->_initEntity('entity');
            if ($entity && $entity->getId()) {
                $skippedItems = 0;
                $request = $this->getRequest();
                if ($request->getParam('product')) {
                    //Adding from product page
                    $entity->addItem(
                        $request->getParam('product'),
                        new \Magento\Framework\DataObject($request->getParams())
                    );
                    $count = $request->getParam('qty') ? $request->getParam('qty') : 1;
                } else {
                    //Adding from cart
                    $cart = $this->_objectManager->get(\Magento\Checkout\Model\Cart::class);
                    foreach ($cart->getQuote()->getAllVisibleItems() as $item) {
                        if (!$this->_objectManager->get(
                            \Magento\GiftRegistry\Helper\Data::class
                        )->canAddToGiftRegistry(
                            $item
                        )
                        ) {
                            $skippedItems++;
                            continue;
                        }
                        $entity->addItem($item);
                        $count += $item->getQty();
                        $cart->removeItem($item->getId());
                    }
                    $cart->save();
                }

                if ($count > 0) {
                    $this->messageManager->addSuccess(__('%1 item(s) have been added to the gift registry.', $count));
                } else {
                    $this->messageManager->addNotice(__('We have nothing to add to this gift registry.'));
                }
                if (!empty($skippedItems)) {
                    $this->messageManager->addNotice(
                        __("You can't add virtual products, digital products or gift cards to gift registries.")
                    );
                }
            }
        } catch (ProductException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl('*/*'));
            return;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('giftregistry');
            return;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t add shopping cart items to the gift registry right now.'));
        }

        if ($entity->getId()) {
            $this->_redirect('giftregistry/index/items', ['id' => $entity->getId()]);
        } else {
            $this->_redirect('giftregistry');
        }
    }
}
