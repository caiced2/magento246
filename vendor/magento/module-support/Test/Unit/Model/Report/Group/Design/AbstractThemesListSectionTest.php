<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Design;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Theme\Model\ResourceModel\Theme\Collection;
use Magento\Theme\Model\Theme;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractThemesListSectionTest extends TestCase
{
    /**
     * @var \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory|MockObject
     */
    protected $themeCollectionFactoryMock;

    /**
     * @var Collection|MockObject
     */
    protected $themeCollectionMock;

    /**
     * @var Theme|MockObject
     */
    protected $themeMock;

    /**
     * @var Theme|MockObject
     */
    protected $parentThemeMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->themeCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setOrder', 'getItems', 'getIterator'])
            ->getMock();
        $this->themeCollectionFactoryMock = $this->getMockBuilder(
            \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->themeCollectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->themeCollectionMock);
        $this->themeCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->themeCollectionMock->expects($this->once())->method('setOrder')->willReturnSelf();
        $this->themeCollectionMock->expects($this->once())->method('load')->willReturnSelf();

        $this->parentThemeMock = $this->getMockBuilder(Theme::class)
            ->setMethods(['getThemePath'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Create theme model mock
     *
     * @param string $themePath
     * @param Theme|MockObject|null $parentThemeMock
     * @param string|null $parentThemePath
     * @return Theme|MockObject
     */
    protected function getThemeMock($themePath, $parentThemeMock = null, $parentThemePath = null)
    {
        $themeMock = $this->createPartialMock(Theme::class, ['getParentTheme', 'getThemePath']);
        $themeMock->expects($this->atLeastOnce())->method('getParentTheme')->willReturn($parentThemeMock);
        $this->parentThemeMock->expects($this->any())->method('getThemePath')->willReturn($parentThemePath);
        $themeMock->expects($this->once())->method('getThemePath')->willReturn($themePath);

        return $themeMock;
    }
}
