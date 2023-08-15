<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model\Plugin;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRuleStaging\Model\Plugin\DateResolverPlugin;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DateResolverPluginTest extends TestCase
{
    /**
     * @var DateResolverPlugin
     */
    private $plugin;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepository;

    /**
     * @var UpdateInterface|MockObject
     */
    private $update;

    /**
     * @var SalesRule
     */
    private $subject;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->updateRepository = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);
        $this->update = $this->getMockForAbstractClass(UpdateInterface::class);

        $this->prepareObjectManager(
            [
                [
                    ExtensionAttributesFactory::class,
                    $this->createMock(ExtensionAttributesFactory::class)
                ],
                [
                    AttributeValueFactory::class,
                    $this->createMock(AttributeValueFactory::class)
                ],
            ]
        );

        $this->subject = $objectManager->getObject(SalesRule::class);

        $this->plugin = new DateResolverPlugin($this->updateRepository);
    }

    /**
     * @covers \Magento\SalesRuleStaging\Model\Plugin\DateResolverPlugin::beforeGetFromDate
     */
    public function testBeforeGetFromDate()
    {
        $startDate = date('Y-m-d', strtotime('+1 day'));
        $fromDate = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->subject->setData('from_date', $fromDate);

        $this->updateRepository->expects(static::atLeastOnce())
            ->method('get')
            ->willReturn($this->update);

        $this->update->expects(static::atLeastOnce())
            ->method('getStartTime')
            ->willReturn($startDate);

        $this->plugin->beforeGetFromDate($this->subject);
        static::assertEquals($startDate, $this->subject->getFromDate());
    }

    public function testBeforeGetFromDateWithoutUpdate()
    {
        $fromDate = date('Y-m-d', strtotime('+1 hour'));
        $this->subject->setData('from_date', $fromDate);

        $this->updateRepository->expects($this->atLeastOnce())
            ->method('get')
            ->willReturn($this->update);

        $this->update->expects($this->atLeastOnce())
            ->method('getStartTime')
            ->willReturn(null);
        $expectedResult = new \DateTime('now');
        $this->plugin->beforeGetFromDate($this->subject);
        $this->assertEquals($expectedResult->format('Y-m-d'), $this->subject->getFromDate());
    }

    /**
     * @covers \Magento\SalesRuleStaging\Model\Plugin\DateResolverPlugin::beforeGetToDate
     */
    public function testBeforeGetToDate()
    {
        $endDate = date('Y-m-d', strtotime('+2 day'));
        $toDate = date('Y-m-d H:i:s', strtotime('+1 day'));

        $this->subject->setData('to_date', $toDate);

        $this->updateRepository->expects(static::atLeastOnce())
            ->method('get')
            ->willReturn($this->update);

        $this->update->expects(static::atLeastOnce())
            ->method('getEndTime')
            ->willReturn($endDate);

        $this->plugin->beforeGetToDate($this->subject);
        static::assertEquals($endDate, $this->subject->getToDate());
    }

    public function testBeforeGetToDateWithoutUpdate()
    {
        $toDate = new \DateTime(date('Y-m-d H:i:s', strtotime('+1 day')));
        $this->subject->setData('to_date', $toDate);

        $this->updateRepository->expects($this->atLeastOnce())
            ->method('get')
            ->willReturn($this->update);

        $this->update->expects($this->atLeastOnce())
            ->method('getEndTime')
            ->willReturnOnConsecutiveCalls(null, '');

        $this->plugin->beforeGetToDate($this->subject);
        $this->assertEquals(
            $toDate->format('Y-m-d'),
            $this->subject->getToDate()
        );
        //Checking that plugin works fine when empty string is returned
        //by getEndTime call.
        $this->plugin->beforeGetToDate($this->subject);
        $this->assertEquals(
            $toDate->format('Y-m-d'),
            $this->subject->getToDate()
        );
    }

    public function testBeforeGetToDateWithUpdateExceedMaxVersion()
    {
        $endDate = date('Y-m-d', strtotime('+30 years'));

        $this->updateRepository->expects(static::atLeastOnce())
            ->method('get')
            ->willReturn($this->update);

        $this->update->expects(static::atLeastOnce())
            ->method('getEndTime')
            ->willReturn($endDate);

        $this->plugin->beforeGetToDate($this->subject);
        static::assertEquals(date('Y-m-d', VersionManager::MAX_VERSION), $this->subject->getToDate());
    }

    /**
     * @param $map
     */
    private function prepareObjectManager($map)
    {
        $objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->setMethods(['getInstance'])
            ->getMockForAbstractClass();
        $objectManagerMock->expects($this->any())->method('getInstance')->willReturnSelf();
        $objectManagerMock->expects($this->any())
            ->method('get')
            ->willReturnMap($map);
        $reflectionClass = new \ReflectionClass(\Magento\Framework\App\ObjectManager::class);
        $reflectionProperty = $reflectionClass->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($objectManagerMock);
    }
}
