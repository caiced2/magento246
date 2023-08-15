<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Action\Creditmemo;

class VoidAction extends \Magento\Reward\Model\Action\Creditmemo
{
    /**
     * Return action message for history log
     *
     * @param array $args additional history data
     * @return \Magento\Framework\Phrase
     */
    public function getHistoryMessage($args = [])
    {
        $incrementId = isset($args['increment_id']) ? $args['increment_id'] : '';
        return __('Points voided at order #%1 refund.', $incrementId);
    }
}
