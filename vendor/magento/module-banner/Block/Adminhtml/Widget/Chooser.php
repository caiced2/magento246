<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Banners chooser for Banner Rotator widget
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Banner\Block\Adminhtml\Widget;

use Magento\Backend\Block\Widget\Grid\Column;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Chooser extends \Magento\Banner\Block\Adminhtml\Banner\Grid
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory
     * @param \Magento\Banner\Model\Config $bannerConfig
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory,
        \Magento\Banner\Model\Config $bannerConfig,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $bannerColFactory, $bannerConfig, $data);
        $this->_elementFactory = $elementFactory;
    }

    /**
     * Store selected banner Ids
     * Used in initial setting selected banners
     *
     * @var array
     */
    protected $_selectedBanners = [];

    /**
     * Store hidden banner ids field id
     *
     * @var string
     */
    protected $_elementValueId = '';

    /**
     * Block construction, prepare grid params
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setDefaultFilter(['in_banners' => 1]);
    }

    /**
     * Prepare chooser element HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element Form Element
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function prepareElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->_elementValueId = "{$element->getId()}";
        $this->_selectedBanners = explode(',', $element->getValue());

        //Create hidden field that store selected banner ids
        $hidden = $this->_elementFactory->create('hidden', ['data' => $element->getData()]);
        $hidden->setId($this->_elementValueId)->setForm($element->getForm());
        $hiddenHtml = $hidden->getElementHtml();

        $element->setValue('')->setValueClass('value2');
        $element->setData('css_class', 'grid-chooser');
        $element->setData('after_element_html', $hiddenHtml . $this->toHtml());
        $element->setData('no_wrap_as_addon', true);

        return $element;
    }

    /**
     * Grid row init js callback
     *
     * @return string
     */
    public function getRowInitCallback()
    {
        return '
        function(grid, row){
            if(!grid.selBannersIds){
                grid.selBannersIds = {};
                if($(\'' .
            $this->_elementValueId .
            '\').value != \'\'){
                    var elementValues = $(\'' .
            $this->_elementValueId .
            '\').value.split(\',\');
                    for(var i = 0; i < elementValues.length; i++){
                        grid.selBannersIds[elementValues[i]] = i+1;
                    }
                }
                grid.reloadParams = {};
                grid.reloadParams[\'selected_banners[]\'] = Object.keys(grid.selBannersIds);
            }
            var inputs      = Element.select($(row), \'input\');
            var checkbox    = inputs[0];
            var position    = inputs[1];
            var bannersNum  = grid.selBannersIds.length;
            var bannerId    = checkbox.value;

            inputs[1].checkboxElement = checkbox;

            var indexOf = Object.keys(grid.selBannersIds).indexOf(bannerId);
            if(indexOf >= 0){
                checkbox.checked = true;
                if (!position.value) {
                    position.value = indexOf + 1;
                }
            }

            Event.observe(position,\'change\', function(){
                var checkb = Element.select($(row), \'input\')[0];
                if(checkb.checked){
                    grid.selBannersIds[checkb.value] = this.value;
                    var idsclone = Object.clone(grid.selBannersIds);
                    var bans = Object.keys(grid.selBannersIds);
                    var pos = Object.values(grid.selBannersIds).sort(sortNumeric);
                    var banners = [];
                    var k = 0;

                    for(var j = 0; j < pos.length; j++){
                        for(var i = 0; i < bans.length; i++){
                            if(idsclone[bans[i]] == pos[j]){
                                banners[k] = bans[i];
                                k++;
                                delete(idsclone[bans[i]]);
                                break;
                            }
                        }
                    }
                    $(\'' .
            $this->_elementValueId .
            '\').value = banners.join(\',\');
                }
            });
        }
        ';
    }

    /**
     * Grid Row JS Callback
     *
     * @return string
     */
    public function getRowClickCallback()
    {
        return '
            function (grid, event) {
                if(!grid.selBannersIds){
                    grid.selBannersIds = {};
                }

                var trElement   = Event.findElement(event, "tr");
                var isInput     = Event.element(event).tagName == \'INPUT\';
                var inputs      = Element.select(trElement, \'input\');
                var checkbox    = inputs[0];
                var position    = inputs[1].value || 1;
                var checked     = isInput ? checkbox.checked : !checkbox.checked;
                checkbox.checked = checked;
                var bannerId    = checkbox.value;

                if(checked){
                    if(Object.keys(grid.selBannersIds).indexOf(bannerId) < 0){
                        grid.selBannersIds[bannerId] = position;
                    }
                }
                else{
                    delete(grid.selBannersIds[bannerId]);
                }

                var idsclone = Object.clone(grid.selBannersIds);
                var bans = Object.keys(grid.selBannersIds);
                var pos = Object.values(grid.selBannersIds).sort(sortNumeric);
                var banners = [];
                var k = 0;
                for(var j = 0; j < pos.length; j++){
                    for(var i = 0; i < bans.length; i++){
                        if(idsclone[bans[i]] == pos[j]){
                            banners[k] = bans[i];
                            k++;
                            delete(idsclone[bans[i]]);
                            break;
                        }
                    }
                }
                $(\'' .
            $this->_elementValueId .
            '\').value = banners.join(\',\');
                grid.reloadParams = {};
                grid.reloadParams[\'selected_banners[]\'] = banners;
            }
        ';
    }

    /**
     * Checkbox Check JS Callback
     *
     * @return string
     */
    public function getCheckboxCheckCallback()
    {
        return 'function (grid, element, checked) {
                    if(!grid.selBannersIds){
                        grid.selBannersIds = {};
                    }
                    var checkbox    = element;

                    checkbox.checked = checked;
                    var bannerId    = checkbox.value;
                    if(bannerId == \'on\'){
                        return;
                    }
                    var trElement   = element.up(\'tr\');
                    var inputs      = Element.select(trElement, \'input\');
                    var position    = inputs[1].value || 1;

                    if(checked){
                        if(Object.keys(grid.selBannersIds).indexOf(bannerId) < 0){
                            grid.selBannersIds[bannerId] = position;
                        }
                    }
                    else{
                        delete(grid.selBannersIds[bannerId]);
                    }

                    var idsclone = Object.clone(grid.selBannersIds);
                    var bans = Object.keys(grid.selBannersIds);
                    var pos = Object.values(grid.selBannersIds).sort(sortNumeric);
                    var banners = [];
                    var k = 0;
                    for(var j = 0; j < pos.length; j++){
                        for(var i = 0; i < bans.length; i++){
                            if(idsclone[bans[i]] == pos[j]){
                                banners[k] = bans[i];
                                k++;
                                delete(idsclone[bans[i]]);
                                break;
                            }
                        }
                    }
                    $(\'' .
            $this->_elementValueId .
            '\').value = banners.join(\',\');
                    grid.reloadParams = {};
                    grid.reloadParams[\'selected_banners[]\'] = banners;
                }';
    }

    /**
     * Create grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_banners',
            [
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select',
                'type' => 'checkbox',
                'name' => 'in_banners',
                'values' => $this->getSelectedBanners(),
                'index' => 'banner_id'
            ]
        );

        $this->addColumn(
            'position',
            [
                'header' => __('Position'),
                'name' => 'position',
                'type' => 'number',
                'validate_class' => 'validate-number',
                'index' => 'position',
                'editable' => true,
                'filter' => false,
                'edit_only' => true,
                'sortable' => false
            ]
        );
        $this->addColumnsOrder('position', 'banner_is_enabled');

        return parent::_prepareColumns();
    }

    /**
     * Set custom filter for in banner flag
     *
     * @param Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_banners') {
            $bannerIds = $this->getSelectedBanners();
            if (empty($bannerIds)) {
                $bannerIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addBannerIdsFilter($bannerIds);
            } else {
                if ($bannerIds) {
                    $this->getCollection()->addBannerIdsFilter($bannerIds, true);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Disable mass action functionality
     *
     * @return $this
     */
    protected function _prepareMassaction()
    {
        return $this;
    }

    /**
     * Adds additional parameter to URL for loading only banners grid
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'adminhtml/banner_widget/chooser',
            [
                'banners_grid' => true,
                '_current' => true,
                'uniq_id' => $this->getId(),
                'selected_banners' => join(',', $this->getSelectedBanners())
            ]
        );
    }

    /**
     * Setter
     *
     * @param array $selectedBanners
     * @return $this
     */
    public function setSelectedBanners($selectedBanners)
    {
        if (is_string($selectedBanners)) {
            $selectedBanners = explode(',', $selectedBanners);
        }
        $this->_selectedBanners = $selectedBanners;
        return $this;
    }

    /**
     * Set banners' positions of saved banners
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        parent::_prepareCollection();

        foreach ($this->getCollection() as $item) {
            foreach ($this->getSelectedBanners() as $pos => $banner) {
                if ($banner == $item->getBannerId()) {
                    $item->setPosition($pos + 1);
                }
            }
        }
        return $this;
    }

    /**
     * Getter
     *
     * @return array
     */
    public function getSelectedBanners()
    {
        if ($selectedBanners = $this->getRequest()->getParam('selected_banners', $this->_selectedBanners)) {
            $this->setSelectedBanners($selectedBanners);
        }
        return $this->_selectedBanners;
    }
}
