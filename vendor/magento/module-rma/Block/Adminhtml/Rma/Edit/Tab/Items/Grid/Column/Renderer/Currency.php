<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\Currency as BackendCurrency;
use Magento\Directory\Model\Currency\DefaultLocator;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Grid column widget for rendering text grid cells for price calculation
 */
class Currency extends BackendCurrency
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param DefaultLocator $currencyLocator
     * @param CurrencyFactory $currencyFactory
     * @param CurrencyInterface $localeCurrency
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface          $storeManager,
        DefaultLocator                 $currencyLocator,
        CurrencyFactory                $currencyFactory,
        CurrencyInterface              $localeCurrency,
        ScopeConfigInterface           $scopeConfig,
        array                          $data = []
    ) {
        parent::__construct(
            $context,
            $storeManager,
            $currencyLocator,
            $currencyFactory,
            $localeCurrency,
            $data
        );
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Renders grid column for price and currency based on tax value config
     *
     * @param  DataObject $row
     * @return string
     * @throws NoSuchEntityException
     */
    public function render(DataObject $row): string
    {
        $taxInclude = (int) $this->scopeConfig->getValue(
            'tax/calculation/price_includes_tax',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        if ($taxInclude) {
            $row->setPrice($row->getOriginalPrice());
        }
        return (string) parent::render($row);
    }
}
