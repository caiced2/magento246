<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Block\Adminhtml\Banner\Edit\Tab\Promotions;

use Magento\Backend\Block\Template\Context;
use Magento\Banner\Block\Adminhtml\Banner\Edit\Tab\Promotions\Catalogrule;
use Magento\Banner\Model\Banner;
use Magento\Framework\Filesystem;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogruleTest extends TestCase
{
    /**
     * @var Catalogrule
     */
    protected $catalogRule;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilder;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var Registry|MockObject
     */
    protected $registry;

    protected function setUp(): void
    {
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $fileSystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->once())->method('getFilesystem')->willReturn($fileSystem);

        $objectManagerHelper = new ObjectManager($this);
        $this->context->expects($this->once())->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $this->catalogRule = $objectManagerHelper->getObject(
            Catalogrule::class,
            [
                'context' => $this->context,
                'registry' => $this->registry
            ]
        );
    }

    public function testGetTabLabel()
    {
        $this->urlBuilder->expects($this->once())->method('getUrl')->with(
            'adminhtml/*/catalogRuleGrid',
            ['_current' => true]
        )->willReturn('test_string');

        $this->assertEquals('test_string', $this->catalogRule->getGridUrl());
    }

    public function testGetRelatedCatalogRule()
    {
        $banner = $this->getMockBuilder(Banner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $banner->expects($this->once())->method('getRelatedCatalogRule')->willReturn(['test1', 'test2']);
        $this->registry->expects($this->once())->method('registry')->with('current_banner')->willReturn(
            $banner
        );
        $this->assertEquals(['test1', 'test2'], $this->catalogRule->getRelatedCatalogRule());
    }
}
