<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Enterprise\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Enterprise general observer
 *
 */
class SetHideSurveyQuestionObserver implements ObserverInterface
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;

    /**
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(\Magento\Backend\Model\Auth\Session $authSession)
    {
        $this->_authSession = $authSession;
    }

    /**
     * Set hide survey question to session
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_authSession->setHideSurveyQuestion(true);

        return $this;
    }
}
