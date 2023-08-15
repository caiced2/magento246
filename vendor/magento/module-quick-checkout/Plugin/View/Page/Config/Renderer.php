<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\QuickCheckout\Plugin\View\Page\Config;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\View\Asset\File;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Config\Renderer as MagentoRenderer;
use Magento\QuickCheckout\Model\Config as ConfigProvider;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Asset\GroupedCollection;

class Renderer
{
    /**
     * @var Repository
     */
    private $assetRepo;

    /**
     * @var GroupedCollection
     */
    private $pageAssets;

    /**
     * @var ConfigProvider
     */
    private $checkoutConfig;

    /**
     * @param ConfigProvider    $checkoutConfig
     * @param Repository        $assetRepo
     * @param GroupedCollection $pageAssets
     */
    public function __construct(
        ConfigProvider $checkoutConfig,
        Repository $assetRepo,
        GroupedCollection $pageAssets
    ) {
        $this->checkoutConfig = $checkoutConfig;
        $this->assetRepo = $assetRepo;
        $this->pageAssets = $pageAssets;
    }

    /**
     * Disable Quick Checkout js mixins if module is disabled
     *
     * @param MagentoRenderer $subject
     * @param array $resultGroups
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeRenderAssets(MagentoRenderer $subject, $resultGroups = [])
    {
        if (!$this->checkoutConfig->isEnabled()) {
            $file = 'Magento_QuickCheckout::js/disabled.js';
            $asset = $this->assetRepo->createAsset($file);
            $this->pageAssets->insert($file, $asset, 'requirejs/require.js');
        }

        return [$resultGroups];
    }
}
