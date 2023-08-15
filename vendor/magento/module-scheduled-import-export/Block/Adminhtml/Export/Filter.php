<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Block\Adminhtml\Export;

/**
 * Export filter block
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Filter extends \Magento\ImportExport\Block\Adminhtml\Export\Filter
{
    /**
     * Get grid url
     *
     * @param array $params
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAbsoluteGridUrl($params = [])
    {
        return $this->getGridUrl();
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        if ($this->hasOperation()) {
            return $this->getUrl(
                'adminhtml/scheduled_operation/getFilter',
                ['entity' => $this->getOperation()->getEntity()]
            );
        } else {
            return $this->getUrl('adminhtml/scheduled_operation/getFilter');
        }
    }
}
