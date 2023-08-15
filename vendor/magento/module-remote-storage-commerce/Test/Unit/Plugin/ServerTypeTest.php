<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RemoteStorageCommerce\Test\Unit\Plugin;

use Magento\ScheduledImportExport\Model\Scheduled\Operation\Data;
use PHPUnit\Framework\TestCase;
use Magento\RemoteStorage\Model\Config;
use Magento\RemoteStorageCommerce\Plugin\ServerType;

class ServerTypeTest extends TestCase
{
    /**
     * @var ServerType
     */
    private $plugin;

    protected function setUp(): void
    {
        /** @var @var Config|MockObject $remoteStorageConfigMock */
        $remoteStorageConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $remoteStorageConfigMock->expects(self::atLeastOnce())
            ->method('isEnabled')
            ->willReturn(true);

        $this->plugin = new ServerType($remoteStorageConfigMock);
    }

    /**
     * @dataProvider getFileStorageDataProvider
     * @param array $expected
     * @return void
     */
    public function testAfterGetServerTypesOptionArray(array $expected): void
    {
        /** @var Data $subject */
        $subject = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertEquals($expected, $this->plugin->afterGetServerTypesOptionArray($subject, []));
    }

    /**
     * @return array
     */
    public function getFileStorageDataProvider(): array
    {
        return [
            [
                [
                    Data::FILE_STORAGE => 'Remote Storage',
                    Data::FTP_STORAGE => 'Remote FTP',
                ]
            ]
        ];
    }
}
