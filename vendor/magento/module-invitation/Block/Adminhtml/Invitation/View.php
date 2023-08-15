<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Block\Adminhtml\Invitation;

/**
 * Invitation view block
 *
 * @api
 * @since 100.0.2
 */
class View extends \Magento\Backend\Block\Widget\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Set header text, add some buttons
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        $invitation = $this->getInvitation();
        $this->_headerText = __('View Invitation for %1 (ID: %2)', $invitation->getEmail(), $invitation->getId());
        $this->getLayout()->getBlock('page.title')->setPageTitle($this->_headerText);
        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'onclick' => "setLocation('{$this->getUrl('invitations/*/')}')",
                'class' => 'back'
            ],
            -1
        );
        if ($invitation->canBeCanceled()) {
            $massCancelUrl = $this->getUrl(
                'invitations/*/massCancel',
                ['_query' => ['invitations' => [$invitation->getId()]]]
            );
            $this->buttonList->add(
                'cancel',
                [
                    'label' => __('Discard Invitation'),
                    'onclick' => 'deleteConfirm(\'' . $this->escapeJs(
                        __('Are you sure you want to discard this invitation?')
                    ) . '\', \'' . $massCancelUrl . '\' )',
                    'class' => 'cancel primary'
                ],
                -1
            );
        }
        if ($invitation->canMessageBeUpdated()) {
            $this->buttonList->add(
                'save_message_button',
                [
                    'label' => __('Save Invitation'),
                    'data_attribute' => [
                        'mage-init' => ['button' => ['event' => 'save', 'target' => '#invitation-elements']],
                    ]
                ],
                -1
            );
        }
        if ($invitation->canBeSent()) {
            $massResendUrl = $this->getUrl(
                'invitations/*/massResend',
                ['_query' => http_build_query(['invitations' => [$invitation->getId()]])]
            );
            $this->buttonList->add(
                'resend',
                ['label' => __('Send Invitation'), 'onclick' => "setLocation('{$massResendUrl}')"],
                -1
            );
        }

        parent::_prepareLayout();
    }

    /**
     * Return Invitation for view
     *
     * @return \Magento\Invitation\Model\Invitation
     */
    public function getInvitation()
    {
        return $this->_coreRegistry->registry('current_invitation');
    }

    /**
     * Retrieve save message url
     *
     * @return string
     */
    public function getSaveMessageUrl()
    {
        return $this->getUrl('invitations/*/saveInvitation', ['id' => $this->getInvitation()->getId()]);
    }
}
