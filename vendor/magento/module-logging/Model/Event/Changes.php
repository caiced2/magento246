<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Model\Event;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Logging event changes model
 *
 * @method string getSourceName()
 * @method \Magento\Logging\Model\Event\Changes setSourceName(string $value)
 * @method int getEventId()
 * @method \Magento\Logging\Model\Event\Changes setEventId(int $value)
 * @method int getSourceId()
 * @method \Magento\Logging\Model\Event\Changes setSourceId(int $value)
 * @method string getOriginalData()
 * @method \Magento\Logging\Model\Event\Changes setOriginalData(array|string $value)
 * @method string getResultData()
 * @method \Magento\Logging\Model\Event\Changes setResultData(array|string $value)
 *
 * @api
 * @since 100.0.2
 */
class Changes extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Set of fields that should not be logged for all models
     *
     * @var array
     */
    protected $_globalSkipFields = [];

    /**
     * Set of fields that should not be logged per expected model
     *
     * @var array
     */
    protected $_skipFields = [];

    /**
     * Store difference between original data and result data of model
     *
     * @var array
     */
    protected $_difference = null;

    /**
     * Serializer Instance
     *
     * @var Json $json
     */
    private $json;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $skipFields
     * @param array $data
     * @param Json|null $json
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $skipFields = [],
        array $data = [],
        Json $json = null
    ) {
        $this->_globalSkipFields = $skipFields;
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource
     *
     * Get fields that should not be logged for all models
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Logging\Model\ResourceModel\Event\Changes::class);
    }

    /**
     * Set some data automatically before saving model
     *
     * @return \Magento\Logging\Model\Event
     */
    public function beforeSave()
    {
        $this->_calculateDifference();
        $this->setOriginalData($this->json->serialize($this->getOriginalData()));
        $this->setResultData($this->json->serialize($this->getResultData()));
        return parent::beforeSave();
    }

    /**
     * Define if current model has difference between original and result data
     *
     * @return bool
     */
    public function hasDifference()
    {
        $difference = $this->_calculateDifference();
        return !empty($difference);
    }

    /**
     * Calculate difference between original and result data and return that difference
     *
     * @return null|array|int
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function _calculateDifference()
    {
        if ($this->_difference === null) {
            $updatedParams = $newParams = $sameParams = $difference = [];
            $newOriginalData = $origData = $this->getOriginalData();
            $newResultData = $resultData = $this->getResultData();

            if (!is_array($origData)) {
                $origData = [];
            }
            if (!is_array($resultData)) {
                $resultData = [];
            }

            if (!$origData && $resultData) {
                $newOriginalData = ['__was_created' => true];
                $difference = $resultData;
            } elseif ($origData && !$resultData) {
                $newResultData = ['__was_deleted' => true];
                $difference = $origData;
            } elseif ($origData && $resultData) {
                $newParams = array_diff_key($resultData, $origData);
                $sameParams = array_intersect_key($origData, $resultData);
                foreach ($sameParams as $key => $value) {
                    if ($origData[$key] != $resultData[$key]) {
                        $updatedParams[$key] = $resultData[$key];
                    }
                }
                $newOriginalData = array_intersect_key($origData, $updatedParams);
                $difference = $newResultData = array_merge($updatedParams, $newParams);
                if ($difference && !$updatedParams) {
                    $newOriginalData = ['__no_changes' => true];
                }
            }

            $this->setOriginalData($newOriginalData);
            $this->setResultData($newResultData);

            $this->_difference = $difference;
        }
        return $this->_difference;
    }

    /**
     * Set skip fields and clear model data
     *
     * @param array $skipFields
     * @return void
     */
    public function cleanupData($skipFields)
    {
        if ($skipFields && is_array($skipFields)) {
            $this->_skipFields = $skipFields;
        }
        $this->setOriginalData($this->_cleanupData($this->getOriginalData()));
        $this->setResultData($this->_cleanupData($this->getResultData()));
    }

    /**
     * Clear model data from objects, arrays and fields that should be skipped
     *
     * @param array $data
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _cleanupData($data)
    {
        if (!$data || !is_array($data)) {
            return [];
        }
        $skipFields = $this->_skipFields;
        if (!$skipFields || !is_array($skipFields)) {
            $skipFields = [];
        }
        $clearedData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $index => $item) {
                    if (is_scalar($item)) {
                        $clearedData[$key . ' [' . $index . ']'] = $item;
                    }
                }
            }
            if (!in_array(
                $key,
                $this->_globalSkipFields
            ) && !in_array(
                $key,
                $skipFields
            ) && !is_array(
                $value
            ) && !is_object(
                $value
            )
            ) {
                $clearedData[$key] = $value;
            }
        }
        return $clearedData;
    }
}
