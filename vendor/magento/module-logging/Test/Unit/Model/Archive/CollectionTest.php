<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Test\Unit\Model\Archive;

use Magento\Backup\Helper\Data;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Logging\Model\Archive;
use Magento\Logging\Model\Archive\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Test \Magento\Logging\Model\Archive\Collection
 */
class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->markTestSkipped('Skipped in #27500 due to testing protected/private methods and properties');
        $this->objectManager = new ObjectManager($this);

        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $directoryWrite = $this->getMockBuilder(
            WriteInterface::class
        )->disableOriginalConstructor()
            ->getMock();
        $filesystem->expects($this->any())->method('getDirectoryWrite')->willReturn($directoryWrite);

        $backupData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $backupData->expects($this->any())->method('getExtensions')->willReturn([]);

        $archive = $this->getMockBuilder(Archive::class)
            ->disableOriginalConstructor()
            ->getMock();
        $archive->expects($this->any())->method('getBasePath')->willReturn(__DIR__ . '/_files');
        $targetDirectoryWrite = $this->getMockBuilder(WriteInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $targetDirectoryWrite->expects($this->any())->method('isDirectory')->willReturn(true);
        $targetDirectory = $this->getMockBuilder(TargetDirectory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $targetDirectory->expects($this->any())->method('getDirectoryWrite')->willReturn($targetDirectoryWrite);
        $this->collection = $this->objectManager->getObject(
            Collection::class,
            [
                'filesystem' => $filesystem,
                'backupData' => $backupData,
                'archive' => $archive,
            ]
        );
    }

    /**
     * Test generateRow()
     *
     * Calls loadData() which will cause generateRow function to be called, which updates the collection's
     * '_collectedFiles' attribute. It should be just one file and dates should be based on filename
     */
    public function testGenerateRow()
    {
        $this->markTestSkipped('Skipped in #27500 due to testing protected/private methods and properties');

        $this->collection->loadData();
        $actualCollectedFiles = $this->getObjectAttribute($this->collection, '_collectedFiles');
        $this->assertEquals(__DIR__ . '/_files/2016031415.csv', $actualCollectedFiles[0]['filename']);
        $this->assertEquals('2016031415.csv', $actualCollectedFiles[0]['basename']);
        $this->assertInstanceOf('DateTime', $actualCollectedFiles[0]['time']);
        $this->assertEquals('2016-03-14', $actualCollectedFiles[0]['timestamp']);

        /** @var \DateTime $date */
        $date = $actualCollectedFiles[0]['time'];
        $this->assertEquals('2016-03-14 15:00:00', $date->format('Y-m-d H:i:s'));
    }
}
