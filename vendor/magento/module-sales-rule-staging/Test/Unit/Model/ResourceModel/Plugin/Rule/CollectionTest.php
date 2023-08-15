<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model\ResourceModel\Plugin\Rule;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRuleStaging\Model\ResourceModel\Plugin\Rule\Collection;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    protected $plugin;

    /**
     * @var VersionManager|MockObject
     */
    protected $versionManagerMock;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection|MockObject
     */
    protected $ruleCollectionMock;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPreviewVersion'])
            ->getMock();
        $this->address = $objectManager->getObject(Address::class);
        $this->ruleCollectionMock = $this->createMock(\Magento\SalesRule\Model\ResourceModel\Rule\Collection::class);

        $className = Collection::class;
        $this->plugin = $objectManager->getObject(
            $className,
            [
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    /**
     * Test the beforeSetValidationFilter(...) method
     *
     * @param bool $isPreviewVersion
     * @param bool $expectedToSkipValidationFilter
     * @dataProvider beforeSetValidationFilterDataProvider
     */
    public function testBeforeSetValidationFilter($isPreviewVersion, $expectedToSkipValidationFilter)
    {
        // flesh out versionManager
        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn($isPreviewVersion);

        // before: ensure that output flag is either false or null
        $skipValFilter = $this->address->getData('skip_validation_filter');
        $this->assertNull($skipValFilter, "Expected 'skip_validation_filter' flag to be false or null");

        // invoke the plugin
        $this->plugin->beforeSetValidationFilter($this->ruleCollectionMock, 1, 1, '', null, $this->address);

        // after: ensure the output flag is the expected result
        $skipValFilter = $this->address->getData('skip_validation_filter');
        $this->assertEquals($expectedToSkipValidationFilter, $skipValFilter);
    }

    /**
     * @return array
     */
    public function beforeSetValidationFilterDataProvider()
    {
        return [
            'preview_mode' => [true, true],
            'present_mode' => [false, false],
        ];
    }

    /**
     * Test the beforeSetValidationFilter(...) method when there is no address
     */
    public function testBeforeSetValidationFilterNoAddress()
    {
        // ensure no blowouts when invoked without an address
        $address = null;
        $result = $this->plugin->beforeSetValidationFilter($this->ruleCollectionMock, 1, 1, '', null, $address);
        $this->assertNull($result);
    }
}
