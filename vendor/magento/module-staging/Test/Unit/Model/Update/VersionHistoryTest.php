<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Update;

use Magento\Staging\Model\Update\Flag;
use Magento\Staging\Model\Update\FlagFactory;
use Magento\Staging\Model\Update\VersionHistory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VersionHistoryTest extends TestCase
{
    /**
     * @var Flag|MockObject
     */
    private $flagMock;

    /**
     * @var FlagFactory|MockObject
     */
    private $flagFactoryMock;

    /**
     * @var VersionHistory
     */
    private $versionHistory;

    protected function setUp(): void
    {
        $this->flagFactoryMock = $this->getMockBuilder(FlagFactory::class)
            ->setMethods(
                ['create']
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->flagMock = $this->getMockBuilder(Flag::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionHistory = new VersionHistory(
            $this->flagFactoryMock
        );
    }

    public function testPersistCurrentVersion()
    {
        $currentId = 11;
        $this->flagFactoryMock->expects($this->once())->method('create')->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())->method('setCurrentVersionId')->with($currentId);
        $this->flagMock->expects($this->once())->method('save');
        $this->flagMock->expects($this->once())->method('loadSelf');
        $this->versionHistory->setCurrentId($currentId);
    }

    public function testPersistMaximumVersionsInDb()
    {
        $maxInDb = 5;
        $this->flagFactoryMock->expects($this->once())->method('create')->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())->method('setMaximumVersionsInDb')->with($maxInDb);
        $this->flagMock->expects($this->once())->method('save');
        $this->flagMock->expects($this->once())->method('loadSelf');
        $this->versionHistory->setMaximumInDB($maxInDb);
    }

    public function testRetrieveCurrentVersion()
    {
        $currentId = 11;
        $this->flagFactoryMock->expects($this->once())->method('create')->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())->method('getCurrentVersionId')->willReturn($currentId);
        $this->flagMock->expects($this->once())->method('loadSelf');
        $this->assertEquals($currentId, $this->versionHistory->getCurrentId());
    }

    public function testRetrieveMaximumVersionsInDb()
    {
        $maxInDb = 11;
        $this->flagFactoryMock->expects($this->once())->method('create')->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())->method('getMaximumVersionsInDb')->willReturn($maxInDb);
        $this->flagMock->expects($this->once())->method('loadSelf');
        $this->assertEquals($maxInDb, $this->versionHistory->getMaximumInDB());
    }
}
