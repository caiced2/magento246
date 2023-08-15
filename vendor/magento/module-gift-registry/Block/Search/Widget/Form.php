<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Search\Widget;

/**
 * Gift registry quick search widget block
 *
 */
class Form extends \Magento\GiftRegistry\Block\Search\Quick implements \Magento\Widget\Block\BlockInterface
{
    /**
     * @var \Magento\GiftRegistry\Model\Source\Search
     */
    protected $sourceSearch;

    /**
     * Search form select options
     *
     * @var array
     */
    protected $_selectOptions;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\GiftRegistry\Helper\Data $giftRegistryData
     * @param \Magento\GiftRegistry\Model\TypeFactory $typeFactory
     * @param \Magento\GiftRegistry\Model\Source\Search $sourceSearch
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\GiftRegistry\Helper\Data $giftRegistryData,
        \Magento\GiftRegistry\Model\TypeFactory $typeFactory,
        \Magento\GiftRegistry\Model\Source\Search $sourceSearch,
        array $data = []
    ) {
        parent::__construct($context, $giftRegistryData, $typeFactory, $data);
        $this->sourceSearch = $sourceSearch;
    }

    /**
     * Make form types getter always return array
     *
     * @return array
     */
    protected function _getFormTypes()
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
     * Check if specified form must be available as part of quick search form
     *
     * @param mixed $code
     * @return bool
     */
    protected function _checkForm($code)
    {
        return in_array($code, $this->_getFormTypes());
    }

    /**
     * Check if all quick search forms must be used
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function useAllForms()
    {
        $code = \Magento\GiftRegistry\Model\Source\Search::SEARCH_ALL_FORM;
        return $this->_checkForm($code);
    }

    /**
     * Check if name quick search form must be used
     *
     * @return bool
     */
    public function useNameForm()
    {
        $code = \Magento\GiftRegistry\Model\Source\Search::SEARCH_NAME_FORM;
        return $this->useAllForms() || $this->_checkForm($code);
    }

    /**
     * Check if email quick search form must be used
     *
     * @return string
     */
    public function useEmailForm()
    {
        $code = \Magento\GiftRegistry\Model\Source\Search::SEARCH_EMAIL_FORM;
        return $this->useAllForms() || $this->_checkForm($code);
    }

    /**
     * Check if id quick search form must be used
     *
     * @return bool
     */
    public function useIdForm()
    {
        $code = \Magento\GiftRegistry\Model\Source\Search::SEARCH_ID_FORM;
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
            'search-by'
        )->setOptions(
            $options
        );

        return $select->getHtml();
    }

    /**
     * Retrieve options for search form select
     *
     * @return array
     */
    public function getSearchFormOptions()
    {
        if ($this->_selectOptions === null) {
            $allForms = $this->sourceSearch->getTypes();
            $useForms = $this->_getFormTypes();
            $codeAll = \Magento\GiftRegistry\Model\Source\Search::SEARCH_ALL_FORM;

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
}
