<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\View;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\LayoutInterface;
use Magento\Support\Block\Adminhtml\Report\View\Tab;
use Magento\Support\Block\Adminhtml\Report\View\Tab\Grid;
use Magento\Support\Model\Report\DataConverter;
use Magento\Support\Model\Report\Group\General\CacheStatusSection;
use Magento\Support\Model\Report\Group\General\DataCountSection;
use Magento\Support\Model\Report\Group\General\VersionSection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TabTest extends TestCase
{
    /**
     * @var Tab
     */
    protected $reportTabBlock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var DataConverter|MockObject
     */
    protected $dataConverterMock;

    /**
     * @var LayoutInterface|MockObject
     */
    protected $layoutMock;

    /**
     * @var Grid|MockObject
     */
    protected $reportGridBlockMock;

    protected function setUp(): void
    {
        $this->dataConverterMock = $this->getMockBuilder(DataConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->getMockForAbstractClass();
        $this->reportGridBlockMock = $this->getMockBuilder(Grid::class)
            ->disableOriginalConstructor()
            ->setMethods(['setId', 'setGridData'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->reportTabBlock = $this->objectManagerHelper->getObject(
            Tab::class,
            [
                'dataConverter' => $this->dataConverterMock
            ]
        );

        $this->reportTabBlock->setLayout($this->layoutMock);
        $this->setNewDependency();
    }

    /**
     * Set a new dependency mock object
     *
     * @deprecated
     */
    private function setNewDependency()
    {
        /**
         * @var EncryptorInterface|MockObject $encryptorMock
         */
        $encryptorMock = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $encryptorMock->expects($this->any())->method('getHash')->willReturnCallback(
            function ($str) {
                return $this->getHash($str);
            }
        );

        $reflection = new \ReflectionClass(get_class($this->reportTabBlock));
        $reflectionProperty = $reflection->getProperty('encryptor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->reportTabBlock, $encryptorMock);
    }

    public function testGetGridsNoData()
    {
        $this->assertEquals([], $this->reportTabBlock->getGrids());
    }

    /**
     * @param array $inputData
     * @param array $ids
     * @param array $gridsData
     *
     * @dataProvider getGridsDataProvider
     */
    public function testGetGrids(
        array $inputData,
        array $ids,
        array $gridsData
    ) {
        $this->reportTabBlock->setData('grids_data', $inputData);

        $this->layoutMock->expects($this->any())
            ->method('createBlock')
            ->with(Grid::class, '', [])
            ->willReturn($this->reportGridBlockMock);

        call_user_func_array(
            [
                $this->reportGridBlockMock->expects($this->any())
                    ->method('setId'),
                'withConsecutive'
            ],
            $ids
        )->willReturnSelf();
        call_user_func_array(
            [
                $this->reportGridBlockMock->expects($this->any())
                    ->method('setGridData'),
                'withConsecutive'
            ],
            $gridsData
        )->willReturnSelf();

        $result = $this->reportTabBlock->getGrids();
        $this->assertNotNull($result);
    }

    /**
     * @return array
     */
    public function getGridsDataProvider()
    {
        return [
            [
                'inputData' => [VersionSection::class => [
                    'Magento Version' => [
                        'column_sizes' => [],
                        'header' => [],
                        'data' => [],
                        'count' => 1
                    ]
                ], DataCountSection::class => [
                    'Data Count' => [
                        'error' => 'Something wrong happened'
                    ]
                ], CacheStatusSection::class => [
                    'Cache Status' => [
                        'column_sizes' => [],
                        'header' => [],
                        'data' => [],
                        'count' => 11
                    ]
                ]
                ],
                'ids' => [
                    ['grid_' . $this->getHash('Magento Version')],
                    ['grid_' . $this->getHash('Cache Status')]
                ],
                'gridsData' => [
                    [
                        [
                            'column_sizes' => [],
                            'header' => [],
                            'data' => [],
                            'count' => 1
                        ]
                    ],
                    [
                        [
                            'column_sizes' => [],
                            'header' => [],
                            'data' => [],
                            'count' => 11
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testGetTabLabel()
    {
        $this->assertEquals(__('Report'), $this->reportTabBlock->getTabLabel());
    }

    public function testGetTabTitle()
    {
        $this->assertEquals(__('Report'), $this->reportTabBlock->getTabTitle());
    }

    public function testCanShowTab()
    {
        $this->assertTrue($this->reportTabBlock->canShowTab());
    }

    public function testIsHidden()
    {
        $this->assertFalse($this->reportTabBlock->isHidden());
    }

    private function getHash($str)
    {
        return hash('md5', $str);
    }
}
