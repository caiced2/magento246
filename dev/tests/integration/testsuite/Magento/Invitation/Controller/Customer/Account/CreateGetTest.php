<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Controller\Customer\Account;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Invitation\Model\Invitation;
use Magento\Invitation\Model\InvitationFactory;
use Magento\Invitation\Model\ResourceModel\Invitation as InvitationResource;
use Magento\TestFramework\TestCase\AbstractController;

class CreateGetTest extends AbstractController
{
    /**
     * @var InvitationFactory
     */
    private $invitationFactory;

    /**
     * @var InvitationResource
     */
    private $invitationResource;

    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    public function setUp(): void
    {
        parent::setUp();
        $this->invitationResource = $this->_objectManager->get(InvitationResource::class);
        $this->invitationFactory = $this->_objectManager->get(InvitationFactory::class);
        $this->urlEncoder = $this->_objectManager->get(EncoderInterface::class);
    }

    /**
     * Checks the content
     *
     * @magentoDataFixture Magento/Customer/_files/customer_confirmation_config_disable.php
     * @magentoDataFixture Magento/Invitation/_files/unaccepted_invitation.php
     */
    public function testGetRequest(): void
    {
        $this->checkSuccessfulGetResponse();
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    private function checkSuccessfulGetResponse(): void
    {
        $createUrl = sprintf(
            'invitation/customer_account/create/invitation/%s',
            $this->urlEncoder->encode($this->getInvitation()->getInvitationCode())
        );
        $this->dispatch($createUrl);
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);

        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * Returns invitation.
     *
     * @return Invitation
     */
    private function getInvitation(): Invitation
    {
        $invitation =  $this->invitationFactory->create();
        $this->invitationResource->load($invitation, 'unaccepted_invitation@example.com', 'email');

        return $invitation;
    }
}
