<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CheckoutStaging\Test\Unit\Plugin;

use Magento\CheckoutStaging\Model\PreviewQuota;
use Magento\CheckoutStaging\Model\PreviewQuotaFactory;
use Magento\CheckoutStaging\Model\PreviewQuotaRepository;
use Magento\CheckoutStaging\Plugin\SavePreviewQuotaPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SavePreviewQuotaPluginTest extends TestCase
{
    /**
     * @var VersionManager|MockObject
     */
    private $versionManager;

    /**
     * @var PreviewQuotaFactory|MockObject
     */
    private $previewQuotaFactory;

    /**
     * @var PreviewQuotaRepository|MockObject
     */
    private $previewQuotaRepository;

    /**
     * @var CartInterface|MockObject
     */
    private $cart;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepository;

    /**
     * @var PreviewQuota|MockObject
     */
    private $previewQuota;

    /**
     * @var SavePreviewQuotaPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['isPreviewVersion'])
            ->getMock();
        $this->previewQuotaFactory = $this->getMockBuilder(PreviewQuotaFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->previewQuotaRepository = $this->getMockBuilder(PreviewQuotaRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cart = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $this->cartRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->previewQuota = $this->getMockBuilder(PreviewQuota::class)
            ->disableOriginalConstructor()
            ->setMethods(['setId'])
            ->getMock();

        $this->plugin = $objectManager->getObject(
            SavePreviewQuotaPlugin::class,
            [
                'versionManager' => $this->versionManager,
                'previewQuotaFactory' => $this->previewQuotaFactory,
                'previewQuotaRepository' => $this->previewQuotaRepository
            ]
        );
    }

    public function testAfterSaveWithoutQuote()
    {
        $this->versionManager->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->cart->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->plugin->afterSave($this->cartRepository, null, $this->cart);
    }

    public function testAfterSaveWithQuote()
    {
        $this->versionManager->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->cart->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);
        $this->previewQuotaFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->previewQuota);
        $this->previewQuota->expects($this->once())
            ->method('setId')
            ->willReturnSelf();
        $this->previewQuotaRepository->expects($this->once())
            ->method('save')
            ->with($this->previewQuota)
            ->willReturnSelf();

        $this->plugin->afterSave($this->cartRepository, null, $this->cart);
    }
}
