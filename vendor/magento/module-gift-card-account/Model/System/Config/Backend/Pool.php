<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\System\Config\Backend;

class Pool extends \Magento\Framework\App\Config\Value
{
    /**
     * Gift card account pool
     *
     * @var \Magento\GiftCardAccount\Model\Pool
     */
    protected $_giftCardAccountPool = null;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\GiftCardAccount\Model\Pool $giftCardAccountPool
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\GiftCardAccount\Model\Pool $giftCardAccountPool,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_giftCardAccountPool = $giftCardAccountPool;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        if ($this->isValueChanged()) {
            if (!$this->_registry->registry('giftcardaccount_code_length_check')) {
                $this->_registry->register('giftcardaccount_code_length_check', 1);
                $this->_checkMaxLength();
            }
        }
        return parent::beforeSave();
    }

    /**
     * @return $this
     */
    public function afterSave()
    {
        if ($this->isValueChanged()) {
            $this->_giftCardAccountPool->cleanupFree();
        }
        return parent::afterSave();
    }

    /**
     * Check Max Length
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _checkMaxLength()
    {
        $groups = $this->getGroups();
        if (isset($groups['general']['fields'])) {
            $fields = $groups['general']['fields'];
        }

        $len = 0;
        $codeLen = 0;
        if (isset($fields['code_length']['value'])) {
            $codeLen = (int)$fields['code_length']['value'];
            $len += $codeLen;
        }
        if (isset($fields['code_suffix']['value'])) {
            $len += strlen($fields['code_suffix']['value']);
        }
        if (isset($fields['code_prefix']['value'])) {
            $len += strlen($fields['code_prefix']['value']);
        }
        if (isset($fields['code_split']['value'])) {
            $v = (int)$fields['code_split']['value'];
            if ($v > 0 && $v < $codeLen) {
                $sep = $this->_giftCardAccountPool->getCodeSeparator();
                $len += ceil($codeLen / $v) * strlen($sep) - 1;
            }
        }

        if ($len > 255) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The maximum generated code length is 255. Please correct your settings.')
            );
        }
    }
}
