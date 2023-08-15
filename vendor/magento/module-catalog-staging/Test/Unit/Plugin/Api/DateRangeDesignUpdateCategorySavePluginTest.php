<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Plugin\Api;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\CatalogStaging\Plugin\Api\DateRangeDesignUpdateCategorySavePlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DateRangeDesignUpdateCategorySavePluginTest extends TestCase
{
    private const CREATED_IN = '1548707040';
    private const UPDATED_IN = '1548707280';

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var DateRangeDesignUpdateCategorySavePlugin
     */
    private $plugin;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->updateRepositoryMock = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            DateRangeDesignUpdateCategorySavePlugin::class,
            ['updateRepository' => $this->updateRepositoryMock]
        );
    }

    /**
     * @param array $data
     * @param int $expectedCustomDesignFrom
     * @param int $expectedCustomDesignTo
     *
     * @return void
     * @dataProvider beforeSaveDataProvider
     */
    public function testBeforeSave(array $data, int $setCustomDesignFromAt, int $setCustomDesignToAt): void
    {
        $startTime = '1970-01-01 00:00:00';
        $endTime = '1971-01-01 00:00:00';

        $categoryMock = $this->createPartialMock(Category::class, ['setCustomAttribute']);
        $categoryMock->setData($data);

        $startVersionMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $startVersionMock->method('getStartTime')
            ->willReturn($startTime);
        $endVersionMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $endVersionMock->method('getStartTime')
            ->willReturn($endTime);
        $this->updateRepositoryMock->method('get')
            ->willReturnMap(
                [
                    [self::CREATED_IN, $startVersionMock],
                    [self::UPDATED_IN, $endVersionMock]
                ]
            );

        if ($setCustomDesignFromAt < 0 && $setCustomDesignToAt < 0) {
            $categoryMock->expects($this->never())->method('setCustomAttribute');
        } elseif ($setCustomDesignFromAt < 0) {
            $categoryMock->expects($this->once())
                ->method('setCustomAttribute')
                ->with('custom_design_to', $endTime);
        } elseif ($setCustomDesignFromAt == 0 && $setCustomDesignToAt > 0) {
            //The second update is not a roll back of the first one
            $categoryMock->expects($this->once())
                ->method('setCustomAttribute')
                ->with('custom_design_from', $startTime);
        } elseif ($setCustomDesignFromAt == 0 && $setCustomDesignToAt < 0) {
            $categoryMock->expects($this->once())
                ->method('setCustomAttribute')
                ->with('custom_design_from', $startTime);
        }

        $this->plugin->beforeSave(
            $this->getMockForAbstractClass(CategoryRepositoryInterface::class),
            $categoryMock
        );
    }

    /**
     * @return array
     */
    public function beforeSaveDataProvider(): array
    {
        return [
            'no_update' => [[], -1, -1],
            'origin_entity' => [
                [
                    'created_in' => VersionManager::MIN_VERSION,
                    'updated_in' => self::UPDATED_IN
                ],
                -1,
                0
            ],
            'mid_update_entity' => [
                [
                    'created_in' => self::CREATED_IN,
                    'updated_in' => self::UPDATED_IN
                ],
                0,
                1
            ],
            'last_update_entity' => [
                [
                    'created_in' => self::CREATED_IN,
                    'updated_in' => VersionManager::MAX_VERSION
                ],
                0,
                -1
            ],
        ];
    }
}
