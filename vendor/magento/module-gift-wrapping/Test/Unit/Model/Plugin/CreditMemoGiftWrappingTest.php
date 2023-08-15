<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Plugin;

use Magento\GiftWrapping\Model\Plugin\CreditMemoGiftWrapping;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditMemoExtension;
use Magento\Sales\Api\Data\CreditMemoExtensionFactory;
use Magento\Sales\Api\Data\CreditmemoInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class CreditMemoGiftWrappingTest extends TestCase
{
    /**
     * @var CreditMemoGiftWrapping
     */
    private $plugin;

    /**
     * @var CreditmemoRepositoryInterface|MockObject
     */
    private $subjectMock;

    /**
     * @var CreditmemoInterface|MockObject
     */
    private $creditMemoMock;

    /**
     * @var CreditMemoExtension|MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var CreditMemoExtensionFactory|MockObject
     */
    private $creditMemoExtensionFactoryMock;

    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockForAbstractClass(CreditmemoRepositoryInterface::class);
        $this->creditMemoMock = $this->getMockBuilder(CreditmemoInterface::class)
            ->setMethods([
                'getGwBasePrice',
                'getGwPrice',
                'getGwItemsBasePrice',
                'getGwItemsPrice',
                'getGwCardBasePrice',
                'getGwCardPrice',
                'getGwBaseTaxAmount',
                'getGwTaxAmount',
                'getGwItemsBaseTaxAmount',
                'getGwItemsTaxAmount',
                'getGwCardBaseTaxAmount',
                'getGwCardTaxAmount',
                'getExtensionAttributes',
                'setExtensionAttributes',
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributeMock = $this->getMockBuilder(CreditMemoExtension::class)
            ->setMethods([
                'setGwBasePrice',
                'setGwPrice',
                'setGwItemsBasePrice',
                'setGwItemsPrice',
                'setGwCardBasePrice',
                'setGwCardPrice',
                'setGwBaseTaxAmount',
                'setGwTaxAmount',
                'setGwItemsBaseTaxAmount',
                'setGwItemsTaxAmount',
                'setGwCardBaseTaxAmount',
                'setGwCardTaxAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditMemoExtensionFactoryMock = $this->getMockBuilder(CreditMemoExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new CreditMemoGiftWrapping(
            $this->creditMemoExtensionFactoryMock
        );
    }

    public function testAfterGet()
    {
        $returnValue = 10;

        $this->creditMemoMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);

        $this->creditMemoMock->expects(static::once())
            ->method('getGwBasePrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwBasePrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwPrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwPrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwItemsBasePrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwItemsBasePrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwItemsPrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwItemsPrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwCardBasePrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwCardBasePrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwCardPrice')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwCardPrice')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwBaseTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwBaseTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwItemsBaseTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwItemsBaseTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwItemsTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwItemsTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwCardBaseTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwCardBaseTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('getGwCardTaxAmount')
            ->willReturn($returnValue);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setGwCardTaxAmount')
            ->with($returnValue)
            ->willReturnSelf();

        $this->creditMemoMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->creditMemoMock);
    }
}
