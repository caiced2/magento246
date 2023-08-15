<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model\Product;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Attribute;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Api\Data\GiftcardAmountInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\Giftcard\AmountRepository;
use Magento\GiftCard\Model\Product\SaveHandler;
use Magento\GiftCard\Model\ResourceModel\Db\GetAmountIdsByProduct;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveHandlerTest extends TestCase
{
    /**
     * @var SaveHandler
     */
    private $saveHandler;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var GetAmountIdsByProduct|MockObject
     */
    private $getAmountIdsByProductMock;

    /**
     * @var AmountRepository|MockObject
     */
    private $giftcardAmountRepositoryMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->getAmountIdsByProductMock = $this->createMock(
            GetAmountIdsByProduct::class
        );
        $this->giftcardAmountRepositoryMock = $this->createMock(AmountRepository::class);
        $attributeRepositoryMock = $this->getMockForAbstractClass(ProductAttributeRepositoryInterface::class);
        $attributeMock = $this->createMock(Attribute::class);
        $attributeRepositoryMock->method('get')->willReturn($attributeMock);
        $attributeMock->method('getAttributeId')->willReturn('attributeId');
        $this->saveHandler = $objectManager->getObject(
            SaveHandler::class,
            [
                'metadataPool' => $this->metadataPoolMock,
                'storeManager' => $this->storeManagerMock,
                'getAmountIdsByProduct' => $this->getAmountIdsByProductMock,
                'giftcardAmountRepository' => $this->giftcardAmountRepositoryMock,
                'attributeRepository' => $attributeRepositoryMock,
            ]
        );
    }

    /**
     * @param GiftcardAmountInterface[] $amounts
     * @param int $deleteCallNum
     * @param int $saveCallNum
     * @param array $amountIds
     * @dataProvider executeDataProvider
     * @throws \Exception
     */
    public function testExecute($amounts, int $deleteCallNum = 0, int $saveCallNum = 0, $amountIds = [1])
    {
        $giftCardAmounts = ['test' => []];
        $entityData = ['row_id' => 1];
        $storeMock = $this->getMockForAbstractClass(StoreInterface::class);
        $productMock = $this->createMock(Product::class);
        $metadataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class);
        $hydratorMock = $this->getMockForAbstractClass(HydratorInterface::class);
        $productMock->expects($this->once())->method('getTypeId')->willReturn(Giftcard::TYPE_GIFTCARD);
        $extensionAttributesMock = $this->getMockBuilder(ProductExtensionInterface::class)
            ->setMethods(['getGiftcardAmounts'])
            ->getMockForAbstractClass();
        $this->metadataPoolMock->expects($this->once())->method('getMetadata')->willReturn($metadataMock);
        $metadataMock->expects($this->once())->method('getLinkField')->willReturn('row_id');
        $this->metadataPoolMock
            ->expects($this->once())
            ->method('getHydrator')
            ->with(ProductInterface::class)
            ->willReturn($hydratorMock);
        $hydratorMock->method('extract')->with($productMock)->willReturn($entityData);
        $productMock->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributesMock);
        $extensionAttributesMock->expects($this->once())->method('getGiftcardAmounts')->willReturn($amounts);
        $productMock->method('getData')->with('giftcard_amounts')->willReturn($giftCardAmounts);
        $this->storeManagerMock->method('getStore')->willReturn($storeMock);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $this->getAmountIdsByProductMock->method('execute')->with('row_id', 1, 1)->willReturn($amountIds);
        $this->giftcardAmountRepositoryMock->method('get')->willReturn($this->getGiftCardAmountMock());
        $this->giftcardAmountRepositoryMock->expects($this->exactly($saveCallNum))->method('save');
        $this->giftcardAmountRepositoryMock->expects($this->exactly($deleteCallNum))->method('delete');
        $this->saveHandler->execute($productMock);
    }

    public function executeDataProvider()
    {
        $giftcardAmountMockWithDataA = $this->getGiftCardAmountMock();
        $giftcardAmountMockWithDataA->method('getData')->willReturn(['value' => 30]);
        $giftcardAmountMockWithDataB = $this->getGiftCardAmountMock();
        $giftcardAmountMockWithDataB->method('getData')->willReturn(['value' => 40]);
        $giftcardAmountMockNoDataC = $this->getGiftCardAmountMock();
        $giftcardAmountMockNoDataC->method('getData')->willReturn([]);
        $giftcardAmountMockNoDataD = $this->getGiftCardAmountMock();
        $giftcardAmountMockNoDataD->method('getData')->willReturn([]);
        return [
            'no amounts entity' => [[], 0, 0],
            'one amount entity' => [[$giftcardAmountMockWithDataA], 1, 1],
            'one amount no data entity' => [[$giftcardAmountMockNoDataC], 1, 0],
            'two amounts entity' => [[$giftcardAmountMockWithDataA, $giftcardAmountMockWithDataB], 0, 2, []],
            'two amounts, one with, one without data entity' => [
                [$giftcardAmountMockWithDataA, $giftcardAmountMockNoDataC],
                1,
                1
            ],
            'two amounts without data entity' => [
                [$giftcardAmountMockNoDataC, $giftcardAmountMockNoDataD],
                1,
                0
            ],
        ];
    }

    /**
     * Get GiftCardAmountInterface mock object
     *
     * @return MockObject
     */
    private function getGiftCardAmountMock(): MockObject
    {
        return $this->getMockBuilder(GiftcardAmountInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'setData', 'unsetData'])
            ->getMockForAbstractClass();
    }
}
