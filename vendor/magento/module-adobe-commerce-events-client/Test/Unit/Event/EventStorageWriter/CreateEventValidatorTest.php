<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\EventStorageWriter;

use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventStorageWriter\CreateEventValidator;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CreateEventValidator class
 */
class CreateEventValidatorTest extends TestCase
{
    /**
     * @var CreateEventValidator
     */
    private CreateEventValidator $createEventValidator;

    /**
     * @var Config|MockObject
     */
    private $eventConfigurationMock;

    /**
     * @var RuleChecker|MockObject
     */
    private $ruleCheckerMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->eventConfigurationMock = $this->createMock(Config::class);
        $this->ruleCheckerMock = $this->createMock(RuleChecker::class);
        $this->createEventValidator = new CreateEventValidator(
            $this->eventConfigurationMock,
            $this->ruleCheckerMock,
        );
    }

    public function testConfigurationIsNotEnabled(): void
    {
        $this->eventConfigurationMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->ruleCheckerMock->expects(self::never())
            ->method('verify');

        self::assertFalse($this->createEventValidator->validate($this->eventMock, ['some_data']));
    }

    public function testRuleCheckerException(): void
    {
        $this->expectException(OperatorException::class);
        $this->expectExceptionMessage('operator error happened');

        $this->eventConfigurationMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->ruleCheckerMock->expects(self::once())
            ->method('verify')
            ->with($this->eventMock, ['some_data'])
            ->willThrowException(new OperatorException(__('operator error happened')));

        self::assertFalse($this->createEventValidator->validate($this->eventMock, ['some_data']));
    }

    public function testRuleCheckerFalse(): void
    {
        $this->eventConfigurationMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->ruleCheckerMock->expects(self::once())
            ->method('verify')
            ->with($this->eventMock, ['some_data'])
            ->willReturn(false);

        self::assertFalse($this->createEventValidator->validate($this->eventMock, ['some_data']));
    }

    public function testSuccessValidation(): void
    {
        $this->eventConfigurationMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->ruleCheckerMock->expects(self::once())
            ->method('verify')
            ->with($this->eventMock, ['some_data'])
            ->willReturn(true);

        self::assertTrue($this->createEventValidator->validate($this->eventMock, ['some_data']));
    }
}
