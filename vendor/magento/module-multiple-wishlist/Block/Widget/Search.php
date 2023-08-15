<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Wishlist Search Widget Block
 */
namespace Magento\MultipleWishlist\Block\Widget;

class Search extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    /**
     * Search form select options
     *
     * @var array
     */
    protected $_selectOptions;

    /**
     * Config source search model
     *
     * @var \Magento\MultipleWishlist\Model\Config\Source\Search
     */
    protected $_configSourceSearch;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\MultipleWishlist\Model\Config\Source\Search $configSourceSearch
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\MultipleWishlist\Model\Config\Source\Search $configSourceSearch,
        \Magento\Framework\Math\Random $mathRandom,
        array $data = []
    ) {
        $this->_configSourceSearch = $configSourceSearch;
        $this->mathRandom = $mathRandom;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve form types list
     *
     * @return array
     */
    protected function _getEnabledFormTypes()
    {
        $types = $this->_getData('types');
        if (is_array($types)) {
            return $types;
        }
        if (empty($types)) {
            $types = [];
        } else {
            $types = explode(',', $types);
        }
        $this->setData('types', $types);

        return $types;
    }

    /**
     * Check whether specified form must be available as part of quick search form
     *
     * @param string $code
     * @return bool
     */
    protected function _checkForm($code)
    {
        return in_array($code, $this->_getEnabledFormTypes());
    }

    /**
     * Check if all quick search forms must be used
     *
     * @return bool
     */
    public function useAllForms()
    {
        $code = \Magento\MultipleWishlist\Model\Config\Source\Search::WISHLIST_SEARCH_DISPLAY_ALL_FORMS;
        return $this->_checkForm($code);
    }

    /**
     * Check if name quick search form must be used
     *
     * @return bool
     */
    public function useNameForm()
    {
        $code = \Magento\MultipleWishlist\Model\Config\Source\Search::WISHLIST_SEARCH_DISPLAY_NAME_FORM;
        return $this->useAllForms() || $this->_checkForm($code);
    }

    /**
     * Check if email quick search form must be used
     *
     * @return string
     */
    public function useEmailForm()
    {
        $code = \Magento\MultipleWishlist\Model\Config\Source\Search::WISHLIST_SEARCH_DISPLAY_EMAIL_FORM;
        return $this->useAllForms() || $this->_checkForm($code);
    }

    /**
     * Retrieve HTML for search form select
     *
     * @return string
     */
    public function getSearchFormSelect()
    {
        $options = array_merge(
            [['value' => '', 'label' => __('Select Search Type')]],
            $this->getSearchFormOptions()
        );

        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setName(
            'search_by'
        )->setId(
            $this->getBlockId() . '-search_by'
        )->setOptions(
            $options
        );

        return $select->getHtml();
    }

    /**
     * Add current block identifier to dom node id
     *
     * @return string
     */
    public function getBlockId()
    {
        if ($this->getData('id') === null) {
            $this->setData('id', $this->mathRandom->getUniqueHash());
        }
        return $this->getData('id');
    }

    /**
     * Retrieve options for search form select
     *
     * @return array
     */
    public function getSearchFormOptions()
    {
        if ($this->_selectOptions === null) {
            $allForms = $this->_configSourceSearch->getTypes();
            $useForms = $this->_getEnabledFormTypes();
            $codeAll = \Magento\MultipleWishlist\Model\Config\Source\Search::WISHLIST_SEARCH_DISPLAY_ALL_FORMS;

            if (in_array($codeAll, $useForms)) {
                unset($allForms[$codeAll]);
            } else {
                foreach ($allForms as $type => $label) {
                    if (!in_array($type, $useForms)) {
                        unset($allForms[$type]);
                    }
                }
            }
            $options = [];
            foreach ($allForms as $type => $label) {
                $options[] = ['value' => $type, 'label' => $label];
            }
            $this->_selectOptions = $options;
        }
        return $this->_selectOptions;
    }

    /**
     * Use search form select in quick search form
     *
     * @return bool
     */
    public function useSearchFormSelect()
    {
        return count($this->getSearchFormOptions()) > 1;
    }

    /**
     * Retrieve Multiple Search URL
     *
     * @return string
     */
    public function getActionUrl()
    {
        return $this->getUrl('wishlist/search/results');
    }
}
