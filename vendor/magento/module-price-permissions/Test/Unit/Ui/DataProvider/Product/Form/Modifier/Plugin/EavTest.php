<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricePermissions\Test\Unit\Ui\DataProvider\Product\Form\Modifier\Plugin;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav as EavModifier;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as GiftCardType;
use Magento\GiftCard\Model\Giftcard\Amount as GiftCardAmount;
use Magento\PricePermissions\Observer\ObserverData;
use Magento\PricePermissions\Ui\DataProvider\Product\Form\Modifier\Plugin\Eav as EavModifierPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EavTest extends TestCase
{
    /**
     * @var EavModifierPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ObserverData|MockObject
     */
    private $observerDataMock;

    /**
     * @var ArrayManager|MockObject
     */
    private $arrayManagerMock;

    /**
     * @var LocatorInterface|MockObject
     */
    private $locatorMock;

    /**
     * @var EavModifier|MockObject
     */
    private $subjectMock;

    /**
     * @var ProductAttributeInterface|MockObject
     */
    private $attributeMock;

    /**
     * @var ProductInterface|MockObject
     */
    private $productMock;

    protected function setUp(): void
    {
        $this->observerDataMock = $this->getMockBuilder(ObserverData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->arrayManagerMock = $this->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->locatorMock = $this->getMockBuilder(LocatorInterface::class)
            ->getMockForAbstractClass();
        $this->attributeMock = $this->getMockBuilder(ProductAttributeInterface::class)
            ->getMockForAbstractClass();
        $this->subjectMock = $this->getMockBuilder(EavModifier::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['isObjectNew'])
            ->getMockForAbstractClass();

        $this->arrayManagerMock->expects(static::any())
            ->method('merge')
            ->willReturnArgument(1);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            EavModifierPlugin::class,
            [
                'observerData' => $this->observerDataMock,
                'arrayManager' => $this->arrayManagerMock,
                'locator' => $this->locatorMock,
            ]
        );
    }

    public function testAfterSetupAttributeMetaStatusFieldCanNotEdit()
    {
        $result = ['code' => 'value'];

        $this->attributeMock->expects(static::atLeastOnce())
            ->method('getAttributeCode')
            ->willReturn(ProductAttributeInterface::CODE_STATUS);
        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('isCanEditProductStatus')
            ->willReturn(false);

        $this->assertEquals(
            $result,
            $this->plugin->afterSetupAttributeMeta(
                $this->subjectMock,
                $result,
                $this->attributeMock,
                'test_group_code',
                1
            )
        );
    }

    public function testAfterSetupAttributeMetaPriceFieldCanRead()
    {
        $result = ['code' => 'value'];

        $this->attributeMock->expects(static::atLeastOnce())
            ->method('getAttributeCode')
            ->willReturn(ProductAttributeInterface::CODE_TIER_PRICE);
        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('isCanReadProductPrice')
            ->willReturn(true);

        $this->assertEquals(
            $result,
            $this->plugin->afterSetupAttributeMeta(
                $this->subjectMock,
                $result,
                $this->attributeMock,
                'test_group_code',
                1
            )
        );
    }

    public function testAfterSetupAttributeMetaPriceFieldCanNotRead()
    {
        $this->attributeMock->expects(static::atLeastOnce())
            ->method('getAttributeCode')
            ->willReturn(ProductAttributeInterface::CODE_TIER_PRICE);
        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('isCanReadProductPrice')
            ->willReturn(false);
        $this->locatorMock->expects(static::atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->assertEquals(
            [],
            $this->plugin->afterSetupAttributeMeta(
                $this->subjectMock,
                ['code' => 'value'],
                $this->attributeMock,
                'test_group_code',
                1
            )
        );
    }

    public function testAfterSetupAttributeContainerMetaPriceFieldCanRead()
    {
        $result = ['code' => 'value'];

        $this->attributeMock->expects(static::atLeastOnce())
            ->method('getAttributeCode')
            ->willReturn(ProductAttributeInterface::CODE_TIER_PRICE);
        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('isCanReadProductPrice')
            ->willReturn(true);

        $this->assertEquals(
            $result,
            $this->plugin->afterSetupAttributeContainerMeta(
                $this->subjectMock,
                $result,
                $this->attributeMock
            )
        );
    }

    public function testAfterSetupAttributeContainerMetaPriceFieldCanNotRead()
    {
        $this->attributeMock->expects(static::atLeastOnce())
            ->method('getAttributeCode')
            ->willReturn(ProductAttributeInterface::CODE_TIER_PRICE);
        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('isCanReadProductPrice')
            ->willReturn(false);
        $this->locatorMock->expects(static::atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->assertEquals(
            [],
            $this->plugin->afterSetupAttributeContainerMeta(
                $this->subjectMock,
                ['code' => 'value'],
                $this->attributeMock
            )
        );
    }

    public function testAfterSetupAttributeDataNoProductException()
    {
        $result = ['code' => 'value'];

        $this->locatorMock->expects(static::once())
            ->method('getProduct')
            ->willThrowException(new NoSuchEntityException());

        $this->assertEquals(
            $result,
            $this->plugin->afterSetupAttributeData(
                $this->subjectMock,
                $result,
                $this->attributeMock
            )
        );
    }

    public function testAfterSetupAttributeDataStatusFieldCanNotEdit()
    {
        $this->locatorMock->expects(static::atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->attributeMock->expects(static::atLeastOnce())
            ->method('getAttributeCode')
            ->willReturn(ProductAttributeInterface::CODE_STATUS);
        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('isCanEditProductStatus')
            ->willReturn(false);
        $this->productMock->expects(static::atLeastOnce())
            ->method('isObjectNew')
            ->willReturn(true);

        $this->assertEquals(
            ProductStatus::STATUS_DISABLED,
            $this->plugin->afterSetupAttributeData(
                $this->subjectMock,
                100,
                $this->attributeMock
            )
        );
    }

    public function testAfterSetupAttributeDataPriceFieldCanNotEdit()
    {
        $defaultPrice = 100;

        $this->locatorMock->expects(static::atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);
        $this->attributeMock->expects(static::atLeastOnce())
            ->method('getFrontendInput')
            ->willReturn('price');
        $this->attributeMock->expects(static::atLeastOnce())
            ->method('getAttributeCode')
            ->willReturn(GiftCardAmount::KEY_VALUE);
        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('isCanEditProductPrice')
            ->willReturn(false);
        $this->productMock->expects(static::atLeastOnce())
            ->method('isObjectNew')
            ->willReturn(true);
        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('getDefaultProductPriceString')
            ->willReturn($defaultPrice);
        $this->productMock->expects(static::atLeastOnce())
            ->method('getTypeId')
            ->willReturn(GiftCardType::TYPE_GIFTCARD);

        $this->assertEquals(
            $defaultPrice,
            $this->plugin->afterSetupAttributeData(
                $this->subjectMock,
                $defaultPrice,
                $this->attributeMock
            )
        );
    }
}
