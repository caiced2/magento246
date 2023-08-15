<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Url;

use Magento\Framework\App\Area;
use Magento\Framework\Url\ModifierInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Modifier of base URLs on staging preview.
 */
class BaseUrlModifier implements \Magento\Framework\Url\ModifierInterface
{
    /**
     * @var string
     */
    private $mode = ModifierInterface::MODE_BASE;

    /**
     * @var string
     */
    private $isPreview;

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * @param \Magento\Framework\App\State $state ,
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Staging\Model\VersionManager $versionManager
     */
    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Staging\Model\VersionManager $versionManager
    ) {
        $this->state = $state;
        $this->request = $request;
        $this->versionManager = $versionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($url, $mode = ModifierInterface::MODE_ENTIRE)
    {
        if ($mode == $this->mode) {
            try {
                $areaCode = $this->state->getAreaCode();
            } catch (LocalizedException $e) {
                return $url;
            }

            if ($areaCode == Area::AREA_FRONTEND) {
                if ($this->isPreview()) {
                    $host = parse_url($url, PHP_URL_HOST);
                    $port = parse_url($url, PHP_URL_PORT);
                    if ($port) {
                        $host .= ':' . $port;
                    }
                    $url = str_replace(
                        $host,
                        $this->request->getServer('HTTP_HOST'),
                        $url
                    );
                }
            }
        }

        return $url;
    }

    /**
     * @return bool
     */
    private function isPreview()
    {
        if ($this->isPreview === null) {
            $this->isPreview = $this->versionManager->isPreviewVersion();
        }

        return $this->isPreview;
    }
}
