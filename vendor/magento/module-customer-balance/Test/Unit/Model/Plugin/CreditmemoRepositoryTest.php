<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Model\Plugin;

use Magento\CustomerBalance\Model\Plugin\CreditmemoRepository;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoExtension;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Api\Data\CreditmemoInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditmemoRepositoryTest extends TestCase
{
    /**
     * @var CreditmemoRepository
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
     * @var CreditmemoExtension|MockObject
     */
    private $extensionAttributeMock;

    /**
     * @var CreditmemoExtensionFactory|MockObject
     */
    private $creditMemoExtensionFactoryMock;

    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockForAbstractClass(CreditmemoRepositoryInterface::class);
        $this->creditMemoMock = $this->getMockBuilder(CreditmemoInterface::class)
            ->setMethods([
                'getExtensionAttributes',
                'setExtensionAttributes',
                'setCustomerBalanceAmount',
                'setBaseCustomerBalanceAmount',
                'getBaseCustomerBalanceAmount',
                'getCustomerBalanceAmount'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extensionAttributeMock = $this->getMockBuilder(CreditmemoExtension::class)
            ->setMethods([
                'getCustomerBalanceAmount',
                'getBaseCustomerBalanceAmount',
                'setCustomerBalanceAmount',
                'setBaseCustomerBalanceAmount'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditMemoExtensionFactoryMock = $this->getMockBuilder(CreditmemoExtensionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new CreditmemoRepository(
            $this->creditMemoExtensionFactoryMock
        );
    }

    public function testAfterGet()
    {
        $customerBalanceAmount = 10;
        $baseCustomerBalanceAmount = 15;

        $this->creditMemoMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->creditMemoMock->expects(static::once())
            ->method('getCustomerBalanceAmount')
            ->willReturn($customerBalanceAmount);
        $this->creditMemoMock->expects(static::once())
            ->method('getBaseCustomerBalanceAmount')
            ->willReturn($baseCustomerBalanceAmount);
        $this->extensionAttributeMock->expects(static::once())
            ->method('setCustomerBalanceAmount')
            ->with($customerBalanceAmount)
            ->willReturnSelf();
        $this->extensionAttributeMock->expects(static::once())
            ->method('setBaseCustomerBalanceAmount')
            ->with($baseCustomerBalanceAmount)
            ->willReturnSelf();
        $this->creditMemoMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock)
            ->willReturnSelf();

        $this->plugin->afterGet($this->subjectMock, $this->creditMemoMock);
    }
}
