<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Widget;

use Magento\Customer\Model\Context;

/**
 * Banner Widget Block
 *
 * @api
 * @since 100.0.2
 */
class Banner extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    /**
     * Rotation mode "series" flag: output one of banners sequentially per visitor session
     *
     */
    const BANNER_WIDGET_RORATE_SERIES = 'series';

    /**
     * Rotation mode "random" flag: output one of banners randomly
     *
     */
    const BANNER_WIDGET_RORATE_RANDOM = 'random';

    /**
     * Rotation mode "shuffle" flag: same as "series" but firstly randomize banners scope
     *
     */
    const BANNER_WIDGET_RORATE_SHUFFLE = 'shuffle';

    /**
     * No rotation: show all banners at once
     *
     */
    const BANNER_WIDGET_RORATE_NONE = '';

    /**
     * Store Banner resource instance
     *
     * @var \Magento\Banner\Model\ResourceModel\Banner
     */
    protected $_bannerResource;

    /**
     * @var int
     */
    protected $_currentStoreId;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Banner\Model\ResourceModel\Banner $resource
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Banner\Model\ResourceModel\Banner $resource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_bannerResource = $resource;
        $this->_currentStoreId = $this->_storeManager->getStore()->getId();
    }

    /**
     * Set default display mode if its not set
     *
     * @return string
     */
    public function getDisplayMode()
    {
        if (!$this->_getData('display_mode')) {
            $this->setData('display_mode', \Magento\Banner\Model\Config::BANNER_WIDGET_DISPLAY_FIXED);
        }
        return $this->_getData('display_mode');
    }

    /**
     * Retrieve right rotation mode or return null
     *
     * @return string|null
     */
    public function getRotate()
    {
        if (!$this->_getData(
            'rotate'
        ) || $this->_getData(
            'rotate'
        ) != self::BANNER_WIDGET_RORATE_RANDOM && $this->_getData(
            'rotate'
        ) != self::BANNER_WIDGET_RORATE_SERIES && $this->_getData(
            'rotate'
        ) != self::BANNER_WIDGET_RORATE_SHUFFLE
        ) {
            $this->setData('rotate', null);
        }
        return $this->_getData('rotate');
    }

    /**
     * Set unique id of widget instance if its not set
     *
     * @return string
     */
    public function getUniqueId()
    {
        if (!$this->_getData('unique_id')) {
            // md5() here is not cryptographic use.
            // phpcs:ignore Magento2.Security.InsecureFunction
            $this->setData('unique_id', md5(implode('-', $this->_getData('banner_ids'))));
        }
        return $this->_getData('unique_id');
    }

    /**
     * Retrieves suggested params for rendering the banner - array with following keys:
     * - 'bannersSelected' - array of banner ids suggested to render (null if not set)
     * - 'bannersSequence' - array of banner ids already shown to user (null if not set)
     * These parameters are set by cache when it needs to render some specific banners. However,
     * if parameters are not valid - they must be ignored, because block has fresh and up-to-date values
     * to check the banners that can be shown to user.
     *
     * @return array
     */
    public function getSuggestedParams()
    {
        $params = $this->getData('suggested_params');
        if (!$params) {
            $params = [];
        }

        // Ensure that option keys exist
        $keys = ['bannersSelected', 'bannersSequence'];
        foreach ($keys as $key) {
            if (!isset($params[$key])) {
                $params[$key] = null;
            }
        }

        return $params;
    }

    /**
     * Get banners content by specified banners IDs depend on Rotation mode
     * // TODO: Banner functionality must be refactored with moving sorting/display logic to frontend
     * @param array $bannerIds
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _getBannersContent(array $bannerIds)
    {
        $content = [];
        if (!empty($bannerIds)) {
            $bannerResource = $this->_bannerResource;

            // Process suggested params
            $suggestedParams = $this->getSuggestedParams();
            $suggBannersSelected = $suggestedParams['bannersSelected'];
            $suggBannersSequence = $suggestedParams['bannersSequence'];

            // Choose banner depending on rotation mode
            switch ($this->getRotate()) {
                case self::BANNER_WIDGET_RORATE_RANDOM:
                    // Choose banner either as suggested or randomly
                    $bannerId = null;
                    if ($suggBannersSelected && count($suggBannersSelected) == 1) {
                        $suggBannerId = $suggBannersSelected[0];
                        if (array_search($suggBannerId, $bannerIds) !== false) {
                            $bannerId = $suggBannerId;
                        }
                    }
                    if ($bannerId === null) {
                        $bannerId = $bannerIds[array_rand($bannerIds, 1)];
                    }

                    $_content = $bannerResource->getStoreContent($bannerId, $this->_currentStoreId);
                    if (!empty($_content)) {
                        $content[$bannerId] = $_content;
                    }
                    break;

                case self::BANNER_WIDGET_RORATE_SHUFFLE:
                case self::BANNER_WIDGET_RORATE_SERIES:
                    $isShuffle = $this->getRotate() == self::BANNER_WIDGET_RORATE_SHUFFLE;
                    $bannerId = null;
                    $bannersSequence = null;

                    // Compose banner sequence either from suggested sequence or from user session data
                    if ($suggBannersSequence !== null) {
                        // Check that suggested sequence is valid - contains only banner ids from list
                        if (!array_diff($suggBannersSequence, $bannerIds)) {
                            $bannersSequence = $suggBannersSequence;
                        }
                    }
                    if ($bannersSequence === null) {
                        $bannersSequence = $this->_session->getData($this->getUniqueId());
                    }

                    // Check that we have suggested banner to render
                    $suggBannerId = null;
                    if ($suggBannersSelected && count($suggBannersSelected) == 1) {
                        $suggBannerId = $suggBannersSelected[0];
                    }

                    // If some banners were shown, get the list of unshown ones and choose banner to show
                    if ($bannersSequence) {
                        $canShowIds = array_merge(array_diff($bannerIds, $bannersSequence), []);
                        if (!empty($canShowIds)) {
                            // Stil not whole serie is shown, choose the banner to show
                            if ($suggBannerId && array_search($suggBannerId, $canShowIds) !== false) {
                                $bannerId = $suggBannerId;
                            } else {
                                $showKey = $isShuffle ? array_rand($canShowIds, 1) : 0;
                                $bannerId = $canShowIds[$showKey];
                            }
                            $bannersSequence[] = $bannerId;
                        }
                    }

                    // Start new serie (either no banners has been shown at all or whole serie has been shown)
                    if (!$bannerId) {
                        if ($suggBannerId && array_search($suggBannerId, $bannerIds) !== false) {
                            $bannerId = $suggBannerId;
                        } else {
                            $bannerKey = $isShuffle ? array_rand($bannerIds, 1) : 0;
                            $bannerId = $bannerIds[$bannerKey];
                        }
                        $bannersSequence = [$bannerId];
                    }

                    $this->_session->setData($this->getUniqueId(), $bannersSequence);

                    $_content = $bannerResource->getStoreContent($bannerId, $this->_currentStoreId);
                    if (!empty($_content)) {
                        $content[$bannerId] = $_content;
                    }
                    break;

                default:
                    // We must always render all available banners - so suggested values are ignored
                    $content = $bannerResource->getBannersContent($bannerIds, $this->_currentStoreId);
                    break;
            }
        }
        return $content;
    }

    /**
     * @return string
     */
    public function getWidgetAttributes()
    {
        $attributes = [
            'data-banner-id' => $this->getUniqueId(),
            'data-types' => $this->getTypes(),
            'data-display-mode' => $this->getDisplayMode(),
            'data-ids' => $this->getBannerIds(),
            'data-rotate' => $this->getRotate(),
            'data-store-id' => $this->_currentStoreId
        ];
        $data = [];
        foreach ($attributes as $key => $value) {
            $data[] = $key . '=' . '"' . $this->escapeHtmlAttr($value) . '"';
        }

        return implode(' ', $data);
    }
}
