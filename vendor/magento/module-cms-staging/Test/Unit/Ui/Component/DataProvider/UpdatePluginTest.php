<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Ui\Component\DataProvider;

use Magento\CmsStaging\Ui\Component\DataProvider\UpdatePlugin;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
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

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilderMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);
        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new UpdatePlugin(
            $this->requestMock,
            $this->updateRepositoryMock,
            $this->filterBuilderMock
        );
    }

    /**
     * @param int|null $updateId
     * @param int $requestUpdateId
     * @param bool $isUpdateExists
     * @dataProvider getUpdateDataProvider
     */
    public function testBeforeGetSearchResult($updateId, $requestUpdateId, $isUpdateExists)
    {
        /** @var DataProviderInterface $dataProviderMock */
        $dataProviderMock = $this->getMockBuilder(
            DataProviderInterface::class
        )->getMockForAbstractClass();

        $filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($filterMock);
        $updateMock = $this->createMock(Update::class);
        $updateMock->expects($this->any())->method('getId')->willReturn($isUpdateExists ? $updateId : false);

        $this->requestMock->expects($this->any())->method('getParam')->willReturn($requestUpdateId);
        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);

        if ($isUpdateExists) {
            $dataProviderMock->expects($this->once())->method('addFilter')->with($filterMock);
        } else {
            $dataProviderMock->expects($this->never())->method('addFilter');
        }

        $this->plugin->beforeGetSearchResult($dataProviderMock);
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
