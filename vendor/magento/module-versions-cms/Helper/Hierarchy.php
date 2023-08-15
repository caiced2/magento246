<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Helper;

/**
 * CMS Hierarchy data helper
 */
class Hierarchy extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_HIERARCHY_ENABLED = 'cms/hierarchy/enabled';

    const XML_PATH_METADATA_ENABLED = 'cms/hierarchy/metadata_enabled';

    const METADATA_VISIBILITY_PARENT = '0';

    const METADATA_VISIBILITY_YES = '1';

    const METADATA_VISIBILITY_NO = '2';

    const SCOPE_PREFIX_STORE = 'store_';

    const SCOPE_PREFIX_WEBSITE = 'website_';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Check is Enabled Hierarchy Functionality
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_HIERARCHY_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check is Enabled Hierarchy Metadata
     *
     * @return bool
     */
    public function isMetadataEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_METADATA_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve metadata fields
     *
     * @return string[]
     */
    public function getMetadataFields()
    {
        return [
            'meta_first_last',
            'meta_next_previous',
            'meta_chapter',
            'meta_section',
            'meta_cs_enabled',
            'pager_visibility',
            'pager_frame',
            'pager_jump',
            'menu_visibility',
            'menu_layout',
            'menu_brief',
            'menu_excluded',
            'menu_levels_down',
            'menu_ordered',
            'menu_list_type',
            'top_menu_visibility',
            'top_menu_excluded'
        ];
    }

    /**
     * Copy meta data from source array to target
     *
     * @param array $source
     * @param array $target
     * @return array
     */
    public function copyMetaData($source, $target)
    {
        if (!is_array($source)) {
            return $target;
        }

        if (isset($source['pager_visibility'])) {
            $default = $this->getDefaultMetadataValues('pager_visibility', $source['pager_visibility']);
            if (is_array($default)) {
                $source = array_merge($source, $default);
            }
        }

        if (isset($source['menu_visibility'])) {
            $default = $this->getDefaultMetadataValues('menu_visibility', $source['menu_visibility']);
            if (is_array($default)) {
                $source = array_merge($source, $default);
            }
        }

        if ($this->isMetadataEnabled()) {
            $fields = $this->getMetadataFields();
            foreach ($fields as $element) {
                if (array_key_exists($element, $source)) {
                    $target[$element] = $source[$element];
                }
            }
        } else {
            $target = $this->forcedCopyMetaData($source, $target);
        }

        return $target;
    }

    /**
     * Copy metadata fields that don't depend on isMetadataEnabled
     *
     * @param array $source
     * @param array $target
     * @return array
     */
    protected function forcedCopyMetaData($source, $target)
    {
        if (!is_array($source)) {
            return $target;
        }
        $forced = [
            'pager_visibility',
            'pager_frame',
            'pager_jump',
            'menu_visibility',
            'menu_layout',
            'menu_brief',
            'menu_excluded',
            'menu_levels_down',
            'menu_ordered',
            'menu_list_type',
            'top_menu_visibility',
            'top_menu_excluded'
        ];
        foreach ($forced as $element) {
            if (array_key_exists($element, $source)) {
                $target[$element] = $source[$element];
            }
        }
        return $target;
    }

    /**
     * Return default values for metadata fields based on other field values
     * Ex: if 'pager_visibility' == '0' then set to zeros pagination params
     *
     * @param string $field Field name to search for
     * @param string $value Field value
     * @return array|null
     */
    protected function getDefaultMetadataValues($field, $value)
    {
        $paginationDefault = ['pager_frame' => '0', 'pager_jump' => '0'];

        $menuDefault = [
            'menu_levels_down' => '0',
            'menu_brief' => '0',
            'menu_layout' => '',
            'menu_ordered' => '0',
            'menu_list_type' => '',
        ];

        $default = [
            'pager_visibility' => [
                self::METADATA_VISIBILITY_PARENT => $paginationDefault,
                self::METADATA_VISIBILITY_NO => $paginationDefault,
            ],
            'menu_visibility' => ['0' => $menuDefault],
        ];

        return isset($default[$field][$value]) ? $default[$field][$value] : null;
    }

    /**
     * Get parent scope and scopeId
     *
     * @param string $scope
     * @param int $scopeId
     * @return array|null
     */
    public function getParentScope($scope, $scopeId)
    {
        if ($scope === \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_STORE) {
            return [
                \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_WEBSITE,
                $this->storeManager->getStore($scopeId)->getWebsiteId()
            ];
        } elseif ($scope === \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_WEBSITE) {
            return [
                \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_DEFAULT,
                \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_DEFAULT_ID
            ];
        }

        return null;
    }
}
