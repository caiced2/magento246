<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Downloadable\Model\Source\TypeUpload;
use Magento\Downloadable\Model\Source\Shareable;
use Magento\PricePermissions\Observer\ObserverData;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form;
use Magento\Downloadable\Ui\DataProvider\Product\Form\Modifier\Data\Links as DataLinks;

/**
 * Class for Product Form Modifier Links
 */
class Links extends \Magento\Downloadable\Ui\DataProvider\Product\Form\Modifier\Links
{
    /**
     * @var ObserverData
     */
    private $observerData;

    /**
     * @param LocatorInterface $locator
     * @param StoreManagerInterface $storeManager
     * @param ArrayManager $arrayManager
     * @param UrlInterface $urlBuilder
     * @param TypeUpload $typeUpload
     * @param Shareable $shareable
     * @param DataLinks $linksData
     * @param ObserverData $observerData
     */
    public function __construct(
        LocatorInterface $locator,
        StoreManagerInterface $storeManager,
        ArrayManager $arrayManager,
        UrlInterface $urlBuilder,
        TypeUpload $typeUpload,
        Shareable $shareable,
        DataLinks $linksData,
        ObserverData $observerData
    ) {
        parent::__construct($locator, $storeManager, $arrayManager, $urlBuilder, $typeUpload, $shareable, $linksData);

        $this->observerData = $observerData;
    }

    /**
     * @inheritdoc
     */
    protected function getPriceColumn()
    {
        $priceColumns = parent::getPriceColumn();

        if (!$this->observerData->isCanEditProductPrice()) {
            $priceField['arguments']['data']['config'] = [
                'imports' => [
                    'linksPurchasedSeparately' => '',
                    'useDefaultPrice' => '${$.parentName}.use_default_price:checked',
                    '__disableTmpl' => ['useDefaultPrice' => false],
                ],
            ];

            $priceColumns = $this->arrayManager->merge('children/link_price', $priceColumns, $priceField);
        }

        return $priceColumns;
    }
}
