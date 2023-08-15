<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BannerPageBuilder\Model\Stage\Renderer\DynamicBlock;

use Psr\Log\LoggerInterface;

/**
 * Replaces instances of dynamic blocks with placeholders
 * @api
 */
class PlaceholderFilter
{
    /**
     * @var \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory
     */
    private $blockCollectionFactory;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection
     */
    private $customerSegmentCollection;

    /**
     * @var \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink
     */
    private $bannerSegmentLink;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $blockCollectionFactory
     * @param LoggerInterface $logger
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection $customerSegmentCollection
     * @param \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink $bannerSegmentLink
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $blockCollectionFactory,
        LoggerInterface $logger,
        \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection $customerSegmentCollection,
        \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink $bannerSegmentLink,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->blockCollectionFactory = $blockCollectionFactory;
        $this->customerSegmentCollection = $customerSegmentCollection;
        $this->bannerSegmentLink = $bannerSegmentLink;
        $this->escaper = $escaper;
        $this->logger = $logger;
    }

    /**
     * Replaces instances of dynamic blocks with placeholders
     *
     * @param string $content
     * @return string
     */
    public function addPlaceholders(string $content): string
    {
        $dom = new \DOMDocument();
        try {
            //this code is required because of https://bugs.php.net/bug.php?id=60021
            $previous = libxml_use_internal_errors(true);
            $content = '<div>' . $content . '</div>';
            $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
        // @phpstan-ignore-next-line
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $dynamicBlocks = $xpath->query('//*[@data-content-type="dynamic_block"]');

        foreach ($dynamicBlocks as $element) {
            $bannerId = $this->extractBannerId($dom, $element);
            $placeholder = $this->generatePlaceholder($dom, $bannerId);
            $element->parentNode->replaceChild($placeholder, $element);
        }

        return substr(trim($dom->saveHTML()), 5, -6);
    }

    /**
     * Attempts to extract the banner ID from the rendered content
     *
     * @param \DOMDocument $dom
     * @param \DOMElement $element
     * @return int|null
     */
    private function extractBannerId(\DOMDocument $dom, \DOMElement $element): ?int
    {
        // check for widget directive first
        preg_match('/banner_ids="(?P<bannerId>.*?)"/', $element->nodeValue, $matches);

        if (!empty($matches)) {
            return $matches['bannerId'];
        } else {
            $xpath = new \DOMXPath($dom);
            // Directive was already rendered so attempt pull the id from the rendered data
            $matchedElements = $xpath->query('//*[@data-ids]');

            if (count($matchedElements) > 0) {
                return (int)$matchedElements->item(0)->getAttribute('data-ids');
            }
        }

        return null;
    }

    /**
     * Creates a placeholder based on the provided id
     *
     * @param \DOMDocument $dom
     * @param int $dynamicBlockId
     * @return \DOMElement
     */
    private function generatePlaceholder(\DOMDocument $dom, ?int $dynamicBlockId): \DOMElement
    {
        $placeholder = $dom->createElement('div');
        $classAttribute = $dom->createAttribute('class');
        $classAttribute->value = 'dynamic-block-placeholder';
        $placeholder->appendChild($classAttribute);

        if (!empty($dynamicBlockId)) {
            $blocks = $this->blockCollectionFactory->create()
                ->addFieldToSelect(['name'])
                ->addFieldToFilter('banner_id', ['eq' => $dynamicBlockId])
                ->load();

            if ($blocks->count() === 0) {
                $placeholder->nodeValue = sprintf(
                    __('Dynamic Block with ID: %s doesn\'t exist')->render(),
                    $dynamicBlockId
                );
            } else {
                $block = $blocks->getFirstItem();
                // Use the real block name as the placeholder
                $placeholder->nodeValue = $this->escaper->escapeHtml($block->getName());
                $placeholder->appendChild($this->generateSegmentElement($dom, $dynamicBlockId));
            }
        } else {
            // If the banner ID could not be extracted, fallback to a generic message
            $placeholder->nodeValue = __('Dynamic block cannot be displayed');
        }

        return $placeholder;
    }

    /**
     * Creates an element to display the segments used by the specified id
     *
     * @param \DOMDocument $dom
     * @param int|null $dynamicBlockId
     * @return \DOMElement|null
     */
    private function generateSegmentElement(\DOMDocument $dom, ?int $dynamicBlockId): ?\DOMElement
    {
        if (empty($dynamicBlockId)) {
            return null;
        }

        $segmentIds = $this->bannerSegmentLink->loadBannerSegments($dynamicBlockId);

        if (!empty($segmentIds)) {
            $segments = $this->customerSegmentCollection
                ->addFieldToSelect(['name'])
                ->addFieldToFilter('segment_id', ['in' => $segmentIds])
                ->load();

            $segmentNames = [];

            foreach ($segments as $segment) {
                $segmentNames[] = $segment->getName();
            }

            $segmentMessage = implode(', ', $segmentNames);
        } else {
            $segmentMessage = __('All Segments');
        }

        $segmentElement = $dom->createElement('div', (string)$segmentMessage);
        $classAttribute = $dom->createAttribute('class');
        $classAttribute->value = 'segment-name';
        $segmentElement->appendChild($classAttribute);

        return $segmentElement;
    }
}
