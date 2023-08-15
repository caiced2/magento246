<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context as ActionContext;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Escaper;
use Magento\Framework\Validator\ValidatorChain;
use Magento\Framework\Validator\EmailAddress;
use Magento\Invitation\Model\Config as InvitationConfig;
use Magento\Invitation\Model\Invitation;
use Magento\Invitation\Model\InvitationFactory;

/**
 * Controller for sending customer invitations.
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class Send extends \Magento\Invitation\Controller\Index
{
    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param ActionContext $context
     * @param CustomerSession $session
     * @param InvitationConfig $config
     * @param InvitationFactory $invitationFactory
     * @param FormKeyValidator|null $formKeyValidator
     * @param Escaper|null $escaper
     */
    public function __construct(
        ActionContext $context,
        CustomerSession $session,
        InvitationConfig $config,
        InvitationFactory $invitationFactory,
        FormKeyValidator $formKeyValidator = null,
        Escaper $escaper = null
    ) {
        parent::__construct($context, $session, $config, $invitationFactory);

        $this->formKeyValidator = $formKeyValidator ?: $this->_objectManager->get(FormKeyValidator::class);
        $this->escaper = $escaper ?: $this->_objectManager->get(Escaper::class);
    }

    /**
     * Send invitations from frontend.
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $validFormKey = $this->formKeyValidator->validate($this->getRequest());
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            if (!$validFormKey) {
                $this->messageManager->addError(__('Invalid Form Key. Please refresh the page.'));
            } else {
                $customer = $this->_session->getCustomer();
                $message = isset($data['message']) ? $data['message'] : '';
                if (!$this->_config->isInvitationMessageAllowed()) {
                    $message = '';
                }
                $invPerSend = $this->_config->getMaxInvitationsPerSend();
                $attempts = 0;
                $customerExists = 0;
                foreach ($data['email'] as $email) {
                    $attempts++;
                    if (!ValidatorChain::is($email, EmailAddress::class)) {
                        continue;
                    }
                    if ($attempts > $invPerSend) {
                        continue;
                    }
                    try {
                        /** @var Invitation $invitation */
                        $invitation = $this->invitationFactory->create();
                        $invitation->setData(
                            ['email' => $email, 'customer' => $customer, 'message' => $message]
                        )->save();
                        if ($invitation->sendInvitationEmail()) {
                            $this->messageManager->addSuccess(
                                __('You sent the invitation for %1.', $this->escaper->escapeHtml($email))
                            );
                        } else {
                            // not \Magento\Framework\Exception\LocalizedException intentionally
                            // phpcs:ignore Magento2.Exceptions.DirectThrow
                            throw new \Exception('');
                        }
                    } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                        $customerExists++;
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        $this->messageManager->addError($e->getMessage());
                        // phpcs:disable Magento2.Exceptions.ThrowCatch
                    } catch (\Exception $e) {
                        $this->messageManager->addError(
                            __(
                                'Something went wrong while sending an email to %1.',
                                $this->escaper->escapeHtml($email)
                            )
                        );
                    }
                }
                if ($customerExists) {
                    $this->messageManager->addNotice(
                        __('We did not send %1 invitation(s) addressed to current customers.', $customerExists)
                    );
                }
            }
            $this->_redirect('*/*/');

            return;
        }

        $this->_view->loadLayout();
        $this->_view->loadLayoutUpdates();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Send Invitations'));
        $this->_view->renderLayout();
    }
}
