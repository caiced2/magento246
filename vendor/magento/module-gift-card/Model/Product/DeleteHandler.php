<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Model\Product;

use Magento\Framework\EntityManager\HydratorPool;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\GiftCard\Model\Giftcard\AmountRepository as GiftcardAmountRepository;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\ResourceModel\Db\GetAmountIdsByProduct;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * The giftcard_amount delete handler
 */
class DeleteHandler implements ExtensionInterface
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var GiftcardAmountRepository
     */
    private $giftcardAmountRepository;

    /**
     * @var GetAmountIdsByProduct
     */
    private $getAmountIdsByProduct;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var HydratorPool
     */
    private $hydratorPool;

    /**
     * DeleteHandler constructor.
     *
     * @param MetadataPool $metadataPool
     * @param HydratorPool $hydratorPool
     * @param GiftcardAmountRepository $giftcardAmountRepository
     * @param GetAmountIdsByProduct $getAmountIdsByProduct
     * @param StoreManager $storeManager
     */
    public function __construct(
        MetadataPool $metadataPool,
        HydratorPool $hydratorPool,
        GiftcardAmountRepository $giftcardAmountRepository,
        GetAmountIdsByProduct $getAmountIdsByProduct,
        StoreManager $storeManager
    ) {
        $this->metadataPool = $metadataPool;
        $this->hydratorPool = $hydratorPool;
        $this->giftcardAmountRepository = $giftcardAmountRepository;
        $this->getAmountIdsByProduct = $getAmountIdsByProduct;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute delete gift card amount
     *
     * @param ProductInterface $entity
     * @param array $arguments
     * @return ProductInterface
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = []) : ProductInterface
    {
        if ($entity->getTypeId() !== Giftcard::TYPE_GIFTCARD) {
            return $entity;
        }
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $hydrator = $this->hydratorPool->getHydrator(ProductInterface::class);
        /** @var \Magento\Catalog\Api\Data\ProductInterface $entity */
        $amounts = $entity->getExtensionAttributes()->getGiftcardAmounts();

        $linkedField = $metadata->getLinkField();
        if (!empty($amounts)) {
            $entityData = $hydrator->extract($entity);
            $amountIds = $this->getAmountIdsByProduct->execute(
                $linkedField,
                $entityData[$linkedField],
                $this->storeManager->getStore($entity->getStoreId())->getWebsiteId()
            );
            foreach ($amountIds as $amountId) {
                $amount = $this->giftcardAmountRepository->get($amountId);
                $this->giftcardAmountRepository->delete($amount);
            }
            $entity->setData('giftcard_amounts', []);
        }

        return $entity;
    }
}
