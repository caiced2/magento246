<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadataFactory;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriber;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeIoEventsClient\Api\EventMetadataInterface;
use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see EventSubscriber class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects))
 */
class EventSubscriberTest extends TestCase
{
    /**
     * @var EventSubscriber
     */
    private EventSubscriber $eventSubscriber;

    /**
     * @var Writer|MockObject
     */
    private $configWriterMock;

    /**
     * @var DeploymentConfig|MockObject
     */
    private $deploymentConfigMock;

    /**
     * @var EventValidatorInterface|MockObject
     */
    private $subscribeValidatorMock;

    /**
     * @var EventValidatorInterface|MockObject
     */
    private $unsubscribeValidatorMock;

    /**
     * @var AdobeIOConfigurationProvider|MockObject
     */
    private $configurationProviderMock;

    /**
     * @var AdobeIoEventMetadataFactory|MockObject
     */
    private $eventMetadataFactoryMock;

    /**
     * @var EventMetadataClient|MockObject
     */
    private $metadataClientMock;

    /**
     * @var EventList|MockObject
     */
    private $eventListMock;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->configWriterMock = $this->createMock(Writer::class);
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);
        $this->subscribeValidatorMock = $this->getMockForAbstractClass(EventValidatorInterface::class);
        $this->unsubscribeValidatorMock = $this->getMockForAbstractClass(EventValidatorInterface::class);
        $this->configurationProviderMock = $this->createMock(AdobeIOConfigurationProvider::class);
        $this->eventMetadataFactoryMock = $this->createMock(AdobeIoEventMetadataFactory::class);
        $this->metadataClientMock = $this->createMock(EventMetadataClient::class);
        $this->eventListMock = $this->createMock(EventList::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->eventSubscriber = new EventSubscriber(
            $this->configWriterMock,
            $this->deploymentConfigMock,
            $this->subscribeValidatorMock,
            $this->unsubscribeValidatorMock,
            $this->configurationProviderMock,
            $this->eventMetadataFactoryMock,
            $this->metadataClientMock,
            $this->eventListMock,
            $this->loggerMock
        );
    }

    public function testSubscribeValidationFailed(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('validation error');

        $this->subscribeValidatorMock->expects(self::once())
            ->method('validate')
            ->with($this->eventMock)
            ->willThrowException(new ValidatorException(__('validation error')));
        $this->configWriterMock->expects(self::never())
            ->method('saveConfig');

        $this->eventSubscriber->subscribe($this->eventMock);
    }

    public function testSubscribeExceptionOnProviderRetrieve(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('validation error');

        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willThrowException(new \Exception('validation error'));
        $this->metadataClientMock->expects(self::never())
            ->method('createEventMetadata');
        $this->subscribeValidatorMock->expects(self::once())
            ->method('validate')
            ->with($this->eventMock);
        $this->eventListMock->expects(self::never())
            ->method('reset');
        $this->configWriterMock->expects(self::never())
            ->method('saveConfig');

        $this->eventSubscriber->subscribe($this->eventMock);
    }

    public function testSubscribe(): void
    {
        $eventName = 'observer.event.test';
        $providerMock = $this->createMock(EventProviderInterface::class);
        $eventMetadataMock = $this->createMock(EventMetadataInterface::class);

        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn($eventName);
        $this->eventMock->expects(self::once())
            ->method('getFields')
            ->willReturn(['field_one', 'field_two']);
        $this->subscribeValidatorMock->expects(self::once())
            ->method('validate')
            ->with($this->eventMock);
        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willReturn($providerMock);
        $this->eventMetadataFactoryMock->expects(self::once())
            ->method('generate')
            ->with(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $eventName)
            ->willReturn($eventMetadataMock);
        $this->metadataClientMock->expects(self::once())
            ->method('createEventMetadata')
            ->with($providerMock, $eventMetadataMock);
        $this->deploymentConfigMock->expects(self::once())
            ->method('get')
            ->with(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME, [])
            ->willReturn([
                'observer.event.test' => [
                    'fields' => [
                        'field_1',
                        'field_2',
                        'field_3',
                    ],
                    'enabled' => 0
                ],
                'observer.event.test_two' => [
                    'fields' => [
                        'field_1',
                    ],
                    'enabled' => 1
                ]
            ]);
        $this->configWriterMock->expects(self::once())
            ->method('saveConfig')
            ->with(
                [
                    ConfigFilePool::APP_CONFIG => [
                        EventSubscriberInterface::IO_EVENTS_CONFIG_NAME => [
                            'observer.event.test' => [
                                'fields' => [
                                    'field_one',
                                    'field_two',
                                ],
                                'enabled' => 1
                            ],
                            'observer.event.test_two' => [
                                'fields' => [
                                    'field_1',
                                ],
                                'enabled' => 1
                            ]
                        ]
                    ],
                ],
                true
            );
        $this->eventListMock->expects(self::once())
            ->method('reset');
        $this->loggerMock->expects(self::once())
            ->method('info');

        $this->eventSubscriber->subscribe($this->eventMock);
    }

    public function testUnsubscribe():void
    {
        $eventName = 'observer.event.test';
        $providerMock = $this->createMock(EventProviderInterface::class);
        $eventMetadataMock = $this->createMock(EventMetadataInterface::class);

        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn($eventName);
        $this->unsubscribeValidatorMock->expects(self::once())
            ->method('validate')
            ->with($this->eventMock);
        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willReturn($providerMock);
        $this->eventMetadataFactoryMock->expects(self::once())
            ->method('generate')
            ->with(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $eventName)
            ->willReturn($eventMetadataMock);
        $this->metadataClientMock->expects(self::once())
            ->method('deleteEventMetadata')
            ->with($providerMock, $eventMetadataMock);
        $this->configWriterMock->expects(self::once())
            ->method('saveConfig')
            ->with(
                [
                    ConfigFilePool::APP_CONFIG => [
                        EventSubscriberInterface::IO_EVENTS_CONFIG_NAME => [
                            $eventName => [
                                'enabled' => 0
                            ],
                        ]
                    ],
                ]
            );
        $this->eventListMock->expects(self::once())
            ->method('reset');
        $this->loggerMock->expects(self::once())
            ->method('info');

        $this->eventSubscriber->unsubscribe($this->eventMock);
    }

    public function testUnsubscribeExceptionOnProviderRetrieve(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('validation error');

        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willThrowException(new \Exception('validation error'));
        $this->metadataClientMock->expects(self::never())
            ->method('createEventMetadata');
        $this->configWriterMock->expects(self::never())
            ->method('saveConfig');
        $this->eventListMock->expects(self::never())
            ->method('reset');

        $this->eventSubscriber->unsubscribe($this->eventMock);
    }
}
