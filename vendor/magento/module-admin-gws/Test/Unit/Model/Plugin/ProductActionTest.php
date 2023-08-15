<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model\Plugin;

use Magento\AdminGws\Model\Plugin\ProductAction;
use Magento\AdminGws\Model\Role;
use Magento\Catalog\Model\Product\Action;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductActionTest extends TestCase
{
    /**
     * @var ProductAction
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $roleMock;

    /**
     * @var MockObject
     */
    protected $subjectMock;

    protected function setUp(): void
    {
        $this->roleMock = $this->createMock(Role::class);
        $this->subjectMock = $this->createMock(Action::class);
        $this->model = new ProductAction($this->roleMock);
    }

    public function testBeforeUpdateWebsitesDoesNotCheckWebsiteAccessWhenRoleIsNotRestricted()
    {
        $this->roleMock->expects($this->once())->method('getIsAll')->willReturn(true);
        $this->roleMock->expects($this->never())->method('getIsWebsiteLevel');
        $this->roleMock->expects($this->never())->method('hasWebsiteAccess');
        $this->model->beforeUpdateWebsites($this->subjectMock, [], [], 'type');
    }

    /**
     * @param boolean $isWebsiteLevelRole
     * @param boolean $hasWebsiteAccess
     * @param string $actionType
     * @dataProvider beforeUpdateWebsitesThrowsExceptionWhenAccessIsRestrictedDataProvider
     */
    public function testBeforeUpdateWebsitesThrowsExceptionWhenAccessIsRestricted(
        $isWebsiteLevelRole,
        $hasWebsiteAccess,
        $actionType
    ) {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('More permissions are needed to save this item.');
        $this->roleMock->expects($this->once())->method('getIsAll')->willReturn(false);
        $this->roleMock->expects(
            $this->any()
        )->method(
            'getIsWebsiteLevel'
        )->willReturn(
            $isWebsiteLevelRole
        );
        $websiteIds = [1];
        $this->roleMock->expects(
            $this->any()
        )->method(
            'hasWebsiteAccess'
        )->with(
            $websiteIds,
            true
        )->willReturn(
            $hasWebsiteAccess
        );
        $this->model->beforeUpdateWebsites($this->subjectMock, [], $websiteIds, $actionType);
    }

    public function beforeUpdateWebsitesThrowsExceptionWhenAccessIsRestrictedDataProvider()
    {
        return [
            [true, false, 'remove'],
            [false, true, 'remove'],
            [false, false, 'remove'],
            [true, false, 'add'],
            [false, true, 'add'],
            [false, false, 'add']
        ];
    }

    public function testBeforeUpdateWebsitesDoesNotThrowExceptionWhenUserHasAccessToGivenWebsites()
    {
        $this->roleMock->expects($this->once())->method('getIsAll')->willReturn(false);
        $this->roleMock->expects($this->once())->method('getIsWebsiteLevel')->willReturn(true);
        $websiteIds = [1];
        $this->roleMock->expects(
            $this->once()
        )->method(
            'hasWebsiteAccess'
        )->with(
            $websiteIds,
            true
        )->willReturn(
            true
        );
        $this->model->beforeUpdateWebsites($this->subjectMock, [], $websiteIds, 'add');
    }
}
