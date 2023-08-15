<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PromotionPermissions\Test\Unit\Block\Adminhtml\Promo\Quote\Edit\GenericButton;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PromotionPermissions\Block\Adminhtml\Promo\Quote\Edit\GenericButton\Plugin;
use Magento\PromotionPermissions\Helper\Data;
use Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\GenericButton;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    /**
     * @var Plugin
     */
    protected $model;

    /**
     * @param bool $canEdit
     * @param string $name
     * @param bool $expectedResult
     * @dataProvider afterCanRenderDataProvider
     */
    public function testAfterCanRender($canEdit, $name, $expectedResult)
    {
        $permissionsDataMock = $this->createMock(Data::class);
        $permissionsDataMock->expects($this->once())->method('getCanAdminEditSalesRules')->willReturn($canEdit);
        $buttonMock = $this->createMock(GenericButton::class);

        $model = (new ObjectManager($this))->getObject(
            Plugin::class,
            ['promoPermData' => $permissionsDataMock]
        );
        $this->assertEquals($expectedResult, $model->afterCanRender($buttonMock, $name));
    }

    /**
     * @return array
     */
    public function afterCanRenderDataProvider()
    {
        return [
            [true, 'any', true],
            [false, 'delete', false],
            [false, 'turbo', true],
            [false, 'save_and_continue_edit', false],
            [false, 'save', false],
            [false, 'reset', false]
        ];
    }
}
