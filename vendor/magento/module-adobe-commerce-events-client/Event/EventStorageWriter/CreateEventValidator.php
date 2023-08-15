<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\EventStorageWriter;

use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleChecker;

/**
 * Checks if event can be created
 * - configuration is enabled
 * - verification passes for all rules
 */
class CreateEventValidator
{
    /**
     * @var Config
     */
    private Config $eventConfiguration;

    /**
     * @var RuleChecker
     */
    private RuleChecker $ruleChecker;

    /**
     * @param Config $eventConfiguration
     * @param RuleChecker $ruleChecker
     */
    public function __construct(
        Config $eventConfiguration,
        RuleChecker $ruleChecker
    ) {
        $this->eventConfiguration = $eventConfiguration;
        $this->ruleChecker = $ruleChecker;
    }

    /**
     * Checks if event can be created
     *
     * @param Event $event
     * @param array $eventData
     * @return bool
     * @throws OperatorException
     */
    public function validate(Event $event, array $eventData): bool
    {
        return $this->eventConfiguration->isEnabled() && $this->ruleChecker->verify($event, $eventData);
    }
}
