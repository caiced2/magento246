<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Model;

use Magento\Banner\Model\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var []
     */
    protected $bannerTypes;

    protected function setConfigForGetTypesOptionArray()
    {
        $this->bannerTypes = [
            'left_column',
            'right_column',
            'footer',
            'header',
            'content_area',
        ];

        $this->config = new Config(
            $this->bannerTypes
        );
    }

    protected function setConfigForExplodeTypes()
    {
        $this->bannerTypes = [
            'left_column' => 'left_column',
            'right_column' => 'right_column',
            'footer' => 'footer',
            'header' => 'header',
            'content_area' => 'content_area',
        ];

        $this->config = new Config(
            $this->bannerTypes
        );
    }

    /**
     * @param [] $expected
     * @param bool $sortedFlag
     * @param bool $withEmptyFlag
     *
     * @dataProvider getTypesDataProvider
     */
    public function testGetTypes($expected, $sortedFlag, $withEmptyFlag)
    {
        $this->setConfigForGetTypesOptionArray();
        $this->assertEquals($expected, $this->config->getTypes($sortedFlag, $withEmptyFlag));
    }

    /**
     * @param [] $expected
     * @param bool $sortedFlag
     * @param bool $withEmptyFlag
     *
     * @dataProvider toOptionArrayDataProvider
     */
    public function testToOptionArray($expected, $sortedFlag, $withEmptyFlag)
    {
        $this->setConfigForGetTypesOptionArray();
        $this->assertEquals($expected, $this->config->toOptionArray($sortedFlag, $withEmptyFlag));
    }

    /**
     * @param [] $expected
     * @param string|[] $types
     *
     * @dataProvider explodeTypesDataProvider
     */
    public function testExplodeTypes($expected, $types)
    {
        $this->setConfigForExplodeTypes();
        $this->assertEquals($expected, $this->config->explodeTypes($types));
    }

    public function getTypesDataProvider()
    {
        return [
            [
                [
                    'left_column',
                    'right_column',
                    'footer',
                    'header',
                    'content_area',
                ],
                null,
                null,
            ],
            [
                [
                    '' => '-- None --',
                    'left_column',
                    'right_column',
                    'footer',
                    'header',
                    'content_area',
                ],
                false, true
            ],
            [
                [
                    '4' => 'content_area',
                    '2' => 'footer',
                    '3' => 'header',
                    '0' => 'left_column',
                    '1' => 'right_column',
                ],
                true, false
            ]
        ];
    }

    public function toOptionArrayDataProvider()
    {
        return [
            [
                [
                    '0' => [
                        'value' => 4,
                        'label' => 'content_area',
                    ],
                    '1' => [
                        'value' => 2,
                        'label' => 'footer',
                    ],
                    '2' => [
                        'value' => 3,
                        'label' => 'header',
                    ],
                    '3' => [
                        'value' => 0,
                        'label' => 'left_column',
                    ],
                    '4' => [
                        'value' => 1,
                        'label' => 'right_column',
                    ],
                ],
                null,
                null,
            ],
            [
                [
                    '0' => [
                        'value' => '',
                        'label' => '-- None --',
                    ],
                    '1' => [
                        'value' => 0,
                        'label' => 'content_area',
                    ],
                    '2' => [
                        'value' => 1,
                        'label' => 'footer',
                    ],
                    '3' => [
                        'value' => 2,
                        'label' => 'header',
                    ],
                    '4' => [
                        'value' => 3,
                        'label' => 'left_column',
                    ],
                    '5' => [
                        'value' => 4,
                        'label' => 'right_column',
                    ],
                ],
                false, true
            ],
            [
                [
                    '4' => 'content_area',
                    '2' => 'footer',
                    '3' => 'header',
                    '0' => 'left_column',
                    '1' => 'right_column',
                ],
                true, false
            ]
        ];
    }

    public function explodeTypesDataProvider()
    {
        return [
            [
                [
                    'content_area',
                ],
                'content_area',
            ],
            [
                [
                    'content_area',
                    'left_column',
                    'right_column',
                    'header',
                    'footer',
                ],
                [
                    'content_area',
                    'left_column',
                    'right_column',
                    'header',
                    'footer'
                ]

            ]
        ];
    }
}
