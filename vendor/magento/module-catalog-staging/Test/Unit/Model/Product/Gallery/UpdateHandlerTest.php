<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Product\Gallery;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\CopyHandler;
use Magento\Catalog\Model\Product\Gallery\DeleteHandler;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\CatalogStaging\Model\Product\Gallery\UpdateHandler;
use Magento\Eav\Model\ResourceModel\AttributeValue;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Filesystem;
use Magento\Framework\Json\Helper\Data;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test update handler for staging catalog product gallery
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateHandlerTest extends TestCase
{
    /**
     * @var ProductAttributeRepositoryInterface|MockObject
     */
    private $attributeRepository;

    /**
     * @var Config|MockObject
     */
    private $mediaConfig;

    /**
     * @var CopyHandler|MockObject
     */
    private $copyHandler;

    /**
     * @var DeleteHandler|MockObject
     */
    private $deleteHandler;

    /**
     * @var ReadHandler|MockObject
     */
    private $readHandler;

    /**
     * @var ReadEntityVersion|MockObject
     */
    private $readEntityVersion;

    /**
     * @var UpdateHandler
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $metadataPool = $this->createMock(MetadataPool::class);
        $metadata = $this->createConfiguredMock(EntityMetadataInterface::class, ['getIdentifierField' => 'id']);
        $metadataPool->method('getMetadata')
            ->willReturn($metadata);
        $this->attributeRepository = $this->createMock(ProductAttributeRepositoryInterface::class);
        $resourceModel = $this->createMock(Gallery::class);
        $jsonHelper = $this->createMock(Data::class);
        $this->mediaConfig = $this->createMock(Config::class);
        $filesystem = $this->createMock(Filesystem::class);
        $fileStorageDb = $this->createMock(Database::class);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $attributeValue = $this->createMock(AttributeValue::class);
        $this->copyHandler = $this->createMock(CopyHandler::class);
        $this->deleteHandler = $this->createMock(DeleteHandler::class);
        $this->readHandler = $this->createMock(ReadHandler::class);
        $this->readEntityVersion = $this->createMock(ReadEntityVersion::class);

        $this->model = new UpdateHandler(
            $metadataPool,
            $this->attributeRepository,
            $resourceModel,
            $jsonHelper,
            $this->mediaConfig,
            $filesystem,
            $fileStorageDb,
            $storeManager,
            $attributeValue,
            $this->copyHandler,
            $this->deleteHandler,
            $this->readHandler,
            $this->readEntityVersion,
        );
    }

    /**
     * Test that delete, copy and read handlers are executed for rollback product
     */
    public function testRollbackProduct()
    {
        $product = $this->createMock(Product::class);
        $attributeCodes = [
            'image',
            'image_label',
            'small_image',
            'small_image_label',
            'thumbnail',
            'thumbnail_label',
        ];
        $originalLinkId = 1;
        $arguments = [
            'is_rollback' => true,
            'copy_origin_in' => 1,
            'media_attribute_codes' => $attributeCodes,
            'original_link_id' => $originalLinkId,
        ];
        $this->mediaConfig->expects($this->once())
            ->method('getMediaAttributeCodes')
            ->willReturn(
                [
                    'image',
                    'small_image',
                    'thumbnail',
                ]
            );
        $this->readEntityVersion->expects($this->once())
            ->method('getVersionRowId')
            ->willReturn($originalLinkId);
        $this->deleteHandler->expects($this->once())
            ->method('execute')
            ->with($product, $arguments);
        $this->copyHandler->expects($this->once())
            ->method('execute')
            ->with($product, $arguments);
        $this->readHandler->expects($this->once())
            ->method('execute')
            ->with($product, $arguments);
        $this->model->execute($product, $arguments);
    }

    /**
     * Test that delete, copy and read handlers are not executed for current product
     *
     * @dataProvider argumentsDataProvider
     */
    public function testCurrentProduct(array $arguments)
    {
        $product = $this->createMock(Product::class);
        $attribute = $this->createMock(ProductAttributeInterface::class);
        $this->attributeRepository->method('get')
            ->willReturn($attribute);
        $this->readEntityVersion->expects($this->never())
            ->method('getVersionRowId');
        $this->deleteHandler->expects($this->never())
            ->method('execute');
        $this->copyHandler->expects($this->never())
            ->method('execute');
        $this->readHandler->expects($this->never())
            ->method('execute');
        $this->model->execute($product, $arguments);
    }

    /**
     * @return array
     */
    public function argumentsDataProvider(): array
    {
        return [
            [
                [
                    'is_rollback' => false,
                    'copy_origin_in' => 1,
                ]
            ],
            [
                [
                    'is_rollback' => true,
                ]
            ],
            [
                [
                    'is_rollback' => true,
                    'copy_origin_in' => '',
                ]
            ],
        ];
    }
}
