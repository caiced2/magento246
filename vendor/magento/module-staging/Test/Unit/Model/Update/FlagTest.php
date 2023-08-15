<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Update;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\Manager;
use Magento\Framework\Flag\FlagResource;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Staging\Model\Update\Flag;
use PHPUnit\Framework\TestCase;

class FlagTest extends TestCase
{
    /**
     * @var Flag
     */
    private $flag;

    protected function setUp(): void
    {
        $data = ['flag_code' => 'synchronize'];
        $this->createInstance($data);
    }

    private function createInstance(array $data = [])
    {
        $eventManager = $this->createPartialMock(Manager::class, ['dispatch']);
        $context = $this->createMock(Context::class);
        $context->expects($this->once())
            ->method('getEventDispatcher')
            ->willReturn($eventManager);
        $registry = $this->createMock(Registry::class);

        $connection = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['beginTransaction'])
            ->getMockForAbstractClass();

        $connection->expects($this->any())
            ->method('beginTransaction')->willReturnSelf();
        $appResource = $this->createMock(ResourceConnection::class);
        $appResource->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        $dbContextMock = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $dbContextMock->expects($this->once())->method('getResources')->willReturn($appResource);
        $resource = $this->getMockBuilder(FlagResource::class)
            ->setMethods(['load', 'save', 'addCommitCallback', 'commit', 'rollBack'])
            ->setConstructorArgs(['context' => $dbContextMock])
            ->getMock();
        $resource->expects($this->any())
            ->method('addCommitCallback')->willReturnSelf();

        $resourceCollection = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $json = $this->getMockBuilder(Json::class)
            ->setMethods(null)
            ->getMock();

        $serialize = $this->getMockBuilder(Serialize::class)
            ->setMethods(null)
            ->getMock();

        $this->flag = new Flag(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data,
            $json,
            $serialize
        );
    }

    protected function tearDown(): void
    {
        unset($this->flag);
    }

    public function testPersistCurrentVersionId()
    {
        $versionId = 22;
        $this->flag->setCurrentVersionId($versionId);
        $this->assertEquals(['current_version' => $versionId], $this->flag->getFlagData());
    }

    public function testPersistMaxVersionInDb()
    {
        $maxVersionInDb = 5;
        $this->flag->setMaximumVersionsInDb($maxVersionInDb);
        $this->assertEquals(['maximum_versions_in_db' => $maxVersionInDb], $this->flag->getFlagData());
    }

    public function testRetrieveCurrentVersionId()
    {
        $versionId = 22;
        $this->flag->setFlagData(['current_version' => $versionId]);
        $this->assertEquals($versionId, $this->flag->getCurrentVersionId());
    }

    public function testRetrieveMaxVersionInDb()
    {
        $maxVersionInDb = 5;
        $this->flag->setFlagData(['maximum_versions_in_db' => $maxVersionInDb]);
        $this->assertEquals($maxVersionInDb, $this->flag->getMaximumVersionsInDb());
    }
}
