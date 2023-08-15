<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SearchStaging\Block;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Test cases for quick search
 *
 * @magentoAppArea frontend
 */
class ViewTest extends AbstractController
{

    /**
     * @var \Magento\Staging\Model\VersionManagerFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $versionManagerFactoryMock;

    /**
     * @var \Magento\Staging\Model\VersionManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $versionManagerMock;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * Create basic mock objects
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->versionManagerMock =
            $this->createPartialMock(\Magento\Staging\Model\VersionManager::class, ['isPreviewVersion']);
        $this->versionManagerFactoryMock =
            $this->createPartialMock(\Magento\Staging\Model\VersionManagerFactory::class, ['create']);
        $this->versionManagerFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->versionManagerMock);
        $this->layout = $this->objectManager->get(LayoutInterface::class);
    }

    /**
     *
     * @dataProvider previewVersionDataProvider
     * @param bool $previewVersion
     * @return void
     */
    public function testPreviewSearch(bool $previewVersion): void
    {
        $objectManager = new ObjectManager($this);
        $this->versionManagerMock->expects($this->any())
            ->method('isPreviewVersion')
            ->willReturn($previewVersion);

        $request =  $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMockForAbstractClass();

        $request->expects($this->any())
            ->method('getParams')
            ->willReturn([
                '___version' => 123455,
                '__timestamp' => 12345666,
                '__signature' => '287ff44b9eb62be4cff081ab26e7282e31f49b33af3ef232cd3351cf31ae9248',
            ]);

        $searchQueryParamsViewModel = $objectManager->getObject(
            \Magento\SearchStaging\ViewModel\AdditionalSearchFormData::class,
            [
                'request' => $request,
                'versionManager' => $this->versionManagerMock
            ]
        );

        $configProviderViewModel = $this->getMockBuilder(\Magento\Search\ViewModel\ConfigProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSuggestionsAllowed','getSearchHelperData'])
            ->getMock();

        $block = $this->layout->createBlock(\Magento\Framework\View\Element\Template::class)
            ->setData([
                'configProvider'=>$configProviderViewModel,
                'additionalSearchFormData' => $searchQueryParamsViewModel,
            ])
            ->setTemplate('Magento_Search::form.mini.phtml');

        if ($previewVersion) {
            $this->assertStringContainsString('___version', $block->toHtml());
            $this->assertStringContainsString('__signature', $block->toHtml());
            $this->assertStringContainsString('__timestamp', $block->toHtml());
        } else {
            $this->assertStringNotContainsString('___version', $block->toHtml());
            $this->assertStringNotContainsString('__signature', $block->toHtml());
            $this->assertStringNotContainsString('__timestamp', $block->toHtml());

        }
    }

    /**
     * Staging preview mode provider
     *
     * @return array
     */
    public function previewVersionDataProvider()
    {
        return [
            [true], // preview mode turned on
            [false] // preview mode turned off
        ];
    }
}
