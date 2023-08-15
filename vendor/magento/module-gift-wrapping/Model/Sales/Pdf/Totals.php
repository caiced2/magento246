<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Model\Sales\Pdf;

use Magento\GiftWrapping\Helper\Data as GiftWrappingHelper;
use Magento\Sales\Model\Order\Pdf\Total\DefaultTotal;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory;

/**
 * Gift wrap totals PDF model
 */
class Totals extends DefaultTotal
{
    /**
     * Default font size for total items
     */
    private const DEFAULT_FONT_SIZE = 7;
    /**
     * @var GiftWrappingHelper
     */
    private $giftWrappingHelper;

    /**
     * Initializes dependencies.
     *
     * @param TaxHelper $taxHelper
     * @param Calculation $taxCalculation
     * @param CollectionFactory $ordersFactory
     * @param GiftWrappingHelper $giftWrappingHelper
     * @param array $data
     */
    public function __construct(
        TaxHelper $taxHelper,
        Calculation $taxCalculation,
        CollectionFactory $ordersFactory,
        GiftWrappingHelper $giftWrappingHelper,
        array $data = []
    ) {
        parent::__construct($taxHelper, $taxCalculation, $ordersFactory, $data);
        $this->giftWrappingHelper = $giftWrappingHelper;
    }

    /**
     * @inheritDoc
     */
    public function getTotalsForDisplay()
    {
        $source = $this->getSource();
        $totals = [];
        foreach ($this->giftWrappingHelper->getTotals($source) as $total) {
            $totals[] = $this->getTotalForDisplay($total);
        }

        return $totals;
    }

    /**
     * @inheritDoc
     */
    public function canDisplay()
    {
        return true;
    }

    /**
     * Get gift-wrap total unit to display in PDF
     *
     * @param array $total
     * @return array
     */
    private function getTotalForDisplay(array $total): array
    {
        return [
            'amount' => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($total['value']),
            'label' => __($total['label']) . ':',
            'font_size' => $this->getFontSize() ?? self::DEFAULT_FONT_SIZE,
        ];
    }
}
