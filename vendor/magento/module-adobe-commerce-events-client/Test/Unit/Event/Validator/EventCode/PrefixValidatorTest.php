<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Validator\EventCode;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventCode\PrefixValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see PrefixValidator class
 */
class PrefixValidatorTest extends TestCase
{
    /**
     * @var PrefixValidator
     */
    private PrefixValidator $validator;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);

        $this->validator = new PrefixValidator();
    }

    public function testValidPrefix()
    {
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testInvalidPrefix()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Invalid event type "invalid".');

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('invalid.some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testInvalidEventCodeStructure()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Event code must consist of a type label and an event code separated by a dot');

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testValidPrefixWithParent()
    {
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.rule_based_event_code');
        $this->eventMock->expects(self::exactly(2))
            ->method('getParent')
            ->willReturn('observer.some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testInvalidParentPrefix()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Invalid event type "invalid".');

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.rule_based_event_code');
        $this->eventMock->expects(self::exactly(2))
            ->method('getParent')
            ->willReturn('invalid.some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testInvalidParentEventCodeStructure()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Event code must consist of a type label and an event code separated by a dot');

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.rule_based_event_code');
        $this->eventMock->expects(self::exactly(2))
            ->method('getParent')
            ->willReturn('some_event_code');

        $this->validator->validate($this->eventMock);
    }
}
