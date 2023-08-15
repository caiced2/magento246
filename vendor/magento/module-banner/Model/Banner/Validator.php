<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Model\Banner;

use Magento\Store\Model\Store;

class Validator
{
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * @var \Magento\Backend\Helper\Js
     */
    protected $jsHelper;

    /**
     * @var array
     */
    protected $preparePostKeys =[
        'banner_catalog_rules' => 'rule',
        'banner_sales_rules' => 'rule',
        'types' => 'empty'
    ];

    /**
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Backend\Helper\Js $jsHelper
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Backend\Helper\Js $jsHelper
    ) {
        $this->storeManager = $storeManager;
        $this->jsHelper = $jsHelper;
    }

    /**
     * Prepare data for save
     *
     * @param array $data
     * @return array
     */
    public function prepareSaveData(array $data)
    {
        $data = $this->prepareContentData($data);
        $data = $this->filterDisallowedData($data);
        $data = $this->preparePostData($data);
        return $data;
    }

    /**
     * Set up content data to be in array
     *
     * @param array $data
     * @return array
     */
    private function prepareContentData($data)
    {
        if (isset($data['store_contents']) && isset($data['use_default_value'])) {
            if ($data['use_default_value'] === "true") {
                $data['store_contents'] = [
                    $data['store_id'] => '',
                    Store::DEFAULT_STORE_ID => $data['default_contents']
                ];
            } else {
                $data['store_contents'] = [
                    $data['store_id'] => $data['store_contents']
                ];
            }
        }

        return $data;
    }

    /**
     * Filter disallowed data
     *
     * @param array $data
     * @return array
     */
    protected function filterDisallowedData(array $data)
    {
        $currentStores = array_keys($this->storeManager->getStores(true));

        if (isset($data['store_contents_not_use'])) {
            $data['store_contents_not_use'] = array_intersect($data['store_contents_not_use'], $currentStores);
        }

        if (isset($data['store_contents'])) {
            $data['store_contents'] = array_intersect_key($data['store_contents'], array_flip($currentStores));
        }

        return $data;
    }

    /**
     * Prepare post data
     *
     * @param array $data
     * @return array
     */
    protected function preparePostData(array $data)
    {
        foreach ($this->preparePostKeys as $postKey => $type) {
            if (!isset($data[$postKey])) {
                $data[$postKey] = [];
            } elseif ($type === 'rule') {
                $related = [];
                foreach ($data[$postKey] as $key => $rule) {
                    $related[$key] = (int)$rule['rule_id'];
                }

                $data[$postKey] = $related;
            }
        }
        return $data;
    }
}
