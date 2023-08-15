<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Swat\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Encryption\UrlCoder;
use Magento\Swat\Model\Jwt;

/**
 * Controller class for launching SWAT
 */
class Launch extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Swat::swat';
    const CONFIG_RSA_PAIR_PATH = 'swat/rsa_keypair';
    const CONFIG_SWAT_URL = 'swat/url';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var UrlCoder */
    private $urlCoder;

    /** @var Jwt */
    private $jwt;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlCoder $urlCoder
     * @param Jwt $jwt
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        UrlCoder $urlCoder,
        Jwt $jwt
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->urlCoder = $urlCoder;
        $this->jwt = $jwt;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $swatUrl = $this->scopeConfig->getValue(self::CONFIG_SWAT_URL);
        return $resultRedirect->setUrl($swatUrl . '?jwt=' . $this->urlCoder->encode($this->jwt->getTokenString()));
    }
}
