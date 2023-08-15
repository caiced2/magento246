<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Preview;

use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Framework\App\Area;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Route parameters preprocessor for staging preview.
 */
class RouteParamsPreprocessor implements \Magento\Framework\Url\RouteParamsPreprocessorInterface
{
    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var RequestSigner|null
     */
    private $requestSigner;

    /**
     * @param HttpRequest $request
     * @param VersionManager $versionManager
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestSigner|null $requestSigner
     */
    public function __construct(
        HttpRequest $request,
        VersionManager $versionManager,
        ScopeConfigInterface $scopeConfig,
        RequestSigner $requestSigner = null
    ) {
        $this->request = $request;
        $this->versionManager = $versionManager;
        $this->scopeConfig = $scopeConfig;
        $this->requestSigner = $requestSigner ?: ObjectManager::getInstance()->get(RequestSigner::class);
    }

    /**
     * Adds 'version' and 'store' to query parameters.
     *
     * @param string $areaCode
     * @param string|null $routePath
     * @param array|null $routeParams
     * @return array|null
     */
    public function execute($areaCode, $routePath, $routeParams)
    {
        if ($areaCode == Area::AREA_FRONTEND) {
            if ($this->versionManager->isPreviewVersion() && $routePath !== null) {
                $routeParams['_query'][VersionManager::PARAM_NAME]
                    = $this->versionManager->getRequestedTimestamp();

                if (!$this->isStoreCodeUsedInUrl()) {
                    $routeParams['_query'][\Magento\Store\Model\StoreManagerInterface::PARAM_NAME]
                        = $this->request->getParam(\Magento\Store\Model\StoreManagerInterface::PARAM_NAME);
                }

                if (!empty($this->versionManager->getRequestedTimestamp())) {
                    $params = $this->requestSigner->generateSignatureParams(
                        $this->versionManager->getRequestedTimestamp()
                    );
                    $routeParams['_query'] = array_merge($routeParams['_query'], $params->getData());
                }
            }
        }

        return $routeParams;
    }

    /**
     * If the store code is used in the url.
     *
     * @return bool
     */
    private function isStoreCodeUsedInUrl()
    {
        return $this->scopeConfig->getValue(
            Store::XML_PATH_STORE_IN_URL,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }
}
