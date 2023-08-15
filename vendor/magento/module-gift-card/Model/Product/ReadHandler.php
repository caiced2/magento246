<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\Product;

use Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory as GiftcardAmountFactory;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\GiftCard\Api\Data\GiftcardAmountInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\GiftCard\Model\Giftcard\AmountRepository as GiftcardAmountRepository;
use Magento\Eav\Api\AttributeRepositoryInterface as AttributeRepository;
use Magento\GiftCard\Model\ResourceModel\Db\GetAmountIdsByProduct;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class Read
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReadHandler implements ExtensionInterface
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
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var DirectoryHelper
     */
    protected $directoryHelper;

    /**
     * @param MetadataPool $metadataPool
     * @param GiftcardAmountRepository $giftcardAmountRepository
     * @param AttributeRepository $attributeRepository
     * @param GiftcardAmountFactory $giftcardAmountFactory
     * @param GetAmountIdsByProduct $getAmountIdsByProduct
     * @param DirectoryHelper $directoryHelper
     * @param StoreManager $storeManager
     */
    public function __construct(
        MetadataPool $metadataPool,
        GiftcardAmountRepository $giftcardAmountRepository,
        AttributeRepository $attributeRepository,
        GiftcardAmountFactory $giftcardAmountFactory,
        GetAmountIdsByProduct $getAmountIdsByProduct,
        DirectoryHelper $directoryHelper,
        StoreManager $storeManager
    ) {
        $this->metadataPool = $metadataPool;
        $this->giftcardAmountRepository = $giftcardAmountRepository;
        $this->attributeRepository = $attributeRepository;
        $this->giftcardAmountFactory = $giftcardAmountFactory;
        $this->getAmountIdsByProduct = $getAmountIdsByProduct;
        $this->storeManager = $storeManager;
        $this->directoryHelper = $directoryHelper;
    }

    /**
     * Returns amount info
     *
     * @param GiftcardAmountInterface $amount
     * @return array
     */
    protected function getAmountData(GiftcardAmountInterface $amount)
    {
        $output = [];
        $hydrator = $this->metadataPool->getHydrator(GiftcardAmountInterface::class);
        $amountData = $hydrator->extract($amount);
        if ($amount->getWebsiteId() == 0) {
            $rate = $this->storeManager->getStore()->getBaseCurrency()->getRate(
                $this->directoryHelper->getBaseCurrencyCode()
            );
            if ($rate) {
                $output = $amountData;
                $output['website_value'] = $amount->getValue() / $rate;
            }
        } else {
            $output = $amountData;
            $output['website_value'] = $amount->getValue();
        }
        return $output;
    }

    /**
     * Load giftcard amounts into product
     *
     * @param object $entity
     * @param array $arguments
     * @return object
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $hydrator = $this->metadataPool->getHydrator(ProductInterface::class);
        $attribute = $this->attributeRepository->get($metadata->getEavEntityType(), 'giftcard_amounts');
        $entityData = $hydrator->extract($entity);
        if ($entityData['type_id'] !== Giftcard::TYPE_GIFTCARD) {
            return $entity;
        }
        $websiteId = null;
        if (isset($entityData['store_id'])) {
            $websiteId = $this->storeManager->getStore($entityData['store_id'])->getWebsiteId();
        }
        $amountIds = $this->getAmountIdsByProduct->execute(
            $metadata->getLinkField(),
            $entityData[$metadata->getLinkField()],
            $websiteId
        );
        $data = [];
        $amounts = [];
        foreach ($amountIds as $i => $amountId) {
            $amount = $this->giftcardAmountRepository->get($amountId);
            $amountData = $this->getAmountData($amount);
            if ($amountData) {
                $data[$i] = $amountData;
                $amounts[] = $amount;
            }
        }
        $entityData[$attribute->getAttributeCode()] = $data;
        $entity = $hydrator->hydrate($entity, $entityData);
        $entity->getExtensionAttributes()->setGiftcardAmounts($amounts);
        return $entity;
    }
}
