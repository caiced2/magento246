<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VersionsCms\Block\Adminhtml\Cms\Hierarchy\Edit;

/**
 * Cms Pages Tree Edit Form Block
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Currently selected store in store switcher
     *
     * @var null|int
     */
    protected $currentStore;

    /**
     * ID of the store where node can be previewed
     *
     * In most cases it is equal to currently selected store except situation when admin is in single store mode
     * @var null|int
     */
    protected $nodePreviewStoreId;

    /**
     * Cms hierarchy
     *
     * @var \Magento\VersionsCms\Helper\Hierarchy
     */
    protected $cmsHierarchy;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $sourceYesno;

    /**
     * @var \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Listmode
     */
    protected $menuListmode;

    /**
     * @var \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Listtype
     */
    protected $menuListtype;

    /**
     * @var \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Chapter
     */
    protected $menuChapter;

    /**
     * @var \Magento\VersionsCms\Model\Source\Hierarchy\Visibility
     */
    protected $hierarchyVisibility;

    /**
     * @var \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Layout
     */
    protected $menuLayout;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy
     * @param \Magento\Config\Model\Config\Source\Yesno $sourceYesno
     * @param \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Listmode $menuListmode
     * @param \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Listtype $menuListtype
     * @param \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Chapter $menuChapter
     * @param \Magento\VersionsCms\Model\Source\Hierarchy\Visibility $hierarchyVisibility
     * @param \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Layout $menuLayout
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy,
        \Magento\Config\Model\Config\Source\Yesno $sourceYesno,
        \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Listmode $menuListmode,
        \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Listtype $menuListtype,
        \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Chapter $menuChapter,
        \Magento\VersionsCms\Model\Source\Hierarchy\Visibility $hierarchyVisibility,
        \Magento\VersionsCms\Model\Source\Hierarchy\Menu\Layout $menuLayout,
        array $data = []
    ) {
        $data['hierarchyHelper'] = $cmsHierarchy;
        $this->jsonEncoder = $jsonEncoder;
        $this->cmsHierarchy = $cmsHierarchy;
        parent::__construct($context, $registry, $formFactory, $data);

        $this->setTemplate('hierarchy/edit.phtml');

        $this->currentStore = $this->getRequest()->getParam('store');
        $this->sourceYesno = $sourceYesno;
        $this->menuListmode = $menuListmode;
        $this->menuListtype = $menuListtype;
        $this->menuChapter = $menuChapter;
        $this->hierarchyVisibility = $hierarchyVisibility;
        $this->menuLayout = $menuLayout;
        $this->nodePreviewStoreId = $this->_storeManager->isSingleStoreMode() ?
            $this->_storeManager->getStore(true)->getId() :
            $this->currentStore;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => ['id' => 'edit_form', 'action' => $this->getUrl('adminhtml/*/save'), 'method' => 'post'],
            ]
        );

        /**
         * Define general properties for each node
         */
        $fieldset = $form->addFieldset('node_properties_fieldset', ['legend' => __('Page Properties')]);
        $fieldset->addField('nodes_data', 'hidden', ['name' => 'nodes_data']);
        $fieldset->addField('use_default_scope_property', 'hidden', ['name' => 'use_default_scope_property']);

        $currentWebsite = $this->getRequest()->getParam('website');
        $currentStore = $this->getRequest()->getParam('store');
        if ($currentStore) {
            $fieldset->addField('store', 'hidden', ['name' => 'store', 'value' => $currentStore]);
        }
        if ($currentWebsite) {
            $fieldset->addField('website', 'hidden', ['name' => 'website', 'value' => $currentWebsite]);
        }

        $fieldset->addField('removed_nodes', 'hidden', ['name' => 'removed_nodes']);
        $fieldset->addField('node_id', 'hidden', ['name' => 'node_id']);
        $fieldset->addField('node_page_id', 'hidden', ['name' => 'node_page_id']);
        $fieldset->addField(
            'node_label',
            'text',
            [
                'name' => 'label',
                'label' => __('Title'),
                'required' => true,
                'class' => 'validate-no-html-tags',
                'onchange' => 'hierarchyNodes.nodeChanged()',
                'tabindex' => '10',
            ]
        );
        $fieldset->addField(
            'node_identifier',
            'text',
            [
                'name' => 'identifier',
                'label' => __('URL Key'),
                'required' => true,
                'class' => 'validate-identifier',
                'onchange' => 'hierarchyNodes.nodeChanged()',
                'tabindex' => '20'
            ]
        );
        $fieldset->addField('node_label_text', 'note', ['label' => __('Title')]);
        $fieldset->addField('node_identifier_text', 'note', ['label' => __('URL Key')]);
        $fieldset->addField(
            'node_preview',
            'link',
            ['label' => __('Preview'), 'href' => '#', 'value' => __('Preview is not available.')]
        );

        $yesNoOptions = $this->sourceYesno->toOptionArray();

        /**
         * Define field set with elements for root nodes
         */
        if ($this->cmsHierarchy->isMetadataEnabled()) {
            $fieldset = $form->addFieldset(
                'metadata_fieldset',
                ['legend' => __('Render Metadata in HTML Head.')]
            );
            $fieldset->addField(
                'meta_first_last',
                'select',
                [
                    'label' => __('First'),
                    'title' => __('First'),
                    'name' => 'meta_first_last',
                    'values' => $yesNoOptions,
                    'onchange' => 'hierarchyNodes.nodeChanged()',
                    'container_id' => 'field_meta_first_last',
                    'tabindex' => '30'
                ]
            );
            $fieldset->addField(
                'meta_next_previous',
                'select',
                [
                    'label' => __('Next/Previous'),
                    'title' => __('Next/Previous'),
                    'name' => 'meta_next_previous',
                    'values' => $yesNoOptions,
                    'onchange' => 'hierarchyNodes.nodeChanged()',
                    'container_id' => 'field_meta_next_previous',
                    'tabindex' => '40'
                ]
            );
            $fieldset->addField(
                'meta_cs_enabled',
                'select',
                [
                    'label' => __('Enable Chapter/Section'),
                    'title' => __('Enable Chapter/Section'),
                    'name' => 'meta_cs_enabled',
                    'values' => $yesNoOptions,
                    'onchange' => 'hierarchyNodes.nodeChanged()',
                    'container_id' => 'field_meta_cs_enabled',
                    'note' => __('Enables Chapter/Section functionality for this node, its sub-nodes and pages'),
                    'tabindex' => '45'
                ]
            );
            $fieldset->addField(
                'meta_chapter_section',
                'select',
                [
                    'label' => __('Chapter/Section'),
                    'title' => __('Chapter/Section'),
                    'name' => 'meta_chapter_section',
                    'values' => $this->menuChapter->toOptionArray(),
                    'onchange' => 'hierarchyNodes.nodeChanged()',
                    'container_id' => 'field_meta_chapter_section',
                    'note' => __('Defines this node as Chapter/Section'),
                    'tabindex' => '50'
                ]
            );
        }

        /**
         * Pagination options
         */
        $pagerFieldset = $form->addFieldset(
            'pager_fieldset',
            ['legend' => __('Pagination Options for Nested Pages')]
        );
        $pagerFieldset->addField(
            'pager_visibility',
            'select',
            [
                'label' => __('Enable Pagination'),
                'name' => 'pager_visibility',
                'values' => $this->hierarchyVisibility->toOptionArray(),
                'value' => \Magento\VersionsCms\Helper\Hierarchy::METADATA_VISIBILITY_PARENT,
                'onchange' => "hierarchyNodes.metadataChanged('pager_visibility', 'pager_fieldset')",
                'tabindex' => '70'
            ]
        );
        $pagerFieldset->addField(
            'pager_frame',
            'text',
            [
                'name' => 'pager_frame',
                'label' => __('Frame'),
                'class' => 'validate-digits',
                'onchange' => 'hierarchyNodes.nodeChanged()',
                'container_id' => 'field_pager_frame',
                'note' => __('Set the number of links to display at one time.'),
                'tabindex' => '80'
            ]
        );
        $pagerFieldset->addField(
            'pager_jump',
            'text',
            [
                'name' => 'pager_jump',
                'label' => __('Frame Skip'),
                'class' => 'validate-digits',
                'onchange' => 'hierarchyNodes.nodeChanged()',
                'container_id' => 'field_pager_jump',
                'note' => __(
                    'If the Current Frame Position does not cover Utmost Pages, '
                    . 'will render Link to Current Position plus/minus this Value'
                ),
                'tabindex' => '90'
            ]
        );

        /**
         * Context menu options
         */
        $menuFieldset = $form->addFieldset('menu_fieldset', ['legend' => __('Page Navigation Menu Options')]);
        $menuFieldset->addField(
            'menu_excluded',
            'select',
            [
                'label' => __('Exclude from Navigation Menu'),
                'name' => 'menu_excluded',
                'values' => $yesNoOptions,
                'onchange' => "hierarchyNodes.nodeChanged()",
                'container_id' => 'field_menu_excluded',
                'tabindex' => '100'
            ]
        );
        $menuFieldset->addField(
            'menu_visibility',
            'select',
            [
                'label' => __('Show in navigation menu.'),
                'name' => 'menu_visibility',
                'values' => $yesNoOptions,
                'onchange' => "hierarchyNodes.metadataChanged('menu_visibility', 'menu_fieldset')",
                'container_id' => 'field_menu_visibility',
                'tabindex' => '110'
            ]
        );
        $menuFieldset->addField(
            'menu_layout',
            'select',
            [
                'label' => __('Menu Layout'),
                'name' => 'menu_layout',
                'values' => $this->menuLayout->toOptionArray(true),
                'onchange' => "hierarchyNodes.nodeChanged()",
                'container_id' => 'field_menu_layout',
                'tabindex' => '115'
            ]
        );
        $menuBriefOptions = [
            ['value' => 1, 'label' => __('Only Children')],
            ['value' => 0, 'label' => __('Neighbours and Children')]
        ];
        $menuFieldset->addField(
            'menu_brief',
            'select',
            [
                'label' => __('Menu Detalization'),
                'name' => 'menu_brief',
                'values' => $menuBriefOptions,
                'onchange' => "hierarchyNodes.nodeChanged()",
                'container_id' => 'field_menu_brief',
                'tabindex' => '120'
            ]
        );
        $menuFieldset->addField(
            'menu_levels_down',
            'text',
            [
                'name' => 'menu_levels_down',
                'label' => __('Maximal Depth'),
                'class' => 'validate-digits',
                'onchange' => 'hierarchyNodes.nodeChanged()',
                'container_id' => 'field_menu_levels_down',
                'note' => __('Node Levels to Include'),
                'tabindex' => '130'
            ]
        );
        $menuFieldset->addField(
            'menu_ordered',
            'select',
            [
                'label' => __('List Type'),
                'title' => __('List Type'),
                'name' => 'menu_ordered',
                'values' => $this->menuListtype->toOptionArray(),
                'onchange' => 'hierarchyNodes.menuListTypeChanged()',
                'container_id' => 'field_menu_ordered',
                'tabindex' => '140'
            ]
        );
        $menuFieldset->addField(
            'menu_list_type',
            'select',
            [
                'label' => __('List Style'),
                'title' => __('List Style'),
                'name' => 'menu_list_type',
                'values' => $this->menuListmode->toOptionArray(),
                'onchange' => 'hierarchyNodes.nodeChanged()',
                'container_id' => 'field_menu_list_type',
                'tabindex' => '150'
            ]
        );

        /**
         * Top menu options
         */
        $menuFieldset = $form->addFieldset(
            'top_menu_fieldset',
            ['legend' => __('Main Navigation Menu Options')]
        );
        $menuFieldset->addField(
            'top_menu_excluded',
            'select',
            [
                'label' => __('Exclude from Navigation Menu'),
                'name' => 'top_menu_excluded',
                'values' => $yesNoOptions,
                'onchange' => "hierarchyNodes.nodeChanged()",
                'container_id' => 'field_top_menu_excluded',
                'tabindex' => '170'
            ]
        );
        $menuFieldset->addField(
            'top_menu_visibility',
            'select',
            [
                'label' => __('Show in navigation menu.'),
                'name' => 'top_menu_visibility',
                'values' => $yesNoOptions,
                'onchange' => "hierarchyNodes.metadataChanged('top_menu_visibility', 'top_menu_fieldset')",
                'container_id' => 'field_top_menu_visibility',
                'tabindex' => '160'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Retrieve buttons HTML for Cms Page Grid
     *
     * @return string
     */
    public function getPageGridButtonsHtml()
    {
        $addButtonData = [
            'id' => 'add_cms_pages',
            'label' => $this->escapeHtmlAttr(__('Add selected page(s) to the tree')),
            'onclick' => 'hierarchyNodes.pageGridAddSelected()',
            'class' => 'add'
        ];

        return $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setData($addButtonData)
            ->toHtml();
    }

    /**
     * Retrieve Buttons HTML for Page Properties form
     *
     * @return string
     */
    public function getPagePropertiesButtons()
    {
        $buttons = [];
        $buttons[] = $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setData(
                [
                    'id' => 'delete_node_button',
                    'label' => __('Remove from tree.'),
                    'onclick' => 'hierarchyNodes.deleteNodePage()',
                    'class' => 'delete'
                ]
            )
            ->toHtml();
        $buttons[] = $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setData(
                [
                    'id' => 'cancel_node_button',
                    'label' => __('Cancel'),
                    'onclick' => 'hierarchyNodes.cancelNodePage()',
                    'class' => 'cancel'
                ]
            )
            ->toHtml();
        $buttons[] = $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setData(
                [
                    'id' => 'save_node_button',
                    'label' => __('Save'),
                    'onclick' => 'hierarchyNodes.saveNodePage()',
                    'class' => 'save'
                ]
            )
            ->toHtml();

        return join(' ', $buttons);
    }

    /**
     * Retrieve buttons HTML for Pages Tree
     *
     * @return string
     */
    public function getTreeButtonsHtml()
    {
        return $this->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setData(
                [
                    'id' => 'new_node_button',
                    'label' => $this->escapeHtmlAttr(__('Add Node...')),
                    'onclick' => 'hierarchyNodes.newNodePage()',
                    'class' => 'add'
                ]
            )
            ->toHtml();
    }

    /**
     * Retrieve current nodes Json.
     *
     * Data loaded from DB or from model in case we had error in save process.
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getNodesJson()
    {
        /** @var $nodeModel \Magento\VersionsCms\Model\Hierarchy\Node */
        $nodeModel = $this->_coreRegistry->registry('current_hierarchy_node');
        $this->setData('current_scope', $nodeModel->getScope());
        $this->setData('current_scope_id', $nodeModel->getScopeId());

        $this->setData('use_default_scope', $nodeModel->getIsInherited());
        $nodes = $nodeModel->getNodesData();
        unset($nodeModel);

        foreach ($nodes as &$node) {
            $node['assigned_to_store'] = !$this->getData('use_default_scope');
        }

        // fill in custom meta_chapter_section field
        $c = count($nodes);
        for ($i = 0; $i < $c; $i++) {
            if (isset(
                $nodes[$i]['meta_chapter']
            ) && isset(
                $nodes[$i]['meta_section']
            ) && $nodes[$i]['meta_chapter'] && $nodes[$i]['meta_section']
            ) {
                $nodes[$i]['meta_chapter_section'] = 'both';
            } elseif (isset($nodes[$i]['meta_chapter']) && $nodes[$i]['meta_chapter']) {
                $nodes[$i]['meta_chapter_section'] = 'chapter';
            } elseif (isset($nodes[$i]['meta_section']) && $nodes[$i]['meta_section']) {
                $nodes[$i]['meta_chapter_section'] = 'section';
            } else {
                $nodes[$i]['meta_chapter_section'] = '';
            }
        }

        return $this->jsonEncoder->encode($nodes);
    }

    /**
     * Check if passed node available for store in case this node representation of page.
     *
     * If node does not represent page then method will return true.
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $node
     * @param null|int $store
     * @return bool
     */
    public function isNodeAvailableForStore($node, $store)
    {
        if (!$node->getPageId()) {
            return true;
        }

        if (!$store) {
            return true;
        }

        if ($node->getPageInStores() == '0') {
            return true;
        }

        $stores = explode(',', $node->getPageInStores());
        if (in_array($store, $stores)) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve Grid JavaScript object name
     *
     * @return string
     */
    public function getGridJsObject()
    {
        return $this->getParentBlock()->getChildBlock('cms_page_grid')->getJsObjectName();
    }

    /**
     * Prepare translated label 'Save' for button used in Js.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getButtonSaveLabel()
    {
        return __('Add to tree.');
    }

    /**
     * Prepare translated label 'Update' for button used in Js
     *
     * @return \Magento\Framework\Phrase
     */
    public function getButtonUpdateLabel()
    {
        return __('Update');
    }

    /**
     * Return legend for Hierarchy node fieldset
     *
     * @return \Magento\Framework\Phrase
     */
    public function getNodeFieldsetLegend()
    {
        return __('Node Properties');
    }

    /**
     * Return legend for Hierarchy page fieldset
     *
     * @return \Magento\Framework\Phrase
     */
    public function getPageFieldsetLegend()
    {
        return __('Page Properties');
    }

    /**
     * Getter for protected currentStore
     *
     * @return null|int
     */
    public function getCurrentStore()
    {
        return $this->currentStore;
    }

    /**
     * Get current store view if available, or get any in current scope
     *
     * @return \Magento\Store\Model\Store
     */
    protected function _getStore()
    {
        $store = null;
        if ($this->currentStore) {
            $store = $this->_storeManager->getStore($this->currentStore);
        } elseif ($this->getCurrentScope() == \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_WEBSITE) {
            $store = $this->_storeManager->getWebsite($this->getCurrentScopeId())->getDefaultStore();
        }

        if (!$store) {
            $store = $this->_storeManager->getDefaultStoreView();
            if (!$store) {
                foreach ($this->_storeManager->getStores() as $store) {
                    return $store;
                }
            }
        }

        return $store;
    }

    /**
     * Return URL query param for current store
     *
     * @return string
     */
    public function getCurrentStoreUrlParam()
    {
        return '?___store=' . $this->_getStore()->getCode();
    }

    /**
     * Return Base URL for current Store
     *
     * @return string
     */
    public function getStoreBaseUrl()
    {
        return $this->_getStore()->getBaseUrl();
    }

    /**
     * Check if node can be previewed
     *
     * @return bool
     */
    public function isNodePreviewAvailable()
    {
        return !empty($this->nodePreviewStoreId);
    }

    /**
     * Retrieve html of store switcher added from layout
     *
     * @return string
     */
    public function getStoreSwitcherHtml()
    {
        return $this->getLayout()->getBlock('scope_switcher')->toHtml();
    }

    /**
     * Return List styles separately for unordered/ordererd list as json
     *
     * @return string
     */
    public function getListModesJson()
    {
        $listModes = $this->menuListmode->toOptionArray();
        $result = [];
        foreach ($listModes as $type => $label) {
            if ($type == '') {
                continue;
            }
            $listType = in_array($type, ['circle', 'disc', 'square']) ? '0' : '1';
            $result[$listType][$type] = $label;
        }

        return $this->jsonEncoder->encode($result);
    }

    /**
     * Retrieve Url to Hierarchy delete action
     *
     * @return string
     */
    public function getDeleteHierarchyUrl()
    {
        $params = [
            'website' => $this->getRequest()->getParam('website'),
            'store' => $this->getRequest()->getParam('store'),
            'scopes' => $this->getData('current_scope') . '_' . $this->getData('current_scope_id')
        ];
        return $this->getUrl('adminhtml/*/delete', $params);
    }
}
