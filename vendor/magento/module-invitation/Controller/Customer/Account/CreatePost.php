<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Customer\Account;

use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Helper\Address;
use Magento\Framework\UrlFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Registration;
use Magento\Framework\Escaper;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Invitation\Model\InvitationProvider;
use Magento\Invitation\Model\Invitation;
use Magento\Framework\Exception\LocalizedException as FrameworkException;
use Magento\Store\Model\ScopeInterface;

/**
 * Post create customer action
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatePost extends \Magento\Customer\Controller\Account\CreatePost
{
    /**
     * @var InvitationProvider
     */
    protected $invitationProvider;

    /**
     * @var Registration
     */
    protected $registration;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $accountManagement
     * @param Address $addressHelper
     * @param UrlFactory $urlFactory
     * @param FormFactory $formFactory
     * @param SubscriberFactory $subscriberFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param CustomerUrl $customerUrl
     * @param Registration $registration
     * @param Escaper $escaper
     * @param CustomerExtractor $customerExtractor
     * @param DataObjectHelper $dataObjectHelper
     * @param AccountRedirect $accountRedirect
     * @param InvitationProvider $invitationProvider
     * @param CustomerRepository $customerRepository
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManagement,
        Address $addressHelper,
        UrlFactory $urlFactory,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        RegionInterfaceFactory $regionDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerUrl $customerUrl,
        Registration $registration,
        Escaper $escaper,
        CustomerExtractor $customerExtractor,
        DataObjectHelper $dataObjectHelper,
        AccountRedirect $accountRedirect,
        InvitationProvider $invitationProvider,
        CustomerRepository $customerRepository
    ) {
        $this->invitationProvider = $invitationProvider;
        parent::__construct(
            $context,
            $customerSession,
            $scopeConfig,
            $storeManager,
            $accountManagement,
            $addressHelper,
            $urlFactory,
            $formFactory,
            $subscriberFactory,
            $regionDataFactory,
            $addressDataFactory,
            $customerDataFactory,
            $customerUrl,
            $registration,
            $escaper,
            $customerExtractor,
            $dataObjectHelper,
            $accountRedirect,
            $customerRepository
        );
    }

    /**
     * Create customer account action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $this->invitationProvider->get($this->getRequest());

            parent::execute();

            $resultRedirect->setPath('customer/account/');
            return $resultRedirect;
        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
            $this->messageManager->addError($e->getMessage())->setCustomerFormData($this->getRequest()->getPostValue());
        } catch (\Magento\Framework\Exception\InputException $e) {
            $this->messageManager->addError($e->getMessage())->setCustomerFormData($this->getRequest()->getPostValue());
        } catch (FrameworkException $e) {
            if ($this->registration->isAllowed()) {
                $this->messageManager->addError(__('Your invitation is not valid. Please create an account.'));
                $resultRedirect->setPath('customer/account/create');
                return $resultRedirect;
            } else {
                $this->messageManager->addError(
                    __(
                        'Your invitation is not valid. Please contact us at %1.',
                        $this->scopeConfig->getValue(
                            'trans_email/ident_support/email',
                            ScopeInterface::SCOPE_STORE
                        )
                    )
                );
                $resultRedirect->setPath('customer/account/login');
                return $resultRedirect;
            }
        } catch (\Exception $e) {
            $this->session->setCustomerFormData($this->getRequest()->getPostValue());
            $this->messageManager->addException($e, __('We can\'t save this customer.'));
        }

        $resultRedirect->setPath('magento_invitation/customer_account/create', ['_current' => true, '_secure' => true]);
        return $resultRedirect;
    }
}
