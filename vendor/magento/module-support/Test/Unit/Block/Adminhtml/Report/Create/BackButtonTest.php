<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\Create;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\Support\Block\Adminhtml\Report\Create\BackButton;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BackButtonTest extends TestCase
{
    /**
     * @var BackButton
     */
    protected $backButton;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            [
                'urlBuilder' => $this->urlBuilderMock
            ]
        );
        $this->backButton = $this->objectManagerHelper->getObject(
            BackButton::class,
            [
                'context' => $this->context
            ]
        );
    }

    public function testGetButtonData()
    {
        $url = '/back/url';
        $buttonData = [
            'label' => __('Back'),
            'on_click' => 'location.href = \'/back/url\';',
            'class' => 'back'
        ];

        $this->urlBuilderMock->expects($this->any())
            ->method('getUrl')
            ->with('*/*/', [])
            ->willReturn($url);

        $this->assertEquals($buttonData, $this->backButton->getButtonData());
    }
}
