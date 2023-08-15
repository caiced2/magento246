<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Design;

use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection;
use Magento\Support\Model\Report\Group\AbstractSection;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;

/**
 * Abstract section for all Design Report sections
 */
abstract class AbstractDesignSection extends AbstractSection
{
    const AREA = 'frontend';

    /**
     * @var \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory
     */
    protected $themeCollectionFactory;

    /**
     * @param LoggerInterface $logger
     * @param \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory $themeCollectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ThemeCollectionFactory $themeCollectionFactory
    ) {
        parent::__construct($logger);
        $this->themeCollectionFactory = $themeCollectionFactory;
    }

    /**
     * Get design theme collection model populated with data
     *
     * @param array $filters
     * @return \Magento\Theme\Model\ResourceModel\Theme\Collection
     */
    protected function getThemeCollection(array $filters = [])
    {
        /** @var \Magento\Theme\Model\ResourceModel\Theme\Collection $themeCollection */
        $themeCollection = $this->themeCollectionFactory->create();
        foreach ($filters as $field => $condition) {
            $themeCollection->addFieldToFilter($field, $condition);
        }
        $themeCollection->setOrder('theme_path', Collection::SORT_ORDER_ASC);
        $themeCollection->load();

        return $themeCollection;
    }

    /**
     * Generate Section Data
     *
     * @param \Magento\Theme\Model\ResourceModel\Theme\Collection $themeCollection
     * @return array
     */
    protected function generateSectionData(ThemeCollection $themeCollection)
    {
        $data = [];
        $items = $themeCollection->getItems();
        /** @var \Magento\Theme\Model\Theme $item */
        foreach ($items as $item) {
            $parent = null;
            if ($item->getParentTheme()) {
                $parentThemePath = $item->getParentTheme()->getThemePath();
                $parts = explode('/', $parentThemePath);
                $parent = $parts[1];
            }
            $themePath = $item->getThemePath();
            $parts = explode('/', $themePath);
            $package = $parts[0];
            $theme = $parts[1];
            $data[$package][$theme] = $parent;
        }
        $result = [];
        foreach ($data as $package => $themes) {
            $result[] = [$package, 'package'];
            foreach ($themes as $theme => $parent) {
                $parent = $parent ?: '';
                $result[] = ['    ' . $theme, 'theme', $parent];
            }
        }
        return $result;
    }

    /**
     * Generate Themes list report
     *
     * @param \Magento\Framework\Phrase $reportTitle
     * @return array
     */
    protected function generateReport(\Magento\Framework\Phrase $reportTitle)
    {
        $themeCollection = $this->getThemeCollection(['area' => static::AREA]);

        return [
            (string)$reportTitle => [
                'headers' => [__('Name'), __('Type'), __('Parent')],
                'data' => $this->generateSectionData($themeCollection)
            ]
        ];
    }
}
