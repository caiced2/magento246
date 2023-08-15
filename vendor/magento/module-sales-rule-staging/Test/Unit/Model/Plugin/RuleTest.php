<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRuleStaging\Model\Plugin\Rule;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;

class RuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Rule
     */
    private $plugin;

    /**
     * @var SalesRule
     */
    private $salesRule;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManager;

    /**
     * @var UpdateInterface|MockObject
     */
    private $update;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentVersion'])
            ->getMock();

        $this->update = $this->getMockForAbstractClass(UpdateInterface::class);
        $this->versionManager->expects(static::once())
            ->method('getCurrentVersion')
            ->willReturn($this->update);

        $this->plugin = $objectManager->getObject(Rule::class, [
            'versionManager' => $this->versionManager
        ]);
        $this->prepareObjectManager([
            [
                \Magento\Framework\Api\ExtensionAttributesFactory::class,
                $this->createMock(\Magento\Framework\Api\ExtensionAttributesFactory::class)
            ],
            [
                \Magento\Framework\Api\AttributeValueFactory::class,
                $this->createMock(\Magento\Framework\Api\AttributeValueFactory::class)
            ],
        ]);
        $this->salesRule = $objectManager->getObject(SalesRule::class);
    }

    /**
     * Test the beforeBeforeSave(...) method
     *
     * @param int|string|null $createdIn
     * @param int|string|null $updatedIn
     * @param bool $expectedToSkipSaveFilter
     * @dataProvider beforeSaveDataProvider
     */
    public function testBeforeBeforeSave($createdIn, $updatedIn, $expectedToSkipSaveFilter)
    {
        // set the input flags
        if ($createdIn !== 'DO_NOT_SET') {
            $this->salesRule->setCreatedIn($createdIn);
        }
        if ($updatedIn !== 'DO_NOT_SET') {
            $this->salesRule->setUpdatedIn($updatedIn);
        }

        // before: ensure that output flag is either false or null
        $skipSaveFilter = $this->salesRule->getData('skip_save_filter');
        static::assertNull($skipSaveFilter, "Expected 'skip_save_filter' flag to be false or null");

        // invoke the plugin
        $this->plugin->beforeBeforeSave($this->salesRule);

        // after: ensure the output flag is the expected result
        $skipSaveFilter = $this->salesRule->getData('skip_save_filter');
        static::assertEquals($expectedToSkipSaveFilter, $skipSaveFilter);
    }

    /**
     * @covers \Magento\SalesRuleStaging\Model\Plugin\Rule::beforeBeforeSave
     */
    public function testBeforeSaveWithVersion()
    {
        $startDate = strtotime('+1 day');
        $endDate = strtotime('+3 days');

        $this->update->method('getStartTime')
            ->willReturn($startDate);
        $this->update->method('getEndTime')
            ->willReturn($endDate);

        $this->plugin->beforeBeforeSave($this->salesRule);

        static::assertEquals($startDate, $this->salesRule->getData('from_date'));
        static::assertEquals($endDate, $this->salesRule->getData('to_date'));
    }

    /*
     * @return array
     */
    public function beforeSaveDataProvider()
    {
        return [
            'forever' => [0, PHP_INT_MAX, false],
            'past_time' => [0, 1000, true],
            'future_time' => [PHP_INT_MAX - 1000, PHP_INT_MAX, true],
            'no_time_specified' => ['DO_NOT_SET', 'DO_NOT_SET', false],
            'nulls_specified' => [null, null, false],
        ];
    }

    /**
     * @param $map
     */
    private function prepareObjectManager($map)
    {
        $objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
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
