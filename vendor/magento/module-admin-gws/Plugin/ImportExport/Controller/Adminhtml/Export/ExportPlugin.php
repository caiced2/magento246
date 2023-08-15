<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\ImportExport\Controller\Adminhtml\Export;

use Magento\AdminGws\Model\Role;
use Magento\ImportExport\Controller\Adminhtml\Export\Export;

/**
 * Plugin for Export
 */
class ExportPlugin
{
    /**
     * @var Role
     */
    private $role;

    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    /**
     * Adds allowed websites for restricted admin role to the parameters
     *
     * Plugin checks if export is filtered by website id,
     * and if it not the case, it adds all allowed websites for restricted admin role
     *
     * @param Export $subject
     * @param array $params
     * @return array
     */
    public function afterGetRequestParameters(Export $subject, array $params): array
    {
        if ($this->role->getIsAll()) {
            return $params;
        }

        $availableWebsiteIds = $this->role->getWebsiteIds();
        if (!isset($params['export_filter']['website_id'])) {
            $params['export_filter']['website_id'] = $availableWebsiteIds;
        } else {
            $params['export_filter']['website_id'] =
                in_array($params['export_filter']['website_id'], $availableWebsiteIds) ?
                $params['export_filter']['website_id'] : $availableWebsiteIds;
        }

        return $params;
    }
}
