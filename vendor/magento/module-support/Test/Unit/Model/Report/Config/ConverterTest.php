<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Config;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\Report\Config\Converter;
use Magento\Support\Model\Report\Group\Environment\EnvironmentSection;
use Magento\Support\Model\Report\Group\General\DataCountSection;
use Magento\Support\Model\Report\Group\General\VersionSection;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    /**
     * @var \DOMDocument
     */
    protected $source;

    /**
     * @var Converter
     */
    protected $converter;

    /**
     * @var string
     */
    protected $configDir;

    protected function setUp(): void
    {
        $this->source = new \DOMDocument();

        /** @var Converter $converter */
        $this->converter = (new ObjectManager($this))
            ->getObject(Converter::class);

        $this->configDir = realpath(__DIR__) . DIRECTORY_SEPARATOR . '_files/';
    }

    public function testConvertValidShouldReturnArray()
    {
        $expected = [
            'groups' => [
                'general' => [
                    'title' => __('General'),
                    'sections' => [
                        40 => VersionSection::class,
                        50 => DataCountSection::class
                    ],
                    'priority' => 10,
                    'data' => [
                        VersionSection::class => [],
                        DataCountSection::class => []
                    ]
                ],
                'environment' => [
                    'title' => __('Environment'),
                    'sections' => [
                        410 => EnvironmentSection::class
                    ],
                    'priority' => 30,
                    'data' => [EnvironmentSection::class => []
                    ]
                ]
            ]
        ];
        $this->source->load($this->configDir . 'report_valid.xml');
        $this->assertEquals($expected, $this->converter->convert($this->source));
    }

    /**
     * @param string $file
     * @param string $message
     * @dataProvider convertInvalidArgumentsDataProvider
     */
    public function testConvertInvalidArgumentsShouldThrowException($file, $message)
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage($message);
        $this->source->load($this->configDir . $file);
        $result = $this->converter->convert($this->source);
        $this->assertNotNull($result);
    }

    /**
     * @return array
     */
    public function convertInvalidArgumentsDataProvider()
    {
        return [
            ['report_absent_name.xml', 'Attribute "name" of one of "group"s does not exist'],
            ['report_absent_sections.xml', 'Tag "sections" of one of "group"s does not exist']
        ];
    }
}
