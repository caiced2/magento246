<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Ip-address grid filter
 */
namespace Magento\Logging\Block\Adminhtml\Grid\Filter;

use Magento\Backend\Block\Widget\Grid\Column\Filter\Text;

class Ip extends Text
{

    /**
     * Collection condition filter getter
     *
     * @return array|int|false
     */
    public function getCondition()
    {
        $value = $this->getValue() ?? '';
        if (preg_match('/^(\d+\.){3}\d+$/', $value)) {
            return ip2long($value);
        }
        $likeExpression = $this->_resourceHelper->addLikeEscape($value, ['position' => 'any']);
        if (preg_match("/[a-z:]/i", $value)) {
            return ['like' => $likeExpression];
        }

        return ['ntoa' => $likeExpression];
    }
}
