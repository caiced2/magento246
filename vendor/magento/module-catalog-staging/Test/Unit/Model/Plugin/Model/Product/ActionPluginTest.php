<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Plugin\Model\Product;

use Magento\Catalog\Model\Product\Action;
use Magento\CatalogStaging\Model\Plugin\Model\Product\ActionPlugin;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionPluginTest extends TestCase
{
    const DATE = '2018-09-20 01:01:01';
    /** @var  ActionPlugin */
    private $plugin;

    /**
     * Set up
     *
     */
    protected function setUp(): void
    {
        /** @var UpdateInterface|MockObject $versionMock */
        $versionMock = $this->getMockBuilder(UpdateInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        /** @var VersionManager | \MockObject $versionManagerMock */
        $versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentVersion'])
            ->getMock();
        $versionManagerMock->expects($this->any())
            ->method('getCurrentVersion')
            ->willReturn($versionMock);
        $dateTimeMock = $this->getMockBuilder(\DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dateTimeMock->expects($this->any())
            ->method('format')
            ->willReturn(self::DATE);

        /** @var TimezoneInterface| MockObject $localeDateMock */
        $localeDateMock = $this->getMockBuilder(TimezoneInterface::class)
            ->getMock();
        $localeDateMock->expects($this->any())
            ->method('date')
            ->willReturn($dateTimeMock);

        $this->plugin = new ActionPlugin($versionManagerMock, $localeDateMock, ['news_from_date' => 'news_to_date']);
        parent::setUp();
    }

    /**
     * @dataProvider provideAttributes
     * @param array $attrData
     * @param array $expectedResult
     */
    public function testUpdateAttributes(array $attrData, array $expectedResult): void
    {
        /** @var Action|MockObject $productActionMock */
        $productActionMock = $this->getMockBuilder(Action::class)
            ->disableOriginalConstructor()
            ->getMock();
        $return = $this->plugin->beforeUpdateAttributes($productActionMock, [], $attrData, 0);
        $this->assertEquals($expectedResult, $return[1]);
    }

    /**
     * @return array
     */
    public function provideAttributes()
    {
        return [
            [['news_from_date' => 1], ['news_from_date' => self::DATE, 'news_to_date' => null]],
            [
                ['news_from_date' => 1, 'test' => 'test data'],
                ['news_from_date' => self::DATE, 'test' => 'test data', 'news_to_date' => null]
            ],
            [['news_from_date' => 0], ['news_from_date' => null]],
            [['fake' => 'fake data'], ['fake' => 'fake data']],
        ];
    }
}
