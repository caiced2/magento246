<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Model\ResourceModel\Grid\Collection;

use Magento\Cms\Model\ResourceModel\AbstractCollection;
use Magento\CmsStaging\Model\ResourceModel\Grid\Collection\UpdatePlugin;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Select;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Update;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdatePluginTest extends TestCase
{
    /**
     * @var UpdatePlugin
     */
    private $plugin;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepositoryMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);

        $this->plugin = new UpdatePlugin(
            $this->requestMock,
            $this->updateRepositoryMock
        );
    }

    /**
     * @param int|null $updateId
     * @param int $requestUpdateId
     * @param bool $isUpdateExists
     * @dataProvider getUpdateDataProvider
     */
    public function testBeforeGetItems($updateId, $requestUpdateId, $isUpdateExists)
    {
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collectionMock = $this->getMockBuilder(AbstractCollection::class)
            ->setMethods(['getSelect'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $collectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);

        $updateMock = $this->createMock(Update::class);
        $updateMock->expects($this->any())->method('getId')->willReturn($isUpdateExists ? $updateId : false);

        $this->requestMock->expects($this->any())->method('getParam')->willReturn($requestUpdateId);
        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);

        if ($isUpdateExists) {
            $selectMock->expects($this->once())->method('setPart')->with('disable_staging_preview', true);
        } else {
            $selectMock->expects($this->never())->method('setPart');
        }

        $this->plugin->beforeGetItems($collectionMock);
    }

    /**
     * Update data provider
     *
     * @return array
     */
    public function getUpdateDataProvider()
    {
        return [
            [1, 1, true],//update exists
            [123, 123, false],//update does not exist
        ];
    }
}
