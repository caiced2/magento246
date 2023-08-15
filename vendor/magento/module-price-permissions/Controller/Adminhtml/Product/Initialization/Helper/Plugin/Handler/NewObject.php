<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper\HandlerInterface;
use Magento\Catalog\Model\Product;

class NewObject implements HandlerInterface
{
    /**
     * Request interface
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * String representation of the default product price
     *
     * @var string
     */
    protected $defaultProductPriceString;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\PricePermissions\Helper\Data $pricePermData
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\PricePermissions\Helper\Data $pricePermData
    ) {
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->defaultProductPriceString = $pricePermData->getDefaultProductPriceString();
    }

    /**
     * Handle new object
     *
     * @param Product $product
     * @return void
     */
    public function handle(Product $product)
    {
        if (!$product->isObjectNew()) {
            return;
        }

        // For new products set default price
        if (!($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE &&
            $product->getPriceType() == \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC)
        ) {
            $product->setPrice((double)$this->defaultProductPriceString);
            // Set default amount for Gift Card product
            if ($product->getTypeId() == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD) {
                $storeId = (int)$this->request->getParam('store', 0);
                $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
                $product->setGiftcardAmounts(
                    [
                        ['website_id' => $websiteId, 'price' => $this->defaultProductPriceString, 'delete' => '']
                    ]
                );
            }
        }
        // Add Msrp default values
        $product->setMsrpDisplayActualPriceType(
            \Magento\Msrp\Model\Product\Attribute\Source\Type\Price::TYPE_USE_CONFIG
        );
    }
}
