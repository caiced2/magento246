<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Model;

use Magento\Customer\Model\Registration;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Session\Generic;
use Magento\Framework\UrlFactory;

/**
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Restrictor
{
    /**
     * @var ConfigInterface
     */
    protected $_config;

    /**
     * @var UrlFactory
     */
    protected $_urlFactory;

    /**
     * @var ActionFlag
     */
    protected $_actionFlag;

    /**
     * @var Registration
     */
    protected $registration;

    /**
     * @var Generic
     */
    protected $_session;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Url
     */
    protected $customerUrl;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @param ConfigInterface $config
     * @param Registration $registration
     * @param Generic $session
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlFactory $urlFactory
     * @param ActionFlag $actionFlag
     * @param Url $customerUrl
     * @param Session $customerSession
     */
    public function __construct(
        ConfigInterface $config,
        Registration $registration,
        Generic $session,
        ScopeConfigInterface $scopeConfig,
        UrlFactory $urlFactory,
        ActionFlag $actionFlag,
        Url $customerUrl,
        Session $customerSession
    ) {
        $this->customerUrl = $customerUrl;
        $this->_config = $config;
        $this->registration = $registration;
        $this->_customerSession = $customerSession;
        $this->_session = $session;
        $this->_scopeConfig = $scopeConfig;
        $this->_urlFactory = $urlFactory;
        $this->_actionFlag = $actionFlag;
    }

    /**
     * Restrict access to website
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param bool $isCustomerLoggedIn
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function restrict($request, $response, $isCustomerLoggedIn)
    {
        $actionFullName = strtolower($request->getFullActionName());
        /**
         * Skip restriction while action name isn't initialised
         */
        if (empty(trim($actionFullName, '_'))) {
            return;
        }

        switch ($this->_config->getMode()) {
            // show only landing page with 503 or 200 code
            case Mode::ALLOW_NONE:
                if ($actionFullName !== 'restriction_index_stub') {
                    $request->setModuleName('restriction')
                        ->setControllerName('index')
                        ->setActionName('stub')
                        ->setDispatched(false);
                    return;
                }
                $httpStatus = $this->_config->getHTTPStatusCode();
                if (Mode::HTTP_503 === $httpStatus) {
                    $response->setStatusHeader(503, '1.1', 'Service Unavailable');
                }
                break;

            case Mode::ALLOW_REGISTER:
                // break intentionally omitted

                //redirect to landing page/login
            case Mode::ALLOW_LOGIN:
                if (!$isCustomerLoggedIn && !$this->_customerSession->isLoggedIn()) {
                    // see whether redirect is required and where
                    $redirectUrl = false;
                    $allowedActionNames = $this->_config->getGenericActions();
                    if ($this->registration->isAllowed()) {
                        $allowedActionNames = array_merge($allowedActionNames, $this->_config->getRegisterActions());
                    }

                    array_walk(
                        $allowedActionNames,
                        function (&$item) {
                            $item = strtolower($item);
                        }
                    );

                    // to specified landing page
                    $restrictionRedirectCode = $this->_config->getHTTPRedirectCode();
                    if (Mode::HTTP_302_LANDING === $restrictionRedirectCode) {
                        $cmsPageViewAction = 'cms_page_view';
                        $allowedActionNames[] = $cmsPageViewAction;
                        $pageIdentifier = $this->_config->getLandingPageCode();
                        // Restrict access to CMS pages too
                        if (!in_array($actionFullName, $allowedActionNames)
                            || $actionFullName === $cmsPageViewAction
                            && $request->getAlias('rewrite_request_path') !== $pageIdentifier
                        ) {
                            $redirectUrl = $this->_urlFactory->create()->getUrl('', ['_direct' => $pageIdentifier]);
                        }
                    } elseif (!in_array($actionFullName, $allowedActionNames)) {
                        // to login form
                        $redirectUrl = $this->customerUrl->getLoginUrl();
                    }

                    if ($redirectUrl) {
                        $response->setRedirect($redirectUrl);
                        $this->_actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
                    }
                }
                break;
        }
    }
}
