<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistrySampleData\Setup;

use Magento\Framework\Setup;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;

class Installer implements Setup\SampleData\InstallerInterface
{
    /**
     * @var \Magento\GiftRegistrySampleData\Model\GiftRegistry $giftRegistry
     */
    protected $giftRegistry;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\GiftRegistrySampleData\Model\GiftRegistry $giftRegistry
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\GiftRegistrySampleData\Model\GiftRegistry $giftRegistry,
        StoreManagerInterface $storeManager = null
    ) {
        $this->giftRegistry = $giftRegistry;
        $this->storeManager = $storeManager ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(StoreManagerInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->storeManager->setCurrentStore(Store::DISTRO_STORE_ID);
        $this->giftRegistry->install(['Magento_GiftRegistrySampleData::fixtures/gift_registry.csv']);
    }
}
