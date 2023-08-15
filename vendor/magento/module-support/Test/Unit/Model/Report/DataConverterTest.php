<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report;

use Magento\Framework\Exception\StateException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\DataConverter;
use PHPUnit\Framework\TestCase;

class DataConverterTest extends TestCase
{
    /**
     * @var DataConverter
     */
    protected $dataConverter;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        /** @var  $objectManagerHelper */
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->dataConverter = $objectManagerHelper->getObject(DataConverter::class);
    }

    /**
     * @param array $data
     * @return void
     * @dataProvider prepareDataThrowExceptionDataProvider
     */
    public function testPrepareDataThrowException($data)
    {
        $this->expectException(StateException::class);
        $this->expectExceptionMessage(
            'Preparing system report data: Detected Single Row Mode but data may be incomplete.'
        );

        $this->dataConverter->prepareData($data);
    }

    /**
     * @return array
     */
    public function prepareDataThrowExceptionDataProvider()
    {
        return [
            [
                'data' => [
                    'headers' => ['Header1', 'Header2'],
                    'data' => [['test1'], 'test2']
                ]
            ],
            [
                'data' => [
                    'headers' => ['Header1', 'Header2'],
                    'data' => ['test1', ['test2']]
                ]
            ],
        ];
    }

    /**
     * @param array $data
     * @param array $expectedResult
     * @return void
     * @dataProvider prepareDataDataProvider
     */
    public function testPrepareData($data, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->dataConverter->prepareData($data));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function prepareDataDataProvider()
    {
        $columnTitle = 'Column %1';

        return [
            0 => [
                'data' => ['headers' => [], 'data' => []],
                'expectedResult' => ['column_sizes' => [], 'headers' => [], 'data' => [], 'count' => 0]
            ],
            1 => [
                'data' => [
                    'headers' => [],
                    'data' => [['test', 'test2'], ['test3']]
                ],
                'expectedResult' => [
                    'column_sizes' => [8, 8],
                    'headers' => [__($columnTitle, 1), __($columnTitle, 2)],
                    'data' => [['test', 'test2'], ['test3', '']],
                    'count' => 2
                ]
            ],
            2 => [
                'data' => [
                    'headers' => [],
                    'data' => [['test'], ['test2']]
                ],
                'expectedResult' => [
                    'column_sizes' => [8],
                    'headers' => [__($columnTitle, 1)],
                    'data' => [['test'], ['test2']],
                    'count' => 2
                ]
            ],
            3 => [
                'data' => [
                    'headers' => [],
                    'data' => ['test']
                ],
                'expectedResult' => [
                    'column_sizes' => [8],
                    'headers' => [__($columnTitle, 1)],
                    'data' => [['test']],
                    'count' => 1
                ]
            ],
            4 => [
                'data' => [
                    'headers' => ['Header1', 'Header2'],
                    'data' => ['test']
                ],
                'expectedResult' => [
                    'column_sizes' => [7, 7],
                    'headers' => ['Header1', 'Header2'],
                    'data' => [['test', '']],
                    'count' => 1
                ]
            ],
            5 => [
                'data' => [
                    'headers' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21],
                    'data' => [['test']]
                ],
                'expectedResult' => [
                    'column_sizes' => [4, 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 11],
                    'headers' => [
                        1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, __('And more...')
                    ],
                    'data' => [
                        ['test', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']
                    ],
                    'count' => 1
                ]
            ],
            6 => [
                'data' => [
                    'headers' => [],
                    'data' => [
                        [
                            'test1', 'test2', 'test3', 'test4', 'test5', 'test6', 'test7',
                            'test8', 'test9', 'test10', 'test11', 'test12', 'test13', 'test14',
                            'test15', 'test16', 'test17', 'test18', 'test19', 'test20', 'test21'
                        ]
                    ]
                ],
                'expectedResult' => [
                    'column_sizes' => [8, 8, 8, 8, 8, 8, 8, 8, 8, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 11],
                    'headers' => [
                        __($columnTitle, 1), __($columnTitle, 2), __($columnTitle, 3), __($columnTitle, 4),
                        __($columnTitle, 5), __($columnTitle, 6), __($columnTitle, 7), __($columnTitle, 8),
                        __($columnTitle, 9), __($columnTitle, 10), __($columnTitle, 11), __($columnTitle, 12),
                        __($columnTitle, 13), __($columnTitle, 14), __($columnTitle, 15), __($columnTitle, 16),
                        __($columnTitle, 17), __($columnTitle, 18), __($columnTitle, 19), __($columnTitle, 20),
                        __($columnTitle, 21)
                    ],
                    'data' => [
                        [
                            'test1', 'test2', 'test3', 'test4', 'test5', 'test6', 'test7',
                            'test8', 'test9', 'test10', 'test11', 'test12', 'test13', 'test14',
                            'test15', 'test16', 'test17', 'test18', 'test19', 'test20', __('And more...')
                        ]
                    ],
                    'count' => 1
                ]
            ],
            7 => [
                'data' => [
                    'headers' => [],
                    'data' => [
                        'test1', 'test2', 'test3', 'test4', 'test5', 'test6', 'test7',
                        'test8', 'test9', 'test10', 'test11', 'test12', 'test13', 'test14',
                        'test15', 'test16', 'test17', 'test18', 'test19', 'test20', 'test21'
                    ]
                ],
                'expectedResult' => [
                    'column_sizes' => [8, 8, 8, 8, 8, 8, 8, 8, 8, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 11],
                    'headers' => [
                        __($columnTitle, 1), __($columnTitle, 2), __($columnTitle, 3), __($columnTitle, 4),
                        __($columnTitle, 5), __($columnTitle, 6), __($columnTitle, 7), __($columnTitle, 8),
                        __($columnTitle, 9), __($columnTitle, 10), __($columnTitle, 11), __($columnTitle, 12),
                        __($columnTitle, 13), __($columnTitle, 14), __($columnTitle, 15), __($columnTitle, 16),
                        __($columnTitle, 17), __($columnTitle, 18), __($columnTitle, 19), __($columnTitle, 20),
                        __($columnTitle, 21)
                    ],
                    'data' => [
                        [
                            'test1', 'test2', 'test3', 'test4', 'test5', 'test6', 'test7',
                            'test8', 'test9', 'test10', 'test11', 'test12', 'test13', 'test14',
                            'test15', 'test16', 'test17', 'test18', 'test19', 'test20', __('And more...')
                        ]
                    ],
                    'count' => 1
                ]
            ],
            8 => [
                'data' => [
                    'headers' => [],
                    'data' => ['There is more 120 chars in this array. There is more 120 chars in this array.'
                        . 'There is more 120 chars in this array. There is more 120 chars in this array.']
                ],
                'expectedResult' => [
                    'column_sizes' => [120],
                    'headers' => [__($columnTitle, 1)],
                    'data' => [
                        [
                            'There is more 120 chars in this array. There is more 120 chars in this array.'
                                . 'There is more 120 chars in this array. There is more 120 chars in this array.'
                        ]
                    ],
                    'count' => 1
                ]
            ],
            9 => [
                'data' => [
                    'headers' => ['1', '2', '3', '4', '5', '6', '7', '8'],
                    'data' => [[null, true, false, 1, 1.5, new \stdClass(), 'str', [1, 2, 3]]]
                ],
                'expectedResult' => [
                    'column_sizes' => [4, 4, 5, 1, 3, 15, 3, 8],
                    'headers' => ['1', '2', '3', '4', '5', '6', '7', '8'],
                    'data' => [['null', 'true', 'false', 1, 1.5, 'Object stdClass', 'str', 'array(3)']],
                    'count' => 1
                ]
            ]
        ];
    }
}
