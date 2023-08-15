<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Ui\Component\Listing\Column\Page;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Staging\Ui\Component\Listing\Column\Entity\UrlProviderInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Entity for preview provider
 */
class PreviewProvider implements UrlProviderInterface
{
    /**
     * @var UrlInterface
     */
    protected $frontendUrlBuilder;

    /**
     * @param UrlInterface $frontendUrlBuilder
     */
    public function __construct(
        UrlInterface $frontendUrlBuilder
    ) {
        $this->frontendUrlBuilder = $frontendUrlBuilder;
    }

    /**
     * Get CMS page URL for data provider item
     *
     * @param array $item
     * @return string
     */
    public function getUrl(array $item)
    {
        $routePath = null;
        $routeParams = [
            '_direct' => $item['identifier'],
            '_nosid' => true,
        ];
        $store = array_key_exists('store_id', $item) ? $item['store_id'] : null;

        if (is_array($store)) {
            $store = reset($store);
        }

        if ($store) {
            $routePath = $item['identifier'];
            $routeParams = ['_scope' => $store];
        }

        return $this->frontendUrlBuilder->getUrl($routePath, $routeParams);
    }
}
