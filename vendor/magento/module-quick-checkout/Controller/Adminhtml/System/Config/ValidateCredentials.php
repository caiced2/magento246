<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Controller\Adminhtml\System\Config;

use Exception;
use Magento\Config\Console\Command\ConfigShow\ValueProcessor;
use Magento\Framework\Controller\ResultInterface;
use Magento\QuickCheckout\Model\Adminhtml\System\Config\CredentialsValidator;
use Magento\QuickCheckout\Model\Adminhtml\System\Config\AccountCredentials;
use Magento\QuickCheckout\Model\Config;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\AbstractAction;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Validate API credentials
 */
class ValidateCredentials extends AbstractAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Config::config';

    private const API_KEY_FIELD = 'api_key';

    private const SIGNING_SECRET_FIELD = 'signing_secret';

    private const PUB_KEY_FIELD = 'publishable_key';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var CredentialsValidator
     */
    private CredentialsValidator $credentialsValidator;

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @param Context $context
     * @param Config $config
     * @param CredentialsValidator $credentialsValidator
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        Config $config,
        CredentialsValidator $credentialsValidator,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->credentialsValidator = $credentialsValidator;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Test credentials against Bolt API
     *
     * @return ResultInterface
     */
    public function execute() : ResultInterface
    {
        $response = ['success' => true];

        try {
            $this->credentialsValidator->validate($this->prepareCredentials());
        } catch (Exception $exception) {
            $response['success'] = false;
        } finally {
            return $this->resultJsonFactory->create()->setData($response);
        }
    }

    /**
     * Prepares the credentials to be tested
     *
     * @return AccountCredentials
     */
    private function prepareCredentials() : AccountCredentials
    {
        $params = $this->getRequest()->getParams();

        $apiKey = $params[self::API_KEY_FIELD] ?? '';
        $signingSecret = $params[self::SIGNING_SECRET_FIELD] ?? '';
        $publishableKey = $params[self::PUB_KEY_FIELD] ?? '';

        if ($this->isPlaceholder($apiKey)) {
            $apiKey = $this->config->getApiKey();
        }

        if ($this->isPlaceholder($signingSecret)) {
            $signingSecret = $this->config->getSigningSecret();
        }

        if ($this->isPlaceholder($publishableKey)) {
            $publishableKey = $this->config->getPublishableKey();
        }

        return new AccountCredentials($apiKey, $signingSecret, $publishableKey);
    }

    /**
     * Returns true if the specified key is equals to the safe placeholder
     *
     * @param string $key
     * @return boolean
     */
    private function isPlaceholder(string $key) : bool
    {
        return $key === ValueProcessor::SAFE_PLACEHOLDER;
    }
}
