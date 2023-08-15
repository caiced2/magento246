<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BannerPageBuilder\Model\Stage\Renderer;

use Magento\PageBuilder\Model\Stage\Renderer\WidgetDirective;

/**
 * Renders a CMS Block for the stage
 *
 * @api
 */
class DynamicBlock implements \Magento\PageBuilder\Model\Stage\RendererInterface
{
    /**
     * @var \Magento\Banner\Model\ResourceModel\Banner\Collection
     */
    private $dynamicBlockCollectionFactory;

    /**
     * @var WidgetDirective
     */
    private $widgetDirectiveRenderer;

    /**
     * @var \Magento\PageBuilder\Model\Stage\HtmlFilter
     */
    private $htmlFilter;

    /**
     * @var \Magento\BannerPageBuilder\Model\ResourceModel\DynamicBlock\Content
     */
    private $dynamicBlockResource;

    /**
     * @var DynamicBlock\PlaceholderFilter
     */
    private $placeholderFilter;

    /**
     * DynamicBlock constructor.
     *
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $blockCollectionFactory
     * @param WidgetDirective $widgetDirectiveRenderer
     * @param \Magento\PageBuilder\Model\Stage\HtmlFilter $htmlFilter
     * @param \Magento\BannerPageBuilder\Model\ResourceModel\DynamicBlock\Content $dynamicBlockContent
     * @param DynamicBlock\PlaceholderFilter $placeholderFilter
     */
    public function __construct(
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $blockCollectionFactory,
        WidgetDirective $widgetDirectiveRenderer,
        \Magento\PageBuilder\Model\Stage\HtmlFilter $htmlFilter,
        \Magento\BannerPageBuilder\Model\ResourceModel\DynamicBlock\Content $dynamicBlockContent,
        \Magento\BannerPageBuilder\Model\Stage\Renderer\DynamicBlock\PlaceholderFilter $placeholderFilter
    ) {
        $this->dynamicBlockCollectionFactory = $blockCollectionFactory;
        $this->widgetDirectiveRenderer = $widgetDirectiveRenderer;
        $this->htmlFilter = $htmlFilter;
        $this->dynamicBlockResource = $dynamicBlockContent;
        $this->placeholderFilter = $placeholderFilter;
    }

    /**
     * Render a state object for the specified block for the stage preview
     *
     * @param array $params
     * @return array
     */
    public function render(array $params): array
    {
        $result = [
            'title' => null,
            'content' => null,
            'error' => null
        ];

        // Short-circuit if needed fields aren't present
        if (empty($params['directive']) && empty($params['block_id'])) {
            return $result;
        }

        $blocks = $this->dynamicBlockCollectionFactory->create()
            ->addFieldToSelect(['name', 'is_enabled'])
            ->addFieldToFilter('banner_id', ['eq' => $params['block_id']])
            ->load();

        if ($blocks->count() === 0) {
            $result['error'] = sprintf(
                __('Dynamic Block with ID: %s doesn\'t exist')->render(),
                $params['block_id']
            );

            return $result;
        }

        /**
         * @var \Magento\Banner\Model\Banner $block
         */
        $block = $blocks->getFirstItem();
        $result['name'] = $block->getName();
        if ($block->getIsEnabled()) {
            $content = $this->dynamicBlockResource->getById((int)$params['block_id']);
            $params['directive'] = $content;
            $directiveResult = $this->widgetDirectiveRenderer->render($params);
            $result['content'] = $this->placeholderFilter->addPlaceholders(
                $this->htmlFilter->filterHtml($directiveResult['content'])
            );
        } else {
            $result['error'] = __('Dynamic Block disabled');
        }

        return $result;
    }
}
