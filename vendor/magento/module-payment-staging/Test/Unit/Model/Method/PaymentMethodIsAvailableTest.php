<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PaymentStaging\Test\Unit\Model\Method;

use Magento\Payment\Model\MethodInterface;
use Magento\PaymentStaging\Plugin\Model\Method\PaymentMethodIsAvailable;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentMethodIsAvailableTest extends TestCase
{
    /**
     * Result of 'proceed' closure call
     */
    const PROCEED_RESULT = 'proceed';

    /**
     * @var PaymentMethodIsAvailable
     */
    private $plugin;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManager;

    /**
     * @var MethodInterface|MockObject
     */
    private $subject;

    /**
     * @var \Closure
     */
    private $proceed;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    protected function setUp(): void
    {
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getMockBuilder(MethodInterface::class)
            ->getMockForAbstractClass();

        $this->proceed = function () {
            return self::PROCEED_RESULT;
        };

        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->getMockForAbstractClass();

        $this->plugin = new PaymentMethodIsAvailable($this->versionManager);
    }

    /**
     * Return true for offline payment methods in preview version
     */
    public function testAroundIsAvailableIsPreviewVersionIsOffline()
    {
        $this->versionManager->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);

        $this->subject->expects($this->once())
            ->method('isOffline')
            ->willReturn(true);

        $this->assertSame(
            self::PROCEED_RESULT,
            $this->plugin->aroundIsAvailable(
                $this->subject,
                $this->proceed,
                $this->quote
            )
        );
    }

    /**
     * Return false for non-offline payment methods in preview version
     */
    public function testAroundIsAvailableIsPreviewVersionNotIsOffline()
    {
        $this->versionManager->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);

        $this->subject->expects($this->once())
            ->method('isOffline')
            ->willReturn(false);

        $this->assertFalse($this->plugin->aroundIsAvailable(
            $this->subject,
            $this->proceed,
            $this->quote
        ));
    }
}
