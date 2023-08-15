<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Block\Adminhtml\Update\IdProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IdProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var IdProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);

        $this->provider = new IdProvider(
            $this->requestMock,
            $this->updateRepositoryMock
        );
    }

    public function testGetUpdateIdReturnsIdIfUpdateExists()
    {
        $updateId = 1;
        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $updateMock->expects($this->any())->method('getId')->willReturn($updateId);
        $this->requestMock->expects($this->any())->method('getParam')->with('update_id')->willReturn($updateId);
        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);

        $this->assertEquals($updateId, $this->provider->getUpdateId());
    }

    public function testGetUpdateIdReturnsNullIfUpdateDoesNotExist()
    {
        $updateId = 9999;
        $this->requestMock->expects($this->any())->method('getParam')->with('update_id')->willReturn($updateId);
        $this->updateRepositoryMock->expects($this->any())
            ->method('get')
            ->with($updateId)
            ->willThrowException(NoSuchEntityException::singleField('id', $updateId));

        $this->assertNull($this->provider->getUpdateId());
    }
}
