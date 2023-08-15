<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Block\Adminhtml\Update\Entity;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Staging\Block\Adminhtml\Update\IdProvider as UpdateIdProvider;
use Magento\Staging\Model\Preview\UrlBuilder;

/**
 * Entity for preview button
 */
class PreviewButton implements ButtonProviderInterface
{
    /**
     * @var EntityProviderInterface
     */
    protected $entityProvider;

    /**
     * @var UpdateIdProvider
     */
    protected $updateIdProvider;

    /**
     * @var UrlBuilder
     */
    protected $previewUrlBuilder;

    /**
     * @var StoreIdProviderInterface
     */
    private $entityStoreIdProvider;

    /**
     * PreviewButton constructor.
     *
     * @param EntityProviderInterface $entityProvider
     * @param UpdateIdProvider $updateIdProvider
     * @param UrlBuilder $previewUrlBuilder
     * @param StoreIdProviderInterface $entityStoreIdProvider
     */
    public function __construct(
        EntityProviderInterface $entityProvider,
        UpdateIdProvider $updateIdProvider,
        UrlBuilder $previewUrlBuilder,
        ?StoreIdProviderInterface $entityStoreIdProvider = null
    ) {
        $this->entityProvider = $entityProvider;
        $this->updateIdProvider = $updateIdProvider;
        $this->previewUrlBuilder = $previewUrlBuilder;
        $this->entityStoreIdProvider = $entityStoreIdProvider ?? ObjectManager::getInstance()
                ->get(StoreIdProviderInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->updateIdProvider->getUpdateId()) {
            $url = $this->previewUrlBuilder->getPreviewUrl(
                $this->updateIdProvider->getUpdateId(),
                $this->entityProvider->getUrl($this->updateIdProvider->getUpdateId()),
                $this->entityStoreIdProvider->getStoreId()
            );

            $data = [
                'label' => __('Preview'),
                'url' =>  $url,
                'on_click' => "window.open('" . $url . "','_blank')",
                'sort_order' => 20,
            ];
        }

        return $data;
    }
}
