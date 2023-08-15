<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Invitation\Model;

use Magento\Framework\App\RequestInterface;

/**
 * Class for providing invitation by request.
 */
class InvitationProvider
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var InvitationFactory
     */
    protected $invitationFactory;

    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @param \Magento\Framework\Registry $registry
     * @param InvitationFactory $invitationFactory
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        InvitationFactory $invitationFactory,
        \Magento\Framework\Url\DecoderInterface $urlDecoder
    ) {
        $this->registry = $registry;
        $this->invitationFactory = $invitationFactory;
        $this->urlDecoder = $urlDecoder;
    }

    /**
     * Retrieve invitation
     *
     * @param RequestInterface $request
     * @return \Magento\Invitation\Model\Invitation
     */
    public function get(RequestInterface $request)
    {
        if (!$this->registry->registry('current_invitation')) {
            /** @var Invitation $invitation */
            $invitation = $this->invitationFactory->create();
            $invitation->loadByInvitationCode(
                $this->urlDecoder->decode(
                    $request->getParam('invitation', false)
                )
            )->makeSureCanBeAccepted();
            $this->registry->register('current_invitation', $invitation);
        }
        return $this->registry->registry('current_invitation');
    }
}
