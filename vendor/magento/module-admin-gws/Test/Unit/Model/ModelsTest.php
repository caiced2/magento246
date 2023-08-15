<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model;

use Magento\AdminGws\Model\Models;
use Magento\AdminGws\Model\Role;
use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\Cms\Model\ResourceModel\Block;
use Magento\Cms\Model\ResourceModel\Page;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\AdminGws\Model\Models.
 */
class ModelsTest extends TestCase
{
    /**
     * @var Models
     */
    private $model;

    /**
     * @var Role|MockObject
     */
    private $role;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->role = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'hasStoreAccess',
                    'hasExclusiveStoreAccess',
                    'getStoreIds',
                    'getDisallowedStoreIds',
                    'isWebsiteLevel',
                    'hasExclusiveAccess'
                ]
            )
            ->getMock();
        $this->model = $this->objectManagerHelper->getObject(
            Models::class,
            [
                'role' => $this->role,
            ]
        );
    }

    /**
     * @return void
     */
    public function testCmsPageSaveBefore()
    {
        $pageId = 1;
        $storeIds = [Store::DEFAULT_STORE_ID];

        /** @var Page|MockObject $cmsPageResource */
        $cmsPageResource = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['lookupStoreIds'])
            ->getMock();
        $cmsPageResource->expects($this->once())->method('lookupStoreIds')
            ->with($pageId)
            ->willReturn($storeIds);

        $this->role->expects($this->once())->method('hasStoreAccess')
            ->with($storeIds)
            ->willReturn(true);
        $this->role->expects($this->once())->method('hasExclusiveStoreAccess')
            ->with($storeIds)
            ->willReturn(true);
        $this->role->expects($this->atLeastOnce())->method('getStoreIds')->willReturn($storeIds);
        $this->role->expects($this->once())->method('getDisallowedStoreIds')->willReturn([]);

        /** @var \Magento\Cms\Model\Page|MockObject $cmsPage */
        $cmsPage = $this->getMockBuilder(\Magento\Cms\Model\Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResource', 'getId', 'getStoreId', 'setStoreId'])
            ->getMock();

        $cmsPage->expects($this->once())->method('getResource')->willReturn($cmsPageResource);
        $cmsPage->expects($this->exactly(2))->method('getId')->willReturn($pageId);
        $cmsPage->expects($this->once())->method('getStoreId')->willReturn($storeIds);
        $cmsPage->expects($this->once())->method('setStoreId')->with($storeIds);

        $this->model->cmsPageSaveBefore($cmsPage);
    }

    /**
     * @dataProvider ruleSaveBeforeDataProvider
     * @param bool $isObjectNew
     * @param bool $hasExclusiveAccess
     * @param bool $isExceptionMessageExpected
     * @param string $exceptionMessage
     * @throws LocalizedException ;
     */
    public function testRuleSaveBefore(
        bool $isObjectNew,
        bool $hasExclusiveAccess,
        bool $isExceptionMessageExpected,
        string $exceptionMessage
    ) {
        $websiteIDs = [Store::DEFAULT_STORE_ID];

        /** @var Rule|MockObject $catalogRuleResource */
        $catalogRuleResource = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'isObjectNew', 'getData', 'getOrigData'])
            ->getMock();

        $catalogRuleResource->expects($this->any())
            ->method('isObjectNew')
            ->willReturn($isObjectNew);
        $catalogRuleResource->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);
        $catalogRuleResource->expects($this->any())
            ->method('getData')->with('website_ids')
            ->willReturn($websiteIDs);
        $catalogRuleResource->expects($this->any())
            ->method('getOrigData')->with('website_ids')
            ->willReturn($websiteIDs);

        $this->role->expects($this->never())
            ->method('isWebsiteLevel')
            ->willReturn(true);
        $this->role->expects($this->once())
            ->method('hasExclusiveAccess')
            ->with($websiteIDs)
            ->willReturn($hasExclusiveAccess);

        if ($isExceptionMessageExpected) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->model->ruleSaveBefore($catalogRuleResource);
    }

    /**
     * @return array
     */
    public function ruleSaveBeforeDataProvider(): array
    {
        $exceptionMessage = 'More permissions are needed to save this item.';

        return [
            [
                true,
                true,
                false,
                ''
            ],
            [
                true,
                false,
                true,
                $exceptionMessage
            ],
            [
                false,
                true,
                false,
                ''
            ],
            [
                false,
                false,
                true,
                $exceptionMessage
            ]
        ];
    }

    /**
     * Test for method cmsBlockSaveBefore.
     *
     * @return void
     */
    public function testCmsBlockSaveBefore()
    {
        $blockId = 1;
        $storeIds = [Store::DEFAULT_STORE_ID];

        /** @var Block|MockObject $cmsBlockResource */
        $cmsBlockResource = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->setMethods(['lookupStoreIds'])
            ->getMock();
        $cmsBlockResource->expects($this->once())->method('lookupStoreIds')
            ->with($blockId)
            ->willReturn($storeIds);

        $this->role->expects($this->once())->method('hasStoreAccess')
            ->with($storeIds)
            ->willReturn(true);
        $this->role->expects($this->once())->method('hasExclusiveStoreAccess')
            ->with($storeIds)
            ->willReturn(true);
        $this->role->expects($this->atLeastOnce())->method('getStoreIds')->willReturn($storeIds);
        $this->role->expects($this->once())->method('getDisallowedStoreIds')->willReturn([]);

        /** @var \Magento\Cms\Model\Block|MockObject $cmsBlock */
        $cmsBlock = $this->getMockBuilder(\Magento\Cms\Model\Block::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResource', 'getId', 'getStoreId', 'setStoreId'])
            ->getMock();

        $cmsBlock->expects($this->once())->method('getResource')->willReturn($cmsBlockResource);
        $cmsBlock->expects($this->exactly(2))->method('getId')->willReturn($blockId);
        $cmsBlock->expects($this->once())->method('getStoreId')->willReturn($storeIds);
        $cmsBlock->expects($this->once())->method('setStoreId')->with($storeIds);

        $this->model->cmsBlockSaveBefore($cmsBlock);
    }

    /**
     * @return void
     */
    public function testCustomerLoadAfter(): void
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('More permissions are needed to view this item.');
        $store1 = 1;
        $store2 = 2;
        /** @var MockObject|Customer $customerMock */
        $customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();
        $customerMock->expects($this->exactly(2))
            ->method('getStoreId')
            ->willReturnOnConsecutiveCalls($store1, $store2);
        $this->role->expects($this->exactly(2))
            ->method('hasStoreAccess')
            ->withConsecutive([$store1], [$store2])
            ->willReturnOnConsecutiveCalls(true, false);

        //Website #1 is allowed for the role.
        $this->model->customerLoadAfter($customerMock);
        //Website #2 is NOT allowed.
        $this->model->customerLoadAfter($customerMock);
    }
}
