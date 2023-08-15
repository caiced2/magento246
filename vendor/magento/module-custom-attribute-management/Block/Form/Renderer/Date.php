<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomAttributeManagement\Block\Form\Renderer;

/**
 * EAV Entity Attribute Form Renderer Block for Date
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Date extends \Magento\CustomAttributeManagement\Block\Form\Renderer\AbstractRenderer
{
    /**
     * Constants for borders of date-type customer attributes
     */
    public const MIN_DATE_RANGE_KEY = 'date_range_min';

    public const MAX_DATE_RANGE_KEY = 'date_range_max';

    /**
     * Array of date parts html fragments keyed by date part code
     *
     * @var array
     */
    protected $_dateInputs = [];

    /**
     * Array of minimal and maximal date range values
     *
     * @var array|null
     */
    protected $_dateRange = null;

    /**
     * Date element instance
     *
     * @var \Magento\Framework\View\Element\Html\Date
     */
    protected $dateElement;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\View\Element\Html\Date $dateElement
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\View\Element\Html\Date $dateElement,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dateElement = $dateElement;
        $this->_isScopePrivate = true;
    }

    /**
     * Return field HTML
     *
     * @return string
     */
    public function getFieldHtml()
    {
        $this->dateElement->setData(
            [
                'name' => $this->getFieldName(),
                'id' => $this->getHtmlId(),
                'class' => $this->getHtmlClass(),
                'value' => $this->_localeDate->formatDateTime(
                    $this->getValue() ?? 'now',
                    \IntlDateFormatter::SHORT,
                    \IntlDateFormatter::NONE,
                    null,
                    'UTC',
                    $this->getDateFormat()
                ),
                'date_format' => $this->getDateFormat(),
                'image' => $this->getViewFileUrl('Magento_Theme::calendar.png'),
                'change_month' => 'true',
                'change_year' => 'true',
                'show_on' => 'both'
            ]
        );

        return $this->dateElement->getHtml();
    }

    /**
     * Returns format which will be applied for date field in javascript
     *
     * @return string
     */
    public function getDateFormat()
    {
        $dateFormat = $this->_localeDate->getDateFormatWithLongYear();
        /** Escape RTL characters which are present in some locales and corrupt formatting */
        $escapedDateFormat = preg_replace('/[^MmDdYy\/\.\-]/', '', $dateFormat);

        return $escapedDateFormat;
    }

    /**
     * Add date input html
     *
     * @param string $code
     * @param string $html
     * @codeCoverageIgnore
     * @return void
     */
    public function setDateInput($code, $html)
    {
        $this->_dateInputs[$code] = $html;
    }

    /**
     * Sort date inputs by dateformat order of current locale
     *
     * @param bool $stripNonInputChars
     *
     * @return string
     */
    public function getSortedDateInputs($stripNonInputChars = true)
    {
        $mapping = [];
        if ($stripNonInputChars) {
            $mapping['/[^medy]/i'] = '\\1';
        }
        $mapping['/m{1,5}/i'] = '%1$s';
        $mapping['/e{1,5}/i'] = '%2$s';
        $mapping['/d{1,5}/i'] = '%2$s';
        $mapping['/y{1,5}/i'] = '%3$s';

        $dateFormat = $this->_localeDate->getDateFormat();
        $dateFormat = preg_replace(array_keys($mapping), array_values($mapping), $dateFormat);

        return sprintf($dateFormat, $this->_dateInputs['m'], $this->_dateInputs['d'], $this->_dateInputs['y']);
    }

    /**
     * Return value as unix time stamp or false
     *
     * @return int|false
     */
    public function getTimestamp()
    {
        $timestamp = $this->getData('timestamp');
        $attributeCodeThis = $this->getData('attribute_code');
        $attributeCodeObj = $this->getAttributeObject()->getAttributeCode();
        if (null === $timestamp || $attributeCodeThis != $attributeCodeObj) {
            $value = $this->getValue();
            if ($value) {
                if (is_numeric($value)) {
                    $timestamp = $value;
                } else {
                    $timestamp = strtotime($value);
                }
            } else {
                $timestamp = false;
            }
            $this->setData('timestamp', $timestamp);
            $this->setData('attribute_code', $attributeCodeObj);
        }
        return $timestamp;
    }

    /**
     * Return Date part by index
     *
     * @param string $index allowed index (Y,m,d)
     * @return string
     */
    protected function _getDateValue($index)
    {
        if ($this->getTimestamp()) {
            return date($index, $this->getTimestamp());
        }
        return '';
    }

    /**
     * Return day value from date
     *
     * @return string
     */
    public function getDay()
    {
        return $this->_getDateValue('d');
    }

    /**
     * Return month value from date
     *
     * @return string
     */
    public function getMonth()
    {
        return $this->_getDateValue('m');
    }

    /**
     * Return year value from date
     *
     * @return string
     */
    public function getYear()
    {
        return $this->_getDateValue('Y');
    }

    /**
     * Return minimal date range value
     *
     * @return string
     */
    public function getMinDateRange()
    {
        return $this->_getBorderDateRange(self::MIN_DATE_RANGE_KEY);
    }

    /**
     * Return maximal date range value
     *
     * @return string
     */
    public function getMaxDateRange()
    {
        return $this->_getBorderDateRange(self::MAX_DATE_RANGE_KEY);
    }

    /**
     * Return minimal or maximal date range value
     *
     * @param string $borderName
     * @return int|null
     */
    protected function _getBorderDateRange($borderName = self::MIN_DATE_RANGE_KEY)
    {
        $dateRange = $this->_getDateRange();
        if (isset($dateRange[$borderName])) {
            //milliseconds for JS
            return $dateRange[$borderName] * 1000;
        } else {
            return null;
        }
    }

    /**
     * Return array of date range border values
     *
     * @return array
     */
    protected function _getDateRange()
    {
        if (null === $this->_dateRange) {
            $this->_dateRange = [];
            $rules = $this->getAttributeObject()->getValidateRules();
            if (isset($rules[self::MIN_DATE_RANGE_KEY])) {
                $this->_dateRange[self::MIN_DATE_RANGE_KEY] = $rules[self::MIN_DATE_RANGE_KEY];
            }
            if (isset($rules[self::MAX_DATE_RANGE_KEY])) {
                $this->_dateRange[self::MAX_DATE_RANGE_KEY] = $rules[self::MAX_DATE_RANGE_KEY];
            }
        }
        return $this->_dateRange;
    }
}
