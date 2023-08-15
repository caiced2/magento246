<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaInvitation\Test\Integration;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\Invitation\Model\Invitation;
use Magento\Invitation\Model\ResourceModel\Invitation as InvitationResource;
use Magento\ReCaptchaValidation\Model\Validator;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Invitation\Model\InvitationFactory;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for customer creation via invitation email link.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class InviteCreateAccountFormTest extends AbstractController
{
    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var InvitationFactory
     */
    private $invitationFactory;

    /**
     * @var InvitationResource
     */
    private $invitationResource;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * @var ValidationResult|MockObject
     */
    private $captchaValidationResultMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->formKey = $this->_objectManager->get(FormKey::class);
        $this->invitationResource = $this->_objectManager->get(InvitationResource::class);
        $this->invitationFactory = $this->_objectManager->get(InvitationFactory::class);
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $this->urlEncoder = $this->_objectManager->get(EncoderInterface::class);
        $this->url = $this->_objectManager->get(UrlInterface::class);
        $this->captchaValidationResultMock = $this->createMock(ValidationResult::class);
        $captchaValidatorMock = $this->createMock(Validator::class);
        $captchaValidatorMock->expects($this->any())
            ->method('isValid')
            ->willReturn($this->captchaValidationResultMock);
        $this->_objectManager->addSharedInstance($captchaValidatorMock, Validator::class);
    }

    /**
     * Checks the content when ReCaptcha is disabled
     *
     * @magentoDataFixture Magento/Customer/_files/customer_confirmation_config_disable.php
     * @magentoDataFixture Magento/Invitation/_files/unaccepted_invitation.php
     */
    public function testGetRequestIfReCaptchaIsDisabled(): void
    {
        $this->checkSuccessfulGetResponse();
    }

    /**
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/customer_invite_create invisible
     * @magentoDataFixture Magento/Customer/_files/customer_confirmation_config_disable.php
     * @magentoDataFixture Magento/Invitation/_files/unaccepted_invitation.php
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/customer_invite_create invisible
     */
    public function testGetRequestIfReCaptchaKeysAreNotConfigured(): void
    {
        $this->checkSuccessfulGetResponse();
    }

    /**
     * @magentoConfigFixture default_store customer/captcha/enable 0
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/customer_invite_create invisible
     * @magentoDataFixture Magento/Customer/_files/customer_confirmation_config_disable.php
     * @magentoDataFixture Magento/Invitation/_files/unaccepted_invitation.php
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/customer_invite_create invisible
     */
    public function testGetRequestIfReCaptchaIsEnabled(): void
    {
        $this->checkSuccessfulGetResponse(true);
    }

    /**
     * Checks the content when ReCaptcha is disabled
     *
     * @magentoDataFixture Magento/Customer/_files/customer_confirmation_config_disable.php
     * @magentoDataFixture Magento/Invitation/_files/unaccepted_invitation.php
     */
    public function testPostRequestIfReCaptchaIsDisabled(): void
    {
        $this->checkSuccessfulPostResponse();
    }

    /**
     * Checks if ReCaptcha is enabled but keys are not configured
     *
     * @magentoDataFixture Magento/Customer/_files/customer_confirmation_config_disable.php
     * @magentoDataFixture Magento/Invitation/_files/unaccepted_invitation.php
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/customer_invite_create invisible
     *
     * It's  needed for proper work of "ifconfig" in layout during tests running
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/customer_invite_create invisible
     */
    public function testPostRequestIfReCaptchaKeysAreNotConfigured(): void
    {
        $this->checkSuccessfulPostResponse();
    }

    /**
     * @magentoConfigFixture default_store customer/captcha/enable 0
     * @magentoDataFixture Magento/Customer/_files/customer_confirmation_config_disable.php
     * @magentoDataFixture Magento/Invitation/_files/unaccepted_invitation.php
     * @magentoConfigFixture current_store magento_invitation/general/registration_use_inviter_group 0
     * @magentoConfigFixture current_store customer/create_account/default_group 2
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/public_key test_public_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_invisible/private_key test_private_key
     * @magentoConfigFixture base_website recaptcha_frontend/type_for/customer_invite_create invisible
     * @magentoConfigFixture default_store recaptcha_frontend/type_for/customer_invite_create invisible
     */
    public function testPostRequestIfReCaptchaParameterIsMissed(): void
    {
        $this->checkFailedPostResponse();
    }

    /**
     * @param bool $shouldContainReCaptcha
     * @return void
     */
    private function checkSuccessfulGetResponse($shouldContainReCaptcha = false): void
    {
        $createUrl = sprintf(
            'invitation/customer_account/create/invitation/%s',
            $this->urlEncoder->encode($this->getInvitation()->getInvitationCode())
        );
        $this->dispatch($createUrl);
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);

        $shouldContainReCaptcha
            ? $this->assertStringContainsString('field-recaptcha', $content)
            : $this->assertStringNotContainsString('field-recaptcha', $content);

        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * @param array $postValues
     * @return void
     */
    private function checkSuccessfulPostResponse(array $postValues = []): void
    {
        $this->makePostRequest($postValues);
        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('customer/account')));
        $customer = $this->customerRepository->get('dummy@dummy.com');
        self::assertNotNull($customer->getId());
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * @param array $postValues
     * @return void
     */
    private function checkFailedPostResponse(array $postValues = []): void
    {
        $this->makePostRequest($postValues);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('customer/account/')));
        $this->assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
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

    /**
     * @param array $postValues
     * @return void
     */
    private function makePostRequest(array $postValues = []): void
    {
        $this->getRequest()
            ->setMethod(Http::METHOD_POST)
            ->setPostValue(
                array_merge_recursive(
                    [
                        'firstname' => 'first_name',
                        'lastname' => 'last_name',
                        'email' => 'dummy@dummy.com',
                        'password' => 'Password1',
                        'password_confirmation' => 'Password1',
                        'form_key' => $this->formKey->getFormKey()
                    ],
                    $postValues
                )
            );

        $createUrl = sprintf(
            'customer/account/createPost/invitation/%s',
            $this->urlEncoder->encode($this->getInvitation()->getInvitationCode())
        );
        $this->dispatch($createUrl);
    }
}
