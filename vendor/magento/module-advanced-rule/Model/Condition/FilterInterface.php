<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedRule\Model\Condition;

/**
 * Interface \Magento\AdvancedRule\Model\Condition\FilterInterface
 *
 * @api
 */
interface FilterInterface
{
    const FILTER_TEXT_TRUE = 'true';

    /**
     * @return string
     */
    public function getFilterText();

    /**
     * @param string $filterText
     * @return $this
     */
    public function setFilterText($filterText);

    /**
     * @return float
     */
    public function getWeight();

    /**
     * @param float $weight
     * @return $this
     */
    public function setWeight($weight);

    /**
     * Return name of the FilterTextGenerator class
     *
     * @return string
     */
    public function getFilterTextGeneratorClass();

    /**
     * @param string $filterTextGeneratorClass
     * @return $this
     */
    public function setFilterTextGeneratorClass($filterTextGeneratorClass);

    /**
     * @return string
     */
    public function getFilterTextGeneratorArguments();

    /**
     * @param string $arguments
     * @return $this
     */
    public function setFilterTextGeneratorArguments($arguments);
}
