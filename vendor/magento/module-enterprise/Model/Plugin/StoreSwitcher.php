<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Enterprise\Model\Plugin;

use Magento\Backend\Block\Store\Switcher as StoreSwitcherBlock;

/**
 * Store switcher block plugin
 */
class StoreSwitcher
{
    /**
     * URL for store switcher hint
     */
    const HINT_URL = 'https://docs.magento.com/user-guide/configuration/scope.html';

    /**
     * Return url for store switcher hint
     *
     * @param StoreSwitcherBlock $subject
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetHintUrl(StoreSwitcherBlock $subject)
    {
        return self::HINT_URL;
    }
}
