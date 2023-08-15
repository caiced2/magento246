<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update\Entity;

use Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\PreviewButton;
use Magento\Staging\Block\Adminhtml\Update\IdProvider;
use Magento\Staging\Model\Preview\UrlBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreviewButtonTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $entityProviderMock;

    /**
     * @var MockObject
     */
    protected $updateIdProviderMock;

    /**
     * @var MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var PreviewButton
     */
    protected $button;

    protected function setUp(): void
    {
        $this->entityProviderMock = $this->createMock(
            EntityProviderInterface::class
        );
        $this->updateIdProviderMock = $this->createMock(IdProvider::class);

        $this->urlBuilderMock = $this->createMock(UrlBuilder::class);
        $this->button = new PreviewButton(
            $this->entityProviderMock,
            $this->updateIdProviderMock,
            $this->urlBuilderMock
        );
    }

    public function testGetButtonDataNoUpdate()
    {
        $this->updateIdProviderMock->expects($this->once())->method('getUpdateId')->willReturn(null);
        $this->assertEmpty($this->button->getButtonData());
    }

    public function testGetButtonData()
    {
        $checkFields = ['label', 'url', 'sort_order'];
        $updateId = 223335;
        $this->updateIdProviderMock->expects($this->exactly(3))->method('getUpdateId')->willReturn($updateId);
        $this->entityProviderMock->expects($this->once())->method('getUrl');
        $this->urlBuilderMock->expects($this->once())->method('getPreviewUrl');

        $result = $this->button->getButtonData();
        foreach ($checkFields as $field) {
            $this->assertArrayHasKey($field, $result);
        }
    }
}
