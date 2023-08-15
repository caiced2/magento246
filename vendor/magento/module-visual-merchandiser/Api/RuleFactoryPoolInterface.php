<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface Rule factory pool interface
 *
 * @api
 */
interface RuleFactoryPoolInterface
{

    const DEFAULT_RULE_BOOL = 'Boolean';
    const DEFAULT_RULE_LITERAL = 'Literal';

    /**
     * @param $ruleId
     * @return string
     * @throws LocalizedException
     */
    public function getRule($ruleId);

    /**
     * @param $ruleId
     * @return bool
     */
    public function hasRule($ruleId);
}
