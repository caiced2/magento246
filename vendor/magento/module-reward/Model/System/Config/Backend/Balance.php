<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Backend model for "Reward Points Balance"
 *
 */
namespace Magento\Reward\Model\System\Config\Backend;

use Magento\Framework\Exception\LocalizedException;

class Balance extends \Magento\Framework\App\Config\Value
{
    /**
     * Check if max_points_balance >= than min_points_balance
     * (max allowed to RP to gain is more than minimum to redeem)
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        parent::beforeSave();
        if (!$this->isValueChanged()) {
            return $this;
        }

        if ($this->getFieldsetDataValue('min_points_balance') < 0) {
            $message = __('The "Minimum Reward Points Balance" is invalid. '
                . 'The balance needs to be a positive number or left empty. Verify and try again.');
            throw new LocalizedException($message);
        }
        if ($this->getFieldsetDataValue('max_points_balance') < 0) {
            $message = __('The "Cap Reward Points Balance" is invalid. '
                . 'The balance needs to be a positive number or left empty. Verify and try again.');
            throw new LocalizedException($message);
        }
        if ($this->getFieldsetDataValue(
            'max_points_balance'
        ) && $this->getFieldsetDataValue(
            'min_points_balance'
        ) > $this->getFieldsetDataValue(
            'max_points_balance'
        )
        ) {
            $message = __('The "Minimum Reward Points Balance" is invalid. '
                . 'The balance needs to be less than or equal to the "Cap Reward Points Balance".');
            throw new LocalizedException($message);
        }
        return $this;
    }
}
