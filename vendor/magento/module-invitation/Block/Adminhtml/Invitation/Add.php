<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Block\Adminhtml\Invitation;

/**
 * Invitation view block
 *
 */
class Add extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var string
     */
    protected $_objectId = 'invitation_id';

    /**
     * @var string
     */
    protected $_blockGroup = 'Magento_Invitation';

    /**
     * @var string
     */
    protected $_controller = 'adminhtml_invitation';

    /**
     * @var string
     */
    protected $_mode = 'add';

    /**
     * Get header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('New Invitations');
    }
}
