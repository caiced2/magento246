<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DeferredTotalCalculating\Test\Unit\Plugin;

use Magento\DeferredTotalCalculating\Plugin\TotalsCollectorPlugin;
use Magento\DeferredTotalCalculating\Setup\ConfigOptionsList;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total\Subtotal;
use Magento\Quote\Model\Quote\Address\TotalFactory;
use Magento\Quote\Model\Quote\QuantityCollector;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use Magento\Quote\Model\Shipping;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\ShippingAssignment;
use Magento\Quote\Model\Quote\Address\Total;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TotalsCollectorPluginTest extends TestCase
{
    /** @var TotalsCollectorPlugin */
    private $modelRepository;

    /**
     * @var DeploymentConfig|MockObject
     */
    private $deploymentConfigMock;

    /**
     * @var QuantityCollector|MockObject
     */
    private $quantityCollectorMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * @var Subtotal|MockObject
     */
    private $subtotalCollectorMock;

    /**
     * @var ShippingAssignmentFactory|MockObject
     */
    private $shippingAssignmentFactoryMock;

    /**
     * @var TotalFactory|MockObject
     */
    private $totalFactoryMock;

    /**
     * @var Total|MockObject
     */
    private $totalMock;

    /**
     * @var ShippingFactory|MockObject
     */
    private $shippingFactoryMock;

    /**
     * @var Address|MockObject
     */
    private $addressMock;

    /**
     * @var Shipping|MockObject
     */
    private $shippingMock;

    /**
     * @var ShippingAssignment|MockObject
     */
    private $shippingAssignmentMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->quantityCollectorMock = $this->createMock(QuantityCollector::class);
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);
        $this->deploymentConfigMock->expects($this->once())
            ->method('get')
            ->with(ConfigOptionsList::CONFIG_PATH_DEFERRED_TOTAL_CALCULATING_FRONTNAME)
            ->willReturn(true);

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getAllAddresses', 'setTotalsCollectedFlag', 'hasDataChanges', 'getData', 'getOrigData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subtotalCollectorMock = $this->createMock(Subtotal::class);

        $this->shippingAssignmentFactoryMock = $this->createMock(ShippingAssignmentFactory::class);

        $this->totalFactoryMock = $this->createMock(TotalFactory::class);

        $this->totalMock = $this->createMock(Total::class);

        $this->shippingFactoryMock = $this->createMock(ShippingFactory::class);

        $this->addressMock = $this->createMock(Address::class);

        $this->shippingMock = $this->createMock(Shipping::class);

        $this->shippingAssignmentMock = $this->createMock(ShippingAssignment::class);

        $this->modelRepository = $objectManager->getObject(
            TotalsCollectorPlugin::class,
            [
                'deploymentConfig' => $this->deploymentConfigMock,
                'quantityCollector' => $this->quantityCollectorMock,
                'subtotalCollector' => $this->subtotalCollectorMock,
                'shippingAssignmentFactory' => $this->shippingAssignmentFactoryMock,
                'totalFactory' => $this->totalFactoryMock,
                'shippingFactory' => $this->shippingFactoryMock
            ]
        );
    }

    public function testAroundCollectTotals()
    {
        $this->quoteMock->expects($this->once())->method('getData')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('getOrigData')->willReturn(2);
        $this->quoteMock->expects($this->once())->method('hasDataChanges')->willReturn(true);
        $this->quoteMock->expects($this->once())->method('getAllAddresses')->willReturn(
            [$this->addressMock, $this->addressMock]
        );
        $this->shippingFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->shippingMock);
        $this->shippingAssignmentFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->shippingAssignmentMock);

        $this->totalFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($this->totalMock);

        $this->quantityCollectorMock->expects($this->once())->method('collectItemsQtys')
            ->with($this->quoteMock)->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->once())->method('setTotalsCollectedFlag');
        $quote = $this->quoteMock;
        $proceed = function () use ($quote) {
            return $quote;
        };
        $this->modelRepository->aroundCollectTotals($quote, $proceed);
    }
}
