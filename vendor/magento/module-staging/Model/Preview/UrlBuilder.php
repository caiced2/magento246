<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Preview;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Url;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Generates preview URLs
 */
class UrlBuilder
{
    /**#@+
     * Get parameters names.
     */
    const PARAM_PREVIEW_URL = 'preview_url';
    const PARAM_PREVIEW_STORE = 'preview_store';
    const PARAM_PREVIEW_VERSION = 'preview_version';
    /**#@-*/

    /**
     * Preview url
     */
    const URL_PATH_PREVIEW = 'staging/update/preview';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Url
     */
    protected $frontendUrl;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param UrlInterface $urlBuilder
     * @param Url $frontendUrl
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Url $frontendUrl,
        ?StoreManagerInterface $storeManager = null
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->frontendUrl = $frontendUrl;
        $this->storeManager = $storeManager ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * Return staging preview URL for version and url
     *
     * @param int $versionId
     * @param string|null $url
     * @param string|null $store
     * @return string
     */
    public function getPreviewUrl($versionId, $url = null, $store = null)
    {
        $params = [
            self::PARAM_PREVIEW_VERSION => $versionId,
            self::PARAM_PREVIEW_URL => $url !== null ? $url : $this->frontendUrl->getUrl()
        ];
        if ($store) {
            $params[self::PARAM_PREVIEW_STORE] = $this->storeManager->getStore($store)->getCode();
        }
        return $this->urlBuilder->getUrl(
            self::URL_PATH_PREVIEW,
            [
                '_query' => $params,
            ]
        );
    }
}
