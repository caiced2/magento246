<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Scheduled operation grid container
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\ScheduledImportExport\Block\Adminhtml\Scheduled;

/**
 * @api
 * @since 100.0.2
 */
class Operation extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Initialize block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_addButtonLabel = __('Add Scheduled Export');
        parent::_construct();

        $this->buttonList->add(
            'add_new_import',
            [
                'label' => __('Add Scheduled Import'),
                'onclick' => "setLocation('" . $this->getUrl('adminhtml/*/new', ['type' => 'import']) . "')",
                'class' => 'add primary add-scheduled-import'
            ]
        );

        $this->_blockGroup = 'Magento_ScheduledImportExport';
        $this->_controller = 'adminhtml_scheduled_operation';
        $this->_headerText = __('Scheduled Import/Export');
    }

    /**
     * Get create url
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('adminhtml/*/new', ['type' => 'export']);
    }
}
