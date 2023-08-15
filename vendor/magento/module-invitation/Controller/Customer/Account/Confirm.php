<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Customer\Account;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Invitation\Controller\Customer\AccountInterface;

/**
 * Class for confirm user.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Confirm extends \Magento\Customer\Controller\Account\Confirm implements
    AccountInterface,
    HttpGetActionInterface,
    HttpPostActionInterface
{
    /**
     * Load customer by id (try/catch in case if it throws exceptions)
     *
     * @param int $customerId
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Exception
     */
    protected function _loadCustomerById($customerId)
    {
        try {
            /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
            return $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            throw new \Exception(__('The wrong customer account is specified.'));
        }
    }

    /**
     * Check if customer is active already.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param mixed $key
     * @return bool|null
     * @throws \Exception
     */
    protected function _checkCustomerActive($customer, $key)
    {
        if ($customer->getConfirmation()) {
            if ($customer->getConfirmation() !== $key) {
                throw new \Exception(__('Please enter a correct confirmation key.'));
            }
            $this->customerAccountManagement->activate($customer->getEmail(), $key);

            // log in and send greeting email, then die happy
            $this->session->setCustomerAsLoggedIn($customer);
            $this->_redirect('customer/account/');
            return true;
        }
    }

    /**
     * Confirm customer account by id and confirmation key
     *
     * @return void
     */
    public function execute()
    {
        if ($this->session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        try {
            $customerId = $this->getRequest()->getParam('id', false);
            $key = $this->getRequest()->getParam('key', false);
            if (empty($customerId) || empty($key)) {
                throw new \Exception(__('Bad request.'));
            }

            $customer = $this->_loadCustomerById($customerId);
            if (true === $this->_checkCustomerActive($customer, $key)) {
                return;
            }
            // die happy
            $this->_redirect('customer/account/');
            return;
        } catch (\Exception $e) {
            // die unhappy
            $this->messageManager->addError($e->getMessage());
            $this->_redirect(
                'magento_invitation/customer_account/create',
                ['_current' => true, '_secure' => true]
            );
            return;
        }
    }
}
