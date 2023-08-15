<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Model\Observer;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AddBySku
 */
class AddBySku implements ObserverInterface
{
    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * @var CartProvider
     */
    protected $cartProvider;

    /**
     * @param Cart $cart
     * @param CartProvider $backendCartProvider
     * @codeCoverageIgnore
     */
    public function __construct(
        Cart $cart,
        CartProvider $backendCartProvider
    ) {
        $this->_cart = $cart;
        $this->cartProvider = $backendCartProvider;
    }

    /**
     * Check submitted SKU's form the form or from error grid
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $request \Magento\Framework\App\RequestInterface */
        $request = $observer->getRequestModel();
        /* @var $cart Cart */
        $cart = $this->cartProvider->get($observer);

        if (empty($request) || empty($cart)) {
            return;
        }

        $removeFailed = $request->getPost('sku_remove_failed');

        if ($removeFailed || $request->getPost('from_error_grid')) {
            $cart->removeAllAffectedItems();
            if ($removeFailed) {
                return;
            }
        }

        $sku = $observer->getRequestModel()->getPost('remove_sku', false);

        if ($sku) {
            $this->cartProvider->get($observer)->removeAffectedItem($sku);
            return;
        }

        $addBySkuItems = $request->getPost(
            \Magento\AdvancedCheckout\Block\Adminhtml\Sku\AbstractSku::LIST_TYPE,
            []
        );
        $items = $request->getPost('item', []);
        if (!$addBySkuItems) {
            return;
        }
        /* @var $orderCreateModel \Magento\Sales\Model\AdminOrder\Create */
        $orderCreateModel = $observer->getOrderCreateModel();
        $storeId = $orderCreateModel->getSession()->getStoreId();
        $cart->setCurrentStore($storeId);

        foreach ($addBySkuItems as $id => $params) {
            $sku = (string) (isset($params['sku']) ? $params['sku'] : $id);
            $cart->prepareAddProductBySku($sku, $params['qty'], isset($items[$id]) ? $items[$id] : []);
        }
        $cart->saveAffectedProducts($orderCreateModel, false);
        // We have already saved succeeded add by SKU items in saveAffectedItems(). This prevents from duplicate saving.
        $request->setPostValue('item', []);
    }
}
