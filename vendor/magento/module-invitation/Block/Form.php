<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Front end helper block to render form
 *
 */
namespace Magento\Invitation\Block;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\FormKey;
use Magento\Invitation\Helper\Data;

/**
 * Form for sending invitations.
 *
 * @api
 * @since 100.0.2
 */
class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * Invitation Config
     *
     * @var \Magento\Invitation\Model\Config
     */
    protected $_config;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Invitation\Model\Config $config
     * @param array $data
     * @param Data|null $invitationHelper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Invitation\Model\Config $config,
        array $data = [],
        Data $invitationHelper = null
    ) {
        $data['invitationHelper'] = $invitationHelper ?? ObjectManager::getInstance()->get(Data::class);
        parent::__construct($context, $data);
        $this->_config = $config;
    }

    /**
     * Returns maximal number of invitations to send in one try
     *
     * @return int
     */
    public function getMaxInvitationsPerSend()
    {
        return $this->_config->getMaxInvitationsPerSend();
    }

    /**
     * Returns whether custom invitation message allowed
     *
     * @return bool
     */
    public function isInvitationMessageAllowed()
    {
        return $this->_config->isInvitationMessageAllowed();
    }

    /**
     * Get form key value.
     *
     * @return string
     * @since 100.1.1
     */
    public function getFormKeyValue()
    {
        return $this->getFormKey()->getFormKey();
    }

    /**
     * Get form key object.
     *
     * @return FormKey
     *
     * @deprecated 100.1.1
     */
    private function getFormKey()
    {
        if ($this->formKey === null) {
            $this->formKey = \Magento\Framework\App\ObjectManager::getInstance()->get(FormKey::class);
        }
        return $this->formKey;
    }
}
