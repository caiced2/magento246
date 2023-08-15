<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Ui\DataProvider\Product\Form\Modifier;

use Magento\PricePermissions\Observer\ObserverData;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Config\Model\Config\Source\Email\TemplateFactory as EmailTemplateFactory;
use Magento\Directory\Model\Currency;
use Magento\GiftCard\Model\Giftcard as GiftCardModel;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class GiftCard
 */
class GiftCard extends \Magento\GiftCard\Ui\DataProvider\Product\Form\Modifier\GiftCard
{
    /**
     * @var ObserverData
     */
    private $observerData;

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryHelper $directoryHelper
     * @param EmailTemplateFactory $emailTemplateFactory
     * @param StoreManagerInterface $storeManager
     * @param ObserverData $observerData
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        ScopeConfigInterface $scopeConfig,
        DirectoryHelper $directoryHelper,
        EmailTemplateFactory $emailTemplateFactory,
        StoreManagerInterface $storeManager,
        ObserverData $observerData
    ) {
        parent::__construct(
            $locator,
            $arrayManager,
            $scopeConfig,
            $directoryHelper,
            $emailTemplateFactory,
            $storeManager
        );

        $this->observerData = $observerData;
    }

    /**
     * {@inheritdoc}
     */
    protected function customizeOpenAmountFields()
    {
        if ($this->observerData->isCanReadProductPrice()) {
            parent::customizeOpenAmountFields();
        }

        return $this;
    }
}
