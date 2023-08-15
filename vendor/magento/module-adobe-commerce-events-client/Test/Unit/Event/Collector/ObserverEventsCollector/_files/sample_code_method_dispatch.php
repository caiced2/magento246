<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\Module;

use Magento\Framework\Event\ManagerInterface;

/**
 * Custom class with different
 */
class CustomClass
{
    /** @var ManagerInterface  */
    private ManagerInterface $eventManager;

    /**
     * @param ManagerInterface $eventManager
     */
    public function __construct(ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    public function dispatchSingleQuotesSingleLine()
    {
        $this->eventManager->dispatch('event_single_quotes', []);
    }

    public function dispatchDoubleQuotesSingleLine()
    {
        $this->eventManager->dispatch("event_double_quotes", []);
    }

    public function dispatchSingleQuotesMultipleLine()
    {
        $this->eventManager->dispatch(
            'event_single_quotes_multiple_lines',
            []
        );
    }

    public function dispatchDoubleQuotesMultipleLine()
    {
        $this->eventManager->dispatch(
            "event_double_quotes_multiple_lines",
            []
        );
    }

    public function dispatchSingleDynamicEvent()
    {
        $value = 'test';
        $this->eventManager->dispatch('event_single_quotes_dynamic_' . $value, []);
    }

    public function dispatchSingleDynamicEventMultipleLine()
    {
        $value = 'test';
        $this->eventManager->dispatch(
            'event_single_quotes_dynamic_multiple_lines_' . $value,
            []
        );
    }
}
