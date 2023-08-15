<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Quote\Item;

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item\CartItemProcessorInterface;

class CartItemProcessor implements CartItemProcessorInterface
{
    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\GiftCard\Model\Giftcard\OptionFactory
     */
    protected $giftCardOptionFactory;

    /**
     * @var \Magento\Quote\Model\Quote\ProductOptionFactory
     */
    protected $productOptionFactory;

    /**
     * @var \Magento\Quote\Api\Data\ProductOptionExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\GiftCard\Model\Giftcard\OptionFactory $giftCardOptionFactory
     * @param \Magento\Quote\Model\Quote\ProductOptionFactory $productOptionFactory
     * @param \Magento\Quote\Api\Data\ProductOptionExtensionFactory $extensionFactory
     */
    public function __construct(
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\GiftCard\Model\Giftcard\OptionFactory $giftCardOptionFactory,
        \Magento\Quote\Model\Quote\ProductOptionFactory $productOptionFactory,
        \Magento\Quote\Api\Data\ProductOptionExtensionFactory $extensionFactory
    ) {
        $this->objectFactory = $objectFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->giftCardOptionFactory = $giftCardOptionFactory;
        $this->productOptionFactory = $productOptionFactory;
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToBuyRequest(CartItemInterface $cartItem)
    {
        $productOptions = $cartItem->getProductOption();
        if ($productOptions
            && $productOptions->getExtensionAttributes()
            && $productOptions->getExtensionAttributes()->getGiftcardItemOption()
        ) {
            $options = $productOptions->getExtensionAttributes()->getGiftcardItemOption()->getData();
            if (is_array($options)) {
                $requestData = [];
                foreach ($options as $key => $value) {
                    $requestData[$key] = $value;
                }
                return $this->objectFactory->create($requestData);
            }
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function processOptions(CartItemInterface $cartItem)
    {
        $options = $cartItem->getOptions();
        if (is_array($options)) {
            $optionsArray = [];
            /** @var \Magento\Quote\Model\Quote\Item\Option  $option */
            foreach ($options as $option) {
                $optionsArray[$option->getCode()] = $option->getValue();
            }
            $giftOptionDataObject = $this->giftCardOptionFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $giftOptionDataObject,
                $optionsArray,
                \Magento\GiftCard\Api\Data\GiftCardOptionInterface::class
            );
            /** set gift card product option */
            $productOption = $cartItem->getProductOption()
                ? $cartItem->getProductOption()
                : $this->productOptionFactory->create();
            /** @var  \Magento\Quote\Api\Data\ProductOptionExtensionInterface $extensibleAttribute */
            $extensibleAttribute =  $productOption->getExtensionAttributes()
                ? $productOption->getExtensionAttributes()
                : $this->extensionFactory->create();

            $extensibleAttribute->setGiftcardItemOption($giftOptionDataObject);
            $productOption->setExtensionAttributes($extensibleAttribute);
            $cartItem->setProductOption($productOption);
        };
        return $cartItem;
    }
}
