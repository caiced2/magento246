<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Block\Events;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeCommerceEventsClient\Event\EventInfo;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Returns event payload
 *
 * @api
 * @since 1.0.0
 */
class EventPayload extends Template
{
    /**
     * @var EventInfo
     */
    private EventInfo $eventInfo;

    /**
     * @var EventFactory
     */
    private EventFactory $eventFactory;

    /**
     * @param Context $context
     * @param EventInfo $eventInfo
     * @param EventFactory $eventFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        EventInfo $eventInfo,
        EventFactory $eventFactory,
        array $data = []
    ) {
        $this->eventInfo = $eventInfo;
        $this->eventFactory = $eventFactory;
        parent::__construct($context, $data);
    }

    /**
     * Returns event payload
     *
     * @return string
     * @throws ValidatorException
     * @throws EventException
     */
    public function getEventPayload(): string
    {
        if (!trim($this->getRequest()->getParam('event', ''))) {
            return '';
        }

        $event = $this->eventFactory->create([
            Event::EVENT_NAME => trim($this->getRequest()->getParam('event'))
        ]);

        return $this->eventInfo->getJsonExample($event, 2);
    }

    /**
     * Get event from the request
     *
     * @return string|null
     */
    public function getEvent(): ?string
    {
        return $this->getRequest()->getParam('event');
    }
}
