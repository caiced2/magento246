<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Product;

use Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory as GiftcardAmountFactory;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\GiftCard\Model\Giftcard\AmountRepository as GiftcardAmountRepository;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\ResourceModel\Db\GetAmountIdsByProduct;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface as AttributeRepository;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class Save
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveHandler implements ExtensionInterface
{
    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var GiftcardAmountRepository
     */
    protected $giftcardAmountRepository;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * @var GiftcardAmountFactory
     */
    protected $giftcardAmountFactory;

    /**
     * @var GetAmountIdsByProduct
     */
    protected $getAmountIdsByProduct;

    /**
     * @var ReadHandler
     */
    protected $readHandler;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @param MetadataPool $metadataPool
     * @param GiftcardAmountRepository $giftcardAmountRepository
     * @param AttributeRepository $attributeRepository
     * @param GiftcardAmountFactory $giftcardAmountFactory
     * @param GetAmountIdsByProduct $getAmountIdsByProduct
     * @param ReadHandler $readHandler
     * @param StoreManager $storeManager
     */
    public function __construct(
        MetadataPool $metadataPool,
        GiftcardAmountRepository $giftcardAmountRepository,
        AttributeRepository $attributeRepository,
        GiftcardAmountFactory $giftcardAmountFactory,
        GetAmountIdsByProduct $getAmountIdsByProduct,
        ReadHandler $readHandler,
        StoreManager $storeManager
    ) {
        $this->metadataPool = $metadataPool;
        $this->readHandler = $readHandler;
        $this->giftcardAmountRepository = $giftcardAmountRepository;
        $this->attributeRepository = $attributeRepository;
        $this->giftcardAmountFactory = $giftcardAmountFactory;
        $this->getAmountIdsByProduct = $getAmountIdsByProduct;
        $this->storeManager = $storeManager;
    }

    /**
     * Remove obsolete amounts
     *
     * @param string $targetField
     * @param int $value
     * @param int|null $websiteId
     * @return void
     */
    protected function removeObsoleteAmounts($targetField, $value, $websiteId)
    {
        $amountIds = $this->getAmountIdsByProduct->execute($targetField, $value, $websiteId);
        foreach ($amountIds as $amountId) {
            $amount = $this->giftcardAmountRepository->get($amountId);
            $this->giftcardAmountRepository->delete($amount);
        }
    }

    /**
     * Save new amounts
     *
     * @param string $targetField
     * @param int $value
     * @param \Magento\GiftCard\Api\Data\GiftcardAmountInterface[] $amounts
     * @return void
     */
    protected function saveNewAmounts($targetField, $value, $amounts)
    {
        $attribute = $this->attributeRepository->get('giftcard_amounts');

        /** @var \Magento\GiftCard\Api\Data\GiftcardAmountInterface $amount */
        foreach ($amounts as $amount) {
            if ($amount->getData()) {   // The amount has data to save
                $amount->setData($targetField, $value);
                $amount->setAttributeId($attribute->getAttributeId());
                $amount->unsetData('value_id');

                $this->giftcardAmountRepository->save($amount);
            }
        }
    }

    /**
     * Execute save
     *
     * @param object $entity
     * @param array $arguments
     * @return \Magento\Catalog\Api\Data\ProductInterface|object
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        if ($entity->getTypeId() !== Giftcard::TYPE_GIFTCARD) {
            return $entity;
        }
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $hydrator = $this->metadataPool->getHydrator(ProductInterface::class);
        /** @var \Magento\Catalog\Api\Data\ProductInterface $entity */
        $amounts = $entity->getExtensionAttributes()->getGiftcardAmounts();

        $linkedField = $metadata->getLinkField();
        if (!empty($amounts)) {
            $entityData = $hydrator->extract($entity);
            $this->removeObsoleteAmounts(
                $linkedField,
                $entityData[$linkedField],
                $this->storeManager->getStore($entity->getStoreId())->getWebsiteId()
            );
            $this->saveNewAmounts(
                $linkedField,
                $entityData[$linkedField],
                $amounts
            );
            $entity = $this->readHandler->execute($entity);
        }

        return $entity;
    }
}
