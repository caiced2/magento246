<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product\Gallery;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\CopyHandler;
use Magento\Catalog\Model\Product\Gallery\CreateHandler as ProductGalleryCreateHandler;
use Magento\Catalog\Model\Product\Gallery\UpdateHandler;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Json\Helper\Data;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Create handler for staging catalog product gallery
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateHandler extends ProductGalleryCreateHandler implements ExtensionInterface
{
    /**
     * @var UpdateHandler
     */
    private $updateHandler;

    /**
     * @var CopyHandler
     */
    private $copyHandler;

    /**
     * @var VersionHistoryInterface
     */
    private $versionHistory;

    /**
     * @var ReadEntityVersion
     */
    private $readEntityVersion;

    /**
     * @param MetadataPool $metadataPool
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param Gallery $resourceModel
     * @param Data $jsonHelper
     * @param Config $mediaConfig
     * @param Filesystem $filesystem
     * @param Database $fileStorageDb
     * @param UpdateHandler $updateHandler
     * @param CopyHandler|null $copyHandler
     * @param VersionHistoryInterface|null $versionHistory
     * @param ReadEntityVersion|null $readEntityVersion
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
        UpdateHandler $updateHandler,
        ?CopyHandler $copyHandler = null,
        ?VersionHistoryInterface $versionHistory = null,
        ?ReadEntityVersion $readEntityVersion = null
    ) {
        $this->updateHandler = $updateHandler;

        parent::__construct(
            $metadataPool,
            $attributeRepository,
            $resourceModel,
            $jsonHelper,
            $mediaConfig,
            $filesystem,
            $fileStorageDb
        );
        $this->copyHandler = $copyHandler ?? ObjectManager::getInstance()->get(CopyHandler::class);
        $this->versionHistory = $versionHistory ?? ObjectManager::getInstance()->get(VersionHistoryInterface::class);
        $this->readEntityVersion = $readEntityVersion ?? ObjectManager::getInstance()->get(ReadEntityVersion::class);
    }

    /**
     * Execute create handler
     *
     * @param Product $product
     * @param array $arguments
     * @return bool|object
     * @throws LocalizedException
     */
    public function execute($product, $arguments = [])
    {
        if (isset($arguments['origin_in'])) {
            $originId = $arguments['origin_in'];
        } elseif (isset($arguments['copy_origin_in'])) {
            $originId = $arguments['copy_origin_in'];
        } else {
            $originId = $this->versionHistory->getCurrentId();
        }

        if (!in_array($product->getData('created_in'), [VersionManager::MIN_VERSION, $originId], true)) {
            $arguments['original_link_id'] = $this->readEntityVersion->getVersionRowId(
                ProductInterface::class,
                $product->getData($this->metadata->getIdentifierField()),
                $originId
            );
            $this->copyHandler->execute($product, $arguments);
            if (!empty($arguments['is_rollback'])) {
                return $product;
            }
        }
        return $product->isObjectNew()
            ? parent::execute($product, $arguments)
            : $this->updateHandler->execute($product, $arguments);
    }
}
