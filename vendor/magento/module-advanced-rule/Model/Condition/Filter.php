<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedRule\Model\Condition;

use Magento\AdvancedRule\Model\Condition\FilterInterface;

/**
 * Class Filter
 *
 * @codeCoverageIgnore
 */
class Filter extends \Magento\Framework\Model\AbstractModel implements FilterInterface
{
    const KEY_FILTER_TEXT = 'filter_text';
    const KEY_WEIGHT = 'weight';
    const KEY_FILTER_TEXT_GENERATOR_CLASS = 'filter_text_generator_class';
    const KEY_FILTER_TEXT_GENERATOR_ARGUMENTS = 'filter_text_generator_arguments';

    /**
     * @return string
     */
    public function getFilterText()
    {
        return $this->getData(self::KEY_FILTER_TEXT);
    }

    /**
     * @param string $filterText
     * @return $this
     */
    public function setFilterText($filterText)
    {
        return $this->setData(self::KEY_FILTER_TEXT, $filterText);
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->getData(self::KEY_WEIGHT);
    }

    /**
     * @param float $weight
     * @return $this
     */
    public function setWeight($weight)
    {
        return $this->setData(self::KEY_WEIGHT, $weight);
    }

    /**
     * Return name of the FilterTextGenerator class
     *
     * @return string
     */
    public function getFilterTextGeneratorClass()
    {
        return $this->getData(self::KEY_FILTER_TEXT_GENERATOR_CLASS);
    }

    /**
     * @param string $filterTextGeneratorClass
     * @return $this
     */
    public function setFilterTextGeneratorClass($filterTextGeneratorClass)
    {
        return $this->setData(self::KEY_FILTER_TEXT_GENERATOR_CLASS, $filterTextGeneratorClass);
    }

    /**
     * @return string
     */
    public function getFilterTextGeneratorArguments()
    {
        return $this->getData(self::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS);
    }

    /**
     * @param string $arguments
     * @return $this
     */
    public function setFilterTextGeneratorArguments($arguments)
    {
        return $this->setData(self::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS, $arguments);
    }
}
