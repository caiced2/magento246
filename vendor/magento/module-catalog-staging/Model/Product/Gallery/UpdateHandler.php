<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model\Product\Gallery;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\CopyHandler;
use Magento\Catalog\Model\Product\Gallery\DeleteHandler;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Eav\Model\ResourceModel\AttributeValue;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Filesystem;
use Magento\Framework\Json\Helper\Data;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Update handler for staging catalog product gallery
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateHandler extends \Magento\Catalog\Model\Product\Gallery\UpdateHandler
{
    /**
     * @var CopyHandler
     */
    private $copyHandler;

    /**
     * @var DeleteHandler
     */
    private $deleteHandler;

    /**
     * @var ReadHandler
     */
    private $readHandler;

    /**
     * @var ReadEntityVersion
     */
    private $readEntityVersion;

    /**
     * @var string[]
     */
    private $mediaAttributesWithLabels = [
        'image',
        'small_image',
        'thumbnail'
    ];

    /**
     * @param MetadataPool $metadataPool
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param Gallery $resourceModel
     * @param Data $jsonHelper
     * @param Config $mediaConfig
     * @param Filesystem $filesystem
     * @param Database $fileStorageDb
     * @param StoreManagerInterface $storeManager
     * @param AttributeValue $attributeValue
     * @param CopyHandler $copyHandler
     * @param DeleteHandler $deleteHandler
     * @param ReadHandler $readHandler
     * @param ReadEntityVersion $readEntityVersion
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        MetadataPool $metadataPool,
        ProductAttributeRepositoryInterface $attributeRepository,
        Gallery $resourceModel,
        Data $jsonHelper,
        Config $mediaConfig,
        Filesystem $filesystem,
        Database $fileStorageDb,
        StoreManagerInterface $storeManager,
        AttributeValue $attributeValue,
        CopyHandler $copyHandler,
        DeleteHandler $deleteHandler,
        ReadHandler $readHandler,
        ReadEntityVersion $readEntityVersion
    ) {
        parent::__construct(
            $metadataPool,
            $attributeRepository,
            $resourceModel,
            $jsonHelper,
            $mediaConfig,
            $filesystem,
            $fileStorageDb,
            $storeManager,
            $attributeValue
        );
        $this->copyHandler = $copyHandler;
        $this->deleteHandler = $deleteHandler;
        $this->readHandler = $readHandler;
        $this->readEntityVersion = $readEntityVersion;
    }

    /**
     * Update product media gallery
     *
     * @param Product $product
     * @param array $arguments
     * @return object
     */
    public function execute($product, $arguments = [])
    {
        if (!empty($arguments['is_rollback']) && !empty($arguments['copy_origin_in'])) {
            $arguments['media_attribute_codes'] = $this->getMediaAttributeCodes();
            $arguments['original_link_id'] = $this->readEntityVersion->getVersionRowId(
                ProductInterface::class,
                $product->getData($this->metadata->getIdentifierField()),
                $arguments['copy_origin_in']
            );
            $this->deleteHandler->execute($product, $arguments);
            $this->copyHandler->execute($product, $arguments);
            // reload gallery data as new value IDs are auto generated
            $this->readHandler->execute($product, $arguments);
            return $product;
        }
        return parent::execute($product, $arguments);
    }

    /**
     * Get all media attributes codes including their corresponding labels
     *
     * @return array
     */
    private function getMediaAttributeCodes(): array
    {
        $attributeCodes = [];
        foreach ($this->mediaConfig->getMediaAttributeCodes() as $attributeCode) {
            $attributeCodes[] = $attributeCode;
            if (in_array($attributeCode, $this->mediaAttributesWithLabels)) {
                $attributeCodes[] = $attributeCode . '_label';
            }
        }
        return $attributeCodes;
    }
}
