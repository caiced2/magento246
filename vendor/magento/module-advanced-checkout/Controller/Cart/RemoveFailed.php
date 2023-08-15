<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Cart;

use Magento\Framework\App\Action\Context;

class RemoveFailed extends \Magento\AdvancedCheckout\Controller\Cart
{
    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @codeCoverageIgnore
     * @param Context $context
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Url\DecoderInterface $urlDecoder
    ) {
        parent::__construct($context);
        $this->urlDecoder = $urlDecoder;
    }

    /**
     * Remove failed items from storage
     *
     * @return void
     */
    public function execute()
    {
        $removed = $this->_getFailedItemsCart()->removeAffectedItem(
            $this->urlDecoder->decode($this->getRequest()->getParam('sku'))
        );

        if ($removed) {
            $this->messageManager->addSuccess(__('You removed the item.'));
        }

        $this->_redirect('checkout/cart');
    }
}
