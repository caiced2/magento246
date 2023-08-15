<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Persistent\Helper\Session;
use Magento\Persistent\Model\Persistent\Config;
use Magento\Persistent\Model\Persistent\ConfigFactory;
use Magento\PersistentHistory\Helper\Data;
use Magento\PersistentHistory\Observer\ApplyPersistentDataObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplyPersistentDataObserverTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $historyHelperMock;

    /**
     * @var MockObject
     */
    protected $persistentHelperMock;

    /**
     * @var MockObject
     */
    protected $customerSessionMock;

    /**
     * @var MockObject
     */
    protected $sessionHelperMock;

    /**
     * @var MockObject
     */
    protected $configFactoryMock;

    /**
     * @var ApplyPersistentDataObserver
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager */
        $objectManager = new ObjectManager($this);

        $this->historyHelperMock = $this->createMock(Data::class);
        $this->sessionHelperMock = $this->createMock(Session::class);
        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->configFactoryMock = $this->createPartialMock(
            ConfigFactory::class,
            ['create']
        );
        $this->persistentHelperMock = $this->getMockBuilder(\Magento\Persistent\Helper\Data::class)->addMethods(
            ['isCompareProductsPersist']
        )
            ->onlyMethods(['canProcess'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $objectManager->getObject(
            ApplyPersistentDataObserver::class,
            [
                'ePersistentData' => $this->historyHelperMock,
                'persistentSession' => $this->sessionHelperMock,
                'mPersistentData' => $this->persistentHelperMock,
                'customerSession' => $this->customerSessionMock,
                'configFactory' => $this->configFactoryMock
            ]
        );
    }

    public function testApplyPersistentDataIfDataCantProcess()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(false);
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataIfSessionNotPersistent()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(true);
        $this->sessionHelperMock->expects($this->once())->method('isPersistent')->willReturn(false);
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataIfUserLoggedIn()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(true);
        $this->sessionHelperMock->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataSuccess()
    {
        $configFilePath = 'file/path';
        $observerMock = $this->createMock(Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(true);
        $this->sessionHelperMock->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(false);

        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('setConfigFilePath')
            ->with($configFilePath)->willReturnSelf();

        $this->historyHelperMock->expects($this->once())
            ->method('getPersistentConfigFilePath')
            ->willReturn($configFilePath);

        $this->configFactoryMock->expects($this->once())->method('create')->willReturn($configMock);
        $this->subject->execute($observerMock);
    }
}
