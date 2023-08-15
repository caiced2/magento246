<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Related banners edit tab for promo catalog rule edit page
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Banner\Block\Adminhtml\Promo\Catalogrule\Edit\Tab;

/**
 * @codeCoverageIgnore
 */
class Banners extends \Magento\Backend\Block\Text\ListText implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Related Dynamic Blocks');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Related Dynamic Blocks');
    }

    /**
     * Returns status flag about this tab can be showen or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return null
     */
    public function getTabClass()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getTabUrl()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }
}
