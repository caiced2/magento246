<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model\Plugin;

use Magento\AdminGws\Model\Plugin\CategoryResource;
use Magento\AdminGws\Model\Role;
use Magento\Catalog\Model\ResourceModel\Category;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryResourceTest extends TestCase
{
    /**
     * @var CategoryResource
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $roleMock;

    protected function setUp(): void
    {
        $this->roleMock = $this->createMock(Role::class);
        $this->model = new CategoryResource($this->roleMock);
    }

    public function testBeforeChangeParentDoesNotCheckCategoryAccessWhenRoleIsNotRestricted()
    {
        $subjectMock = $this->createMock(Category::class);
        $currentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $parentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $this->roleMock->expects($this->once())->method('getIsAll')->willReturn(true);
        $this->roleMock->expects($this->never())->method('hasExclusiveCategoryAccess');
        $this->model->beforeChangeParent($subjectMock, $currentCategory, $parentCategory);
    }

    /**
     * @param boolean $hasParentPathAccess
     * @param boolean $hasCurrentPathAccess
     * @dataProvider beforeChangeParentThrowsExceptionWhenAccessIsRestrictedDataProvider
     */
    public function testBeforeChangeParentThrowsExceptionWhenAccessIsRestricted(
        $hasParentPathAccess,
        $hasCurrentPathAccess
    ) {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('More permissions are needed to save this item.');
        $this->roleMock->expects($this->once())->method('getIsAll')->willReturn(false);

        $subjectMock = $this->createMock(Category::class);
        $currentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $currentCategory->expects(
            $this->any()
        )->method(
            'getData'
        )->with(
            'path',
            null
        )->willReturn(
            'current/path'
        );
        $parentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $parentCategory->expects(
            $this->any()
        )->method(
            'getData'
        )->with(
            'path',
            null
        )->willReturn(
            'parent/path'
        );

        $this->roleMock->expects(
            $this->any()
        )->method(
            'hasExclusiveCategoryAccess'
        )->willReturnMap(
            [['parent/path', $hasParentPathAccess], ['current/path', $hasCurrentPathAccess]]
        );
        $this->model->beforeChangeParent($subjectMock, $currentCategory, $parentCategory, null);
    }

    public function beforeChangeParentThrowsExceptionWhenAccessIsRestrictedDataProvider()
    {
        return [[true, false], [false, true], [false, false]];
    }

    public function testBeforeChangeParentDoesNotThrowExceptionWhenUserHasAccessToGivenCategories()
    {
        $this->roleMock->expects($this->once())->method('getIsAll')->willReturn(false);

        $subjectMock = $this->createMock(Category::class);
        $parentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $parentCategory->expects(
            $this->any()
        )->method(
            'getData'
        )->with(
            'path',
            null
        )->willReturn(
            'parent/path'
        );
        $currentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $currentCategory->expects(
            $this->any()
        )->method(
            'getData'
        )->with(
            'path',
            null
        )->willReturn(
            'current/path'
        );

        $this->roleMock->expects(
            $this->exactly(2)
        )->method(
            'hasExclusiveCategoryAccess'
        )->willReturnMap(
            [['parent/path', true], ['current/path', true]]
        );
        $this->model->beforeChangeParent($subjectMock, $currentCategory, $parentCategory, null);
    }
}
