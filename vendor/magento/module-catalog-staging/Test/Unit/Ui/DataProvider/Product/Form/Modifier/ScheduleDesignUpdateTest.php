<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier\ScheduleDesignUpdate;
use Magento\Framework\Stdlib\ArrayManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ScheduleDesignUpdateTest extends TestCase
{
    /**
     * @var ScheduleDesignUpdate
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $modifierMock;

    /**
     * @var MockObject
     */
    protected $arrayMergerMock;

    protected function setUp(): void
    {
        $this->modifierMock = $this->createMock(
            \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\ScheduleDesignUpdate::class
        );
        $this->arrayMergerMock = $this->createMock(ArrayManager::class);
        $this->model = new ScheduleDesignUpdate(
            $this->arrayMergerMock,
            $this->modifierMock
        );
    }

    /**
     * @param string $customDesignPath
     * @param string $containerPath
     * @param array $arrayGetMap
     * @param array $arraySetMap
     * @param array $expected
     *
     * @dataProvider modifyMetaDataProvider
     */
    public function testModifyMeta(
        $customDesignPath,
        $containerPath,
        array $arrayGetMap,
        array $arraySetMap,
        array $expected
    ) {
        $meta = ['input meta'];
        $arrayMergeMap = [
            [
                'container/path/arguments/data/config',
                ['new meta 1'],
                ['sortOrder' => 0],
                ArrayManager::DEFAULT_PATH_DELIMITER,
                ['new meta 2'],
            ],
            [
                'custom/design/path/arguments/data/config',
                ['new meta 2'],
                ['label' => 'Theme'],
                ArrayManager::DEFAULT_PATH_DELIMITER,
                ['new meta 3'],
            ],
        ];
        $arrayRemoveMap = [
            [$customDesignPath, ['new meta 5'], ArrayManager::DEFAULT_PATH_DELIMITER, ['new meta 6']],
            ['schedule-design-update', ['new meta 6'], ArrayManager::DEFAULT_PATH_DELIMITER, $expected],
        ];
        $this->modifierMock->expects($this->any())->method('modifyMeta')->with($meta)->willReturn(['new meta 1']);
        $this->setupArrayMerger(['new meta 1'], $customDesignPath, $containerPath, $arrayMergeMap, $arrayGetMap);
        $this->arrayMergerMock->expects($this->exactly(count($arraySetMap)))
            ->method('set')
            ->willReturnMap($arraySetMap);
        $this->arrayMergerMock->expects($this->exactly(count($arrayRemoveMap)))
            ->method('remove')
            ->willReturnMap($arrayRemoveMap);
        $actual = $this->model->modifyMeta($meta);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function modifyMetaDataProvider()
    {
        $expected = ['final meta'];
        $customDesignPath = 'custom/design/path';
        $containerPath = 'container/path';
        return [
            'design tab is absent' => [
                $customDesignPath,
                $containerPath,
                'arrayGetMap' => [
                    ['design', ['new meta 3'], null, ArrayManager::DEFAULT_PATH_DELIMITER, null],
                    [
                        'schedule-design-update/arguments',
                        ['new meta 3'],
                        null,
                        ArrayManager::DEFAULT_PATH_DELIMITER,
                        ['design tab']
                    ],
                    [$containerPath, ['new meta 4'], null, ArrayManager::DEFAULT_PATH_DELIMITER, ['custom design tab']],
                ],
                'arraySetMap' => [
                    [
                        'data/config/label',
                        ['design tab'],
                        (string)__('Design'),
                        ArrayManager::DEFAULT_PATH_DELIMITER,
                        ['new design tab']
                    ],
                    [
                        'design/arguments',
                        ['new meta 3'],
                        ['new design tab'],
                        ArrayManager::DEFAULT_PATH_DELIMITER,
                        ['new meta 4']
                    ],
                    [
                        'design/children/schedule-design-update',
                        ['new meta 4'],
                        ['custom design tab'],
                        ArrayManager::DEFAULT_PATH_DELIMITER,
                        ['new meta 5']
                    ],
                ],
                $expected
            ],
            'design tab is present' => [
                $customDesignPath,
                $containerPath,
                'arrayGetMap' => [
                    ['design', ['new meta 3'], null, ArrayManager::DEFAULT_PATH_DELIMITER, ['something']],
                    [$containerPath, ['new meta 3'], null, ArrayManager::DEFAULT_PATH_DELIMITER, ['custom design tab']],
                ],
                'arraySetMap' => [
                    [
                        'design/children/schedule-design-update',
                        ['new meta 3'],
                        ['custom design tab'],
                        ArrayManager::DEFAULT_PATH_DELIMITER,
                        ['new meta 5']
                    ],
                ],
                $expected
            ],
        ];
    }

    /**
     * Setup arrayMergerMock object with necessary data
     *
     * @param array $meta
     * @param string $customDesignPath
     * @param string $containerPath
     * @param array $mergeMap
     * @param array $getMap
     */
    private function setupArrayMerger(array $meta, $customDesignPath, $containerPath, array $mergeMap, array $getMap)
    {
        $this->arrayMergerMock->expects($this->any())
            ->method('findPath')
            ->with('custom_design', $meta)
            ->willReturn($customDesignPath);
        $this->arrayMergerMock->expects($this->any())
            ->method('slicePath')
            ->with($customDesignPath, 0, 3)
            ->willReturn($containerPath);
        $this->arrayMergerMock->expects($this->any())
            ->method('merge')
            ->willReturnMap($mergeMap);
        $this->arrayMergerMock->expects($this->any())
            ->method('get')
            ->willReturnMap($getMap);
    }

    public function testModifyData()
    {
        $data = [];
        $this->modifierMock->expects($this->once())->method('modifyData')->with($data)->willReturn(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $this->model->modifyData($data));
    }
}
