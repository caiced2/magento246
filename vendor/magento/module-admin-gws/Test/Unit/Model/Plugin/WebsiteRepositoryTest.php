<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model\Plugin;

use Magento\AdminGws\Model\Plugin\WebsiteRepository;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebsiteRepositoryTest extends TestCase
{
    /**
     * @var WebsiteRepository
     */
    private $model;

    /**
     * @var WebsiteRepositoryInterface|MockObject
     */
    private $subjectMock;

    /**
     * @var string
     */
    private $returnValue;

    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockBuilder(WebsiteRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->returnValue = 'randomValue';
        $this->model = new WebsiteRepository();
    }

    public function testGetDefaultNoException()
    {
        $closure = function () {
            return $this->returnValue;
        };

        $this->subjectMock->expects($this->never())
            ->method('getById');

        $this->assertEquals($this->returnValue, $this->model->aroundGetDefault($this->subjectMock, $closure));
    }

    public function testGetDefaultDomainExceptionThrown()
    {
        $closure = function () {
            throw new \DomainException();
        };

        $websiteMock = $this->getMockBuilder(WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->subjectMock->expects($this->once())
            ->method('getById')
            ->willReturn($websiteMock);

        $this->assertSame($websiteMock, $this->model->aroundGetDefault($this->subjectMock, $closure));
    }
}
