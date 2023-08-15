<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Plugin\Framework\App;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Staging\Model\Preview\RequestSigner;

/**
 * Plugin for front controller interface.
 */
class FrontController
{
    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * @var RequestSigner|null
     */
    private $requestSigner;

    /**
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\Staging\Model\VersionManager $versionManager
     * @param RequestSigner|null $requestSigner
     */
    public function __construct(
        \Magento\Backend\Model\Auth $auth,
        \Magento\Staging\Model\VersionManager $versionManager,
        RequestSigner $requestSigner = null
    ) {
        $this->auth = $auth;
        $this->versionManager = $versionManager;
        $this->requestSigner = $requestSigner ?: ObjectManager::getInstance()->get(RequestSigner::class);
    }

    /**
     * Check if user logged in and allowed for staging before preview dispatch
     *
     * @param \Magento\Framework\App\FrontControllerInterface $subject
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(
        \Magento\Framework\App\FrontControllerInterface $subject,
        \Magento\Framework\App\RequestInterface $request
    ) {
        if ($this->versionManager->isPreviewVersion()
            && (!$request instanceof Request || !$this->requestSigner->validateUrl($request->getRequestString()))
        ) {
            $this->forwardRequest($request);
        }
    }

    /**
     * Forwards request to the 404 page.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return void
     */
    private function forwardRequest(
        \Magento\Framework\App\RequestInterface $request
    ) {
        $request->initForward();
        $request->setActionName('noroute');
    }
}
