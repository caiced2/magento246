<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Block\Adminhtml\Widget\Chooser;

/**
 * Date range widget chooser
 * Currently works without localized format
 */
class Daterange extends \Magento\Backend\Block\AbstractBlock
{
    /**
     * HTML ID of the element that will obtain the joined chosen values
     *
     * @var string
     */
    protected $_targetElementId = '';

    /**
     * From/To values to be rendered
     *
     * @var array
     */
    protected $_rangeValues = ['from' => '', 'to' => ''];

    /**
     * Range string delimiter for from/to dates
     *
     * @var string
     */
    protected $_rangeDelimiter = '...';

    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $_formFactory;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Math\Random $mathRandom,
        array $data = []
    ) {
        $this->_formFactory = $formFactory;
        $this->mathRandom = $mathRandom;
        parent::__construct($context, $data);
    }

    /**
     * Render the chooser HTML
     * Target element should be set.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (empty($this->_targetElementId)) {
            return '';
        }

        $idSuffix = $this->mathRandom->getUniqueHash();
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $dateFields = ['from' => __('From'), 'to' => __('To')];
        foreach ($dateFields as $key => $label) {
            $form->addField(
                "{$key}_{$idSuffix}",
                'date',
                [
                    // hardcoded because hardcoded values delimiter
                    'format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                    'label' => $label,
                    // won't work through Event.observe()
                    'onchange' => "dateTimeChoose_{$idSuffix}()",
                    'value' => $this->_rangeValues[$key]
                ]
            );
        }
        return $form->toHtml() .
            "<script type=\"text/javascript\">require(['prototype'], function(){"
            . "\n            dateTimeChoose_{$idSuffix} = function() {"
            . "\n                \$('{$this->_targetElementId}').value = "
            . "\$('from_{$idSuffix}').value + '{$this->_rangeDelimiter}' + \$('to_{$idSuffix}').value;"
            . "\n            };\n            });</script>";
    }

    /**
     * Target element ID setter
     *
     * @param string $value
     * @return $this
     */
    public function setTargetElementId($value)
    {
        $this->_targetElementId = trim($value);
        return $this;
    }

    /**
     * Range values setter
     *
     * @param string $from
     * @param string $to
     * @return $this
     */
    public function setRangeValues($from, $to)
    {
        $this->_rangeValues = ['from' => $from, 'to' => $to];
        return $this;
    }

    /**
     * Range values setter, string implementation.
     * Automatically attempts to split the string by delimiter
     *
     * @param string $delimitedString
     * @return $this
     */
    public function setRangeValue($delimitedString)
    {
        $split = explode($this->_rangeDelimiter, $delimitedString, 2);
        $from = $split[0];
        $to = '';
        if (isset($split[1])) {
            $to = $split[1];
        }
        return $this->setRangeValues($from, $to);
    }

    /**
     * Range delimiter setter
     *
     * @param string $value
     * @return $this
     */
    public function setRangeDelimiter($value)
    {
        $this->_rangeDelimiter = (string)$value;
        return $this;
    }
}
