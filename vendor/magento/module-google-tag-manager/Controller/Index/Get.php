<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\GoogleTagManager\Helper\CookieData;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;

/**
 * Get product list from quick order / advanced add
 */
class Get implements ActionInterface, HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var TagManagerConfig
     */
    private $tagManagerConfig;

    /**
     * @var CookieData
     */
    private $cookieData;

    /**
     *
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param CookieData $cookieData
     * @param TagManagerConfig $tagManagerConfig
     * @param SessionManagerInterface $sessionManager
     * @param CookieManagerInterface $cookieManager
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        RequestInterface        $request,
        ResultFactory           $resultFactory,
        CookieData              $cookieData,
        TagManagerConfig        $tagManagerConfig,
        SessionManagerInterface $sessionManager,
        CookieManagerInterface  $cookieManager,
        JsonFactory             $jsonFactory
    ) {
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->cookieData = $cookieData;
        $this->tagManagerConfig = $tagManagerConfig;
        $this->sessionManager = $sessionManager;
        $this->cookieManager = $cookieManager;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Get add to cart advanced list
     *
     * @return ResponseInterface|Json|Raw|ResultInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        if ($this->isRequestAllowed() &&
            $this->cookieManager->getCookie(CookieData::GOOGLE_ANALYTICS_COOKIE_ADVANCED_NAME)
        ) {
            $addToCartProductList = $this->sessionManager->getAddToCartAdvanced();

            if ($addToCartProductList) {
                $this->sessionManager->unsAddToCartAdvanced();
                $resultJson = $this->jsonFactory->create();
                return $resultJson->setData($addToCartProductList);
            }
        }
        $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $resultRaw->setHttpResponseCode(404);
        return $resultRaw;
    }

    /**
     * Check if request is allowed.
     *
     * @return bool
     */
    private function isRequestAllowed(): bool
    {
        if (!$this->cookieData->isGoogleAnalyticsAvailable()
            && !$this->cookieData->isTagManagerAvailable()
            && !$this->tagManagerConfig->isGoogleAnalyticsAvailable()
            && !$this->tagManagerConfig->isTagManagerAvailable()
        ) {
            return false;
        }
        return $this->request->isAjax() && $this->request->isPost();
    }
}
