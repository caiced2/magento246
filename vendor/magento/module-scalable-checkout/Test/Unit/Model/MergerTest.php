<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScalableCheckout\Test\Unit\Model;

use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ScalableCheckout\Model\Merger;
use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for testing messages merge
 */
class MergerTest extends TestCase
{
    /**
     * @var Merger
     */
    private $merger;

    /**
     * @var MergedMessageInterfaceFactory|MockObject
     */
    private $mergedMessageFactory;

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->mergedMessageFactory = $this->getMockBuilder(MergedMessageInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->merger = $objectManager->getObject(
            Merger::class,
            [
                'mergedMessageFactory' => $this->mergedMessageFactory
            ]
        );
    }

    /**
     * Testing method merge with several messages.
     *
     * @return void
     */
    public function testMerge(): void
    {
        $originalMessage = $this->getMockBuilder(EnvelopeInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $originalMessageSecond = $this->getMockBuilder(EnvelopeInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $messages = [[$originalMessage, $originalMessageSecond]];
        $expected = [
            'mergedMessage' => $originalMessageSecond,
            'originalMessagesIds' => [0, 1]
        ];
        $this->mergedMessageFactory
            ->method('create')
            ->withConsecutive([], [$expected]);

        $this->merger->merge($messages);
    }
}
