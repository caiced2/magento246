<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reminder\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Reminder rules observer model
 */
class AddUseAutoGenerationNoticeObserver implements ObserverInterface
{
    /**
     * Adds notice to "Use Auto Generation" checkbox
     *
     * @param EventObserver $observer
     *
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $form = $observer->getForm();
        $checkbox = $form->getElement('use_auto_generation');
        $checkbox->setNote(
            $checkbox->getNote() . '<br />' . __(
                '<b>Important</b>: If you select "Use Auto Generation", '
                . 'this rule will no longer be used in any automated email reminder rules for abandoned carts'
            )
        );
    }
}
