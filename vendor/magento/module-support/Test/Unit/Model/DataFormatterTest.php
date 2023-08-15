<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model;

use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\DataFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataFormatterTest extends TestCase
{
    /**
     * @var DataFormatter
     */
    protected $dataFormatter;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory|MockObject
     */
    protected $dataTimeFactoryMock;

    /**
     * @var DateTime|MockObject
     */
    protected $dateTimeMock;

    protected function setUp(): void
    {
        $this->dataTimeFactoryMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataTimeFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->dateTimeMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->dataFormatter = $this->objectManagerHelper->getObject(
            DataFormatter::class,
            [
                'dateFactory' => $this->dataTimeFactoryMock
            ]
        );
    }

    /**
     * @param string $inputData
     * @param string $currentDate
     * @param Phrase|string $sinceTimeString
     *
     * @dataProvider getSinceTimeStringDataProvider
     */
    public function testGetSinceTimeString($inputData, $currentDate, $sinceTimeString)
    {
        $this->dateTimeMock->expects($this->once())
            ->method('gmtDate')
            ->with(null, null)
            ->willReturn($currentDate);

        $this->assertEquals($sinceTimeString, $this->dataFormatter->getSinceTimeString($inputData));
    }

    /**
     * @return array
     */
    public function getSinceTimeStringDataProvider()
    {
        return [
            'future' => [
                'inputDate' => '2016-03-26 04:10:01',
                'currentDate' => '2015-07-29 12:19:37',
                'sinceTimeString' => ''
            ],
            'now' => [
                'inputDate' => '2015-07-29 12:19:37',
                'currentDate' => '2015-07-29 12:19:37',
                'sinceTimeString' => __('(now)')
            ],
            'minutes' => [
                'inputDate' => '2015-07-29 11:46:25',
                'currentDate' => '2015-07-29 12:19:37',
                'sinceTimeString' => __('[%1 minutes ago]', 33)
            ],
            'hours' => [
                'inputDate' => '2015-07-28 23:13:25',
                'currentDate' => '2015-07-29 12:19:37',
                'sinceTimeString' => __('[%1 hours ago]', 13)
            ],
            'days' => [
                'inputDate' => '2015-07-24 22:59:55',
                'currentDate' => '2015-07-29 12:19:37',
                'sinceTimeString' => __('[%1 days ago]', 5)
            ],
            'weeks' => [
                'inputDate' => '2015-07-20 03:40:31',
                'currentDate' => '2015-07-29 12:19:37',
                'sinceTimeString' => __('[%1 weeks ago]', 1)
            ],
            'months' => [
                'inputDate' => '2014-11-12 06:09:11',
                'currentDate' => '2015-07-29 12:19:37',
                'sinceTimeString' => __('[%1 months ago]', 9)
            ],
            'years' => [
                'inputDate' => '2006-01-03 22:12:57',
                'currentDate' => '2015-07-29 12:19:37',
                'sinceTimeString' => __('[%1 years ago]', 10)
            ]
        ];
    }

    /**
     * @param int $val
     * @param int $digits
     * @param string $mode
     * @param string $bB
     * @param string $formattedBytes
     *
     * @dataProvider formatBytesDataProvider
     */
    public function testFormatBytes($val, $digits, $mode, $bB, $formattedBytes)
    {
        $this->assertEquals(
            $formattedBytes,
            $this->dataFormatter->formatBytes($val, $digits, $mode, $bB)
        );
    }

    /**
     * @return array
     */
    public function formatBytesDataProvider()
    {
        return [
            [12345, 3, 'SI', 'B', '12.3 KiB'],
            [12345, 6, 'SI', 'B', '12.345 KiB'],
            [12345, 3, 'IEC', 'B', '12.1 kB'],
            [12345, 6, 'IEC', 'B', '12.0557 kB'],
            [12345, 3, 'SI', 'b', '98.8 Kib'],
            [12345, 6, 'SI', 'b', '98.76 Kib'],
            [12345, 3, 'IEC', 'b', '96.4 kb'],
            [12345, 6, 'IEC', 'b', '96.4453 kb']
        ];
    }
}
