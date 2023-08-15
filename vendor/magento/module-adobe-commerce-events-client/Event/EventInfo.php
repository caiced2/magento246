<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Event\Collector\AggregatedEventList;
use Magento\AdobeCommerceEventsClient\Event\EventInfo\EventInfoReflection;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use Magento\AdobeCommerceEventsClient\Util\ClassToArrayConverter;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * Returns event info
 */
class EventInfo
{
    public const NESTED_LEVEL = ClassToArrayConverter::NESTED_LEVEL;

    /**
     * @var EventValidatorInterface
     */
    private EventValidatorInterface $eventCodeValidator;

    /**
     * @var AggregatedEventList
     */
    private AggregatedEventList $aggregatedEventList;

    /**
     * @var EventInfoReflection
     */
    private EventInfoReflection $infoReflection;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param EventValidatorInterface $eventCodeValidator
     * @param EventInfoReflection $infoReflection
     * @param AggregatedEventList $aggregatedEventList
     * @param LoggerInterface $logger
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        EventValidatorInterface $eventCodeValidator,
        EventInfoReflection $infoReflection,
        AggregatedEventList $aggregatedEventList,
        LoggerInterface $logger
    ) {
        $this->eventCodeValidator = $eventCodeValidator;
        $this->infoReflection = $infoReflection;
        $this->aggregatedEventList = $aggregatedEventList;
        $this->logger = $logger;
    }

    /**
     * Returns payload info for given event.
     *
     * @param Event $event
     * @param int $nestedLevel
     * @return array
     * @throws EventException if information can not be obtained
     * @throws ValidatorException if event name has wrong format
     */
    public function getInfo(Event $event, int $nestedLevel = self::NESTED_LEVEL): array
    {
        $this->eventCodeValidator->validate($event);

        try {
            if (str_starts_with($event->getName(), EventSubscriberInterface::EVENT_TYPE_OBSERVER)) {
                $payloadInfo = $this->getInfoForObserverEvent($event->getName(), $nestedLevel);
            } else {
                $payloadInfo = $this->infoReflection->getPayloadInfo($event, $nestedLevel);
            }

            if (isset($payloadInfo['data_model']) && is_array($payloadInfo['data_model'])) {
                return $payloadInfo['data_model'];
            }

            return $payloadInfo;
        } catch (ReflectionException $e) {
            $this->logger->error(sprintf(
                'Event %s is not defined: %s',
                $event->getName(),
                $e->getMessage()
            ));
            throw new EventException(__('Cannot get details for event %1', $event->getName()), $e);
        }
    }

    /**
     * Returns event info in json format.
     *
     * @param Event $event
     * @param int $nestedLevel
     * @return string
     * @throws EventException|ValidatorException
     */
    public function getJsonExample(Event $event, int $nestedLevel = self::NESTED_LEVEL): string
    {
        return str_replace('\\\\', '\\', json_encode($this->getInfo($event, $nestedLevel), JSON_PRETTY_PRINT));
    }

    /**
     * Returns info for observer event type
     *
     * @param string $eventName
     * @param int $nestedLevel
     * @return array
     * @throws EventException
     */
    public function getInfoForObserverEvent(string $eventName, int $nestedLevel = self::NESTED_LEVEL): array
    {
        $eventList = $this->aggregatedEventList->getList();
        if (!isset($eventList[$eventName])) {
            throw new EventException(__('Cannot get details about event %1', $eventName));
        }

        return $this->infoReflection->getInfoForObserverEvent(
            $eventList[$eventName]->getEventClassEmitter(),
            $nestedLevel
        );
    }
}
