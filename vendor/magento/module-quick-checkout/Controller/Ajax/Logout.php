<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Controller\Ajax;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Magento and Bolt account logout
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Logout implements HttpGetActionInterface, CsrfAwareActionInterface
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param CustomerSession $customerSession
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        CustomerSession $customerSession,
        JsonFactory $resultJsonFactory
    ) {
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Logout bolt and registered users
     *
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $response = [
            'success' => true,
            'message' => __('Logout successful.')
        ];

        $this->customerSession->unsBoltCustomerToken();
        $this->customerSession->unsCanUseBoltSso();

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request) :? InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request) :? bool
    {
        return true;
    }
}
