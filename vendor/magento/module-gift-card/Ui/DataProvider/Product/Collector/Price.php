<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Ui\DataProvider\Product\Collector;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductRender\PriceInfoInterface;
use Magento\Catalog\Api\Data\ProductRender\PriceInfoInterfaceFactory;
use Magento\Catalog\Api\Data\ProductRenderInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRender\FormattedPriceInfoBuilder;
use Magento\Catalog\Ui\DataProvider\Product\ProductRenderCollectorInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Collect information about gift card prices
 */
class Price implements ProductRenderCollectorInterface
{
    const PRODUCT_TYPE = "giftcard";

    const KEY_MIN_AMOUNT  = 'min';

    const KEY_MAX_AMOUNT = 'max';

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var PriceInfoInterfaceFactory
     */
    private $priceInfoInterfaceFactory;
    
    /**
     * @var FormattedPriceInfoBuilder
     */
    private $formattedPriceInfoBuilder;

    /**
     * Price constructor.
     * @param PriceCurrencyInterface $priceCurrency
     * @param PriceInfoInterfaceFactory $priceInfoInterfaceFactory
     * @param FormattedPriceInfoBuilder $formattedPriceInfoBuilder
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        PriceInfoInterfaceFactory $priceInfoInterfaceFactory,
        FormattedPriceInfoBuilder $formattedPriceInfoBuilder
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->priceInfoInterfaceFactory = $priceInfoInterfaceFactory;
        $this->formattedPriceInfoBuilder = $formattedPriceInfoBuilder;
    }

    /**
     * @inheritdoc
     */
    public function collect(ProductInterface $product, ProductRenderInterface $productRender)
    {
        if ($product->getTypeId() == self::PRODUCT_TYPE) {
            $priceInfo = $productRender->getPriceInfo();

            if (!$priceInfo) {
                /** @var PriceInfoInterface $priceInfo */
                $priceInfo = $this->priceInfoInterfaceFactory->create();
            }

            $amounts = $this->getGiftCardAmounts($product);

            if ($product->getAllowOpenAmount()) {
                $max = $product->getOpenAmountMax();
                $min = $product->getOpenAmountMin();
            } elseif (!empty($amounts)) {
                $max = max($amounts);
                $min = min($amounts);
            } else {
                $max = $min = 0;
            }

            //Override range of prices for giftcard
            $priceInfo->setMinimalPrice($min);
            $priceInfo->setMaxPrice($max);

            $this->formattedPriceInfoBuilder->build(
                $priceInfo,
                $productRender->getStoreId(),
                $productRender->getCurrencyCode()
            );
            
            $productRender->setPriceInfo($priceInfo);
        }
    }

    /**
     * Gather gift card amounts by scope (website)
     *
     * @param Product $product
     * @return array
     */
    private function getGiftCardAmounts(Product $product)
    {
        $amounts = [];

        foreach ((array) $product->getGiftcardAmounts() as $amount) {
            $amounts[] = $amount['website_value'];
        }

        return $amounts;
    }
}
