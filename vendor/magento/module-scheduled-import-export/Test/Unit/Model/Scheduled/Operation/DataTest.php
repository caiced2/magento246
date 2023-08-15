<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScheduledImportExport\Test\Unit\Model\Scheduled\Operation;

use Magento\ImportExport\Model\Import\Config;
use Magento\ScheduledImportExport\Model\Scheduled\Operation\Data;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Data
     */
    protected $model;

    protected function setUp(): void
    {
        $importConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exportConfigMock = $this->getMockBuilder(\Magento\ImportExport\Model\Export\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new Data(
            $importConfigMock,
            $exportConfigMock
        );
    }

    /**
     * Test getServerTypesOptionArray()
     */
    public function testGetServerTypesOptionArray()
    {
        $expected = [
            Data::FILE_STORAGE => 'Local Server',
            Data::FTP_STORAGE => 'Remote FTP',
        ];
        $result = $this->model->getServerTypesOptionArray();
        $this->assertEquals($expected, $result);
    }
}
