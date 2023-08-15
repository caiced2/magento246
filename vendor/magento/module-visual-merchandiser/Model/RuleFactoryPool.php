<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\VisualMerchandiser\Api\RuleFactoryPoolInterface;

class RuleFactoryPool implements RuleFactoryPoolInterface
{

    private $rules;

    public function __construct(
        $rules = []
    ) {
        if (!array_key_exists(static::DEFAULT_RULE_LITERAL, $rules)
            || !array_key_exists(static::DEFAULT_RULE_BOOL, $rules)) {
            throw new \InvalidArgumentException('Default rules missing in Pool');
        }

        $this->rules = $rules;
    }

    /**
     * @param $ruleId
     * @return string
     * @throws LocalizedException
     */
    public function getRule($ruleId)
    {
        if ($this->hasRule($ruleId)) {
            return $this->rules[$ruleId];
        } else {
            throw new LocalizedException(__("Rule %1 does not exists", $ruleId));
        }
    }

    /**
     * @param $ruleId
     * @return bool
     */
    public function hasRule($ruleId)
    {
        if (isset($this->rules[$ruleId])) {
            return true;
        }

        return false;
    }
}
