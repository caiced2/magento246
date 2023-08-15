<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Block\Adminhtml\Banner\Edit\Tab;

use Magento\Backend\Block\Widget\Context;
use Magento\Banner\Block\Adminhtml\Banner\Edit\Tab\Content;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    /**
     * @var Content
     */
    protected $content;

    /**
     * @var Registry|MockObject
     */
    protected $registry;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var FormFactory|MockObject
     */
    protected $formFactory;

    /**
     * @var Config|MockObject
     */
    protected $wysiwygConfig;

    protected function setUp(): void
    {
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formFactory = $this->getMockBuilder(FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wysiwygConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->content = new Content(
            $this->context,
            $this->registry,
            $this->formFactory,
            $this->wysiwygConfig,
            []
        );
    }

    public function testGetTabLabel()
    {
        $this->assertEquals('Content', $this->content->getTabLabel());
    }

    public function testGetTabTitle()
    {
        $this->assertEquals('Content', $this->content->getTabTitle());
    }

    public function testCanShowTab()
    {
        $this->assertTrue($this->content->canShowTab());
    }

    public function testIsHidden()
    {
        $this->assertFalse($this->content->isHidden());
    }
}
