<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Invitation status option source
 *
 */
namespace Magento\Invitation\Model\Source\Invitation;

class Options implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Invitation Status
     *
     * @var \Magento\Invitation\Model\Source\Invitation\Status
     */
    protected $_invitationStatus;

    /**
     * @param \Magento\Invitation\Model\Source\Invitation\Status $invitationStatus
     */
    public function __construct(\Magento\Invitation\Model\Source\Invitation\Status $invitationStatus)
    {
        $this->_invitationStatus = $invitationStatus;
    }

    /**
     * Return list of invitation statuses as options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_invitationStatus->getOptions();
    }
}
