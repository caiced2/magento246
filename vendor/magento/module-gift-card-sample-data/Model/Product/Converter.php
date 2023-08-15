<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardSampleData\Model\Product;

/**
 * Class Converter
 */
class Converter extends \Magento\CatalogSampleData\Model\Product\Converter
{
    /**
     * @var \Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory
     */
    protected $giftcardAmountFactory;

    /**
     * @param \Magento\Catalog\Model\Category\TreeFactory $categoryTreeFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\TreeFactory $categoryResourceTreeFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory
     */
    public function __construct(
        \Magento\Catalog\Model\Category\TreeFactory $categoryTreeFactory,
        \Magento\Catalog\Model\ResourceModel\Category\TreeFactory $categoryResourceTreeFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory $giftcardAmountFactory
    ) {
        $this->giftcardAmountFactory = $giftcardAmountFactory;

        parent::__construct(
            $categoryTreeFactory,
            $categoryResourceTreeFactory,
            $eavConfig,
            $categoryCollectionFactory,
            $attributeCollectionFactory,
            $attrOptionCollectionFactory,
            $productCollectionFactory
        );
    }

    /**
     * @inheritdoc
     */
    protected function convertField(&$data, $field, $value)
    {
        $weight = 1;
        if ($field == 'price') {
            $data = $this->getAmountValues($data, $value);
            return true;
        }
        if ($field == 'format') {
            switch ($value) {
                case 'Virtual':
                    $data['giftcard_type'] = \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL;
                    break;
                case 'Physical':
                    $data['giftcard_type'] = \Magento\GiftCard\Model\Giftcard::TYPE_PHYSICAL;
                    $data['weight'] = $weight;
                    break;
                case 'Combined':
                    $data['giftcard_type'] = \Magento\GiftCard\Model\Giftcard::TYPE_COMBINED;
                    $data['weight'] = $weight;
                    break;
            }
            return true;
        }
        return false;
    }

    /**
     * @param array $data
     * @param mixed $value
     * @return mixed
     */
    protected function getAmountValues($data, $value)
    {
        $prices = $this->getArrayValue($value);
        $i = -1;
        foreach ($prices as $price) {
            if (is_numeric($price)) {
                $amount = $this->giftcardAmountFactory->create(
                    [
                        'data' => [
                            'website_id' => 0,
                            'price' => $price,
                            'delete' => null
                        ]
                    ]
                );

                $data['giftcard_amounts'][++$i] = $amount;
            } elseif ($price == 'Custom') {
                $data['allow_open_amount'] = \Magento\GiftCard\Model\Giftcard::OPEN_AMOUNT_ENABLED;
                $data['open_amount_min'] = min($prices);
                $data['open_amount_max'] = null;
            }
        }

        return $data;
    }
}
