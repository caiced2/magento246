<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Block\Adminhtml\Archive\Grid\Filter;

use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Filter\NormalizedToLocalized;
use Magento\Framework\Stdlib\DateTime;

/**
 * Custom date column filter for logging archive grid
 */
class Date extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Date
{
    /**
     * Convert date from localized to internal format
     *
     * @param string $date
     * @return string
     */
    protected function _convertDate($date)
    {
        $filterInput = new LocalizedToNormalized(
            [
                'date_format' => $this->_localeDate->getDateFormat(),
            ]
        );
        $filterInternal = new NormalizedToLocalized(
            ['date_format' => DateTime::DATE_INTERNAL_FORMAT]
        );
        $date = $filterInput->filter($date);

        return $filterInternal->filter($date);
    }
}
