<?php
/**
 * Plugin for \Magento\Customer\Model\ResourceModel\Visitor model
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Plugin\Catalog\Category;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\AuthorizationInterface;

class DataProvider
{
    const PERMISSIONS_GWS_PATH = 'Magento_CatalogPermissions::catalog_magento_catalogpermissions';

    /**
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param ConfigInterface $permissionsConfig
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        AuthorizationInterface $authorization
    ) {
        $this->permissionsConfig = $permissionsConfig;
        $this->authorization = $authorization;
    }

    /**
     * @param \Magento\Catalog\Model\Category\DataProvider $subject
     * @param array $result
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPrepareMeta(\Magento\Catalog\Model\Category\DataProvider $subject, $result)
    {
        $permissionsEnabled = $this->permissionsConfig->isEnabled();
        if (!$permissionsEnabled || !$this->authorization->isAllowed(self::PERMISSIONS_GWS_PATH)) {
            $result['category_permissions']['arguments']['data']['disabled'] = true;
            $result['category_permissions']['arguments']['data']['config']['componentType'] =
                \Magento\Ui\Component\Form\Fieldset::NAME;
        }
        return $result;
    }
}
