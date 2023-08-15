<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class PreventSettingQuoteObserver implements ObserverInterface
{
    /**
     * Whether set quote to be persistent in workflow
     *
     * @var QuotePersistentPreventFlag
     */
    protected $quotePersistent;

    /**
     * @param QuotePersistentPreventFlag $quotePersistent
     */
    public function __construct(
        QuotePersistentPreventFlag $quotePersistent
    ) {
        $this->quotePersistent = $quotePersistent;
    }

    /**
     * Prevent setting persistent data into quote
     *
     * @param EventObserver $observer
     * @return void
     *
     * @see QuotePersistentPreventFlag::setValue
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(EventObserver $observer)
    {
        $this->quotePersistent->setValue(false);
    }
}
