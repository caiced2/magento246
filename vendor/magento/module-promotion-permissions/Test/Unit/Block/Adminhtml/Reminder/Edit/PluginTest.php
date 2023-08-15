<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PromotionPermissions\Test\Unit\Block\Adminhtml\Reminder\Edit;

use Magento\Backend\Block\Widget\Button\Item as ButtonItemWidget;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\PromotionPermissions\Block\Adminhtml\Reminder\Edit\Plugin as ReminderEditPlugin;
use Magento\PromotionPermissions\Helper\Data as DataHelper;
use Magento\Reminder\Block\Adminhtml\Reminder\Edit as ReminderEditBlock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    /**
     * @var ReminderEditPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var DataHelper|MockObject
     */
    private $dataHelperMock;

    /**
     * @var ReminderEditBlock|MockObject
     */
    private $subjectMock;

    /**
     * @var ButtonItemWidget|MockObject
     */
    private $buttonItemWidgetMock;

    protected function setUp(): void
    {
        $this->dataHelperMock = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(ReminderEditBlock::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buttonItemWidgetMock = $this->getMockBuilder(ButtonItemWidget::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            ReminderEditPlugin::class,
            ['dataHelper' => $this->dataHelperMock]
        );
    }

    public function testAfterCanRenderNegativeResult()
    {
        $result = false;

        $this->assertEquals(
            $result,
            $this->plugin->afterCanRender($this->subjectMock, $result, $this->buttonItemWidgetMock)
        );
    }

    public function testAfterCanRenderCanEdit()
    {
        $result = true;

        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('getCanAdminEditReminderRules')
            ->willReturn(true);

        $this->assertEquals(
            $result,
            $this->plugin->afterCanRender($this->subjectMock, $result, $this->buttonItemWidgetMock)
        );
    }

    public function testAfterCanRenderAllowedButton()
    {
        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('getCanAdminEditReminderRules')
            ->willReturn(false);
        $this->buttonItemWidgetMock->expects(static::atLeastOnce())
            ->method('getId')
            ->willReturn('back');

        $this->assertTrue($this->plugin->afterCanRender($this->subjectMock, true, $this->buttonItemWidgetMock));
    }

    public function testAfterCanRenderRestrictedButton()
    {
        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('getCanAdminEditReminderRules')
            ->willReturn(false);
        $this->buttonItemWidgetMock->expects(static::atLeastOnce())
            ->method('getId')
            ->willReturn('save');

        $this->assertFalse($this->plugin->afterCanRender($this->subjectMock, true, $this->buttonItemWidgetMock));
    }
}
