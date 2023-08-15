<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\ResourceModel\Db;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\App\ResourceConnection as AppResource;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class GetAmountIdsByProduct
 */
class GetAmountIdsByProduct
{
    /**
     * @var AppResource
     */
    protected $appResource;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @param AppResource $appResource
     * @param ProductAttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        AppResource $appResource,
        ProductAttributeRepositoryInterface $attributeRepository
    ) {
        $this->appResource = $appResource;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @param string $linkField
     * @param int $productId
     * @param null $websiteId
     * @return array
     */
    public function execute($linkField, $productId, $websiteId = null)
    {
        $attribute = $this->attributeRepository->get('giftcard_amounts');
        $connection = $this->appResource->getConnection();
        $select = $connection->select()->from(
            $this->appResource->getTableName('magento_giftcard_amount'),
            ['value_id']
        )->where($linkField . ' = :product_id')
        ->where('attribute_id = :attribute_id');

        $bind = [
            'product_id' => $productId,
            'attribute_id' => $attribute->getAttributeId()
        ];

        if ($websiteId) {
            $select->where('website_id IN (0, :website_id)');
            $bind['website_id'] = $websiteId;
        } else {
            $select->where('website_id = 0');
        }

        return $connection->fetchAll($select, $bind);
    }
}
