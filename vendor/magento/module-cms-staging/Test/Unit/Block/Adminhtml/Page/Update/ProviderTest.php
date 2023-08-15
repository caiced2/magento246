<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Block\Adminhtml\Page\Update;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\CmsStaging\Block\Adminhtml\Page\Update\Provider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Ui\Component\Listing\Column\Entity\UrlProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $pageRepositoryMock;

    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $versionManagerMock;

    /**
     * @var MockObject
     */
    private $urlProviderMock;

    /**
     * @var Provider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->pageRepositoryMock = $this->getMockForAbstractClass(PageRepositoryInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->urlProviderMock = $this->createMock(
            UrlProviderInterface::class
        );

        $this->versionManagerMock = $this->createMock(VersionManager::class);

        $this->provider = new Provider(
            $this->requestMock,
            $this->pageRepositoryMock,
            $this->versionManagerMock,
            $this->urlProviderMock
        );
    }

    public function testGetIdReturnsPageIdIfPageExists()
    {
        $pageId = 1;

        $pageMock = $this->getMockForAbstractClass(PageInterface::class);
        $pageMock->expects($this->any())->method('getId')->willReturn($pageId);

        $this->requestMock->expects($this->any())->method('getParam')->with('page_id')->willReturn($pageId);
        $this->pageRepositoryMock->expects($this->any())->method('getById')->with($pageId)->willReturn($pageMock);

        $this->assertEquals($pageId, $this->provider->getId());
    }

    public function testGetIdReturnsNullIfPageDoesNotExist()
    {
        $pageId = 9999;

        $this->requestMock->expects($this->any())->method('getParam')->with('page_id')->willReturn($pageId);
        $this->pageRepositoryMock->expects($this->any())
            ->method('getById')
            ->with($pageId)
            ->willThrowException(NoSuchEntityException::singleField('page_id', $pageId));

        $this->assertNull($this->provider->getId());
    }

    public function testGetUrlReturnsUrlBasedOnPageDataIfPageExists()
    {
        $expectedResult = 'http://www.example.com';
        $currentVersionId = 1;
        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $updateMock->expects($this->any())->method('getId')->willReturn($currentVersionId);
        $this->versionManagerMock->expects($this->any())->method('getCurrentVersion')->willReturn($updateMock);

        $pageId = 1;
        $pageData = [
            'id' => $pageId,
        ];
        $pageMock = $this->createMock(Page::class);
        $pageMock->expects($this->any())->method('getId')->willReturn($pageId);
        $pageMock->expects($this->any())->method('getData')->willReturn($pageData);

        $this->requestMock->expects($this->any())->method('getParam')->with('page_id')->willReturn($pageId);
        $this->pageRepositoryMock->expects($this->any())->method('getById')->with($pageId)->willReturn($pageMock);

        $this->urlProviderMock->expects($this->any())->method('getUrl')->with($pageData)->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->provider->getUrl(1));
    }

    public function testGetUrlReturnsNullIfPageDoesNotExist()
    {
        $currentVersionId = 1;
        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $updateMock->expects($this->any())->method('getId')->willReturn($currentVersionId);
        $this->versionManagerMock->expects($this->any())->method('getCurrentVersion')->willReturn($updateMock);

        $pageId = 9999;
        $this->requestMock->expects($this->any())->method('getParam')->with('page_id')->willReturn($pageId);
        $this->pageRepositoryMock->expects($this->any())
            ->method('getById')
            ->with($pageId)
            ->willThrowException(NoSuchEntityException::singleField('page_id', $pageId));

        $this->urlProviderMock->expects($this->never())->method('getUrl');

        $this->assertNull($this->provider->getUrl(1));
    }
}
