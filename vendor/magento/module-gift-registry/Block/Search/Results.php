<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Search;

/**
 * Gift registry search results
 *
 * @api
 * @since 100.0.2
 */
class Results extends \Magento\Framework\View\Element\Template
{
    /**
     * Set search results and create html pager block
     *
     * @param mixed $results
     * @return void
     * @codeCoverageIgnore
     */
    public function setSearchResults($results)
    {
        $this->setData('search_results', $results);
        $pager = $this->getLayout()->createBlock(
            \Magento\Theme\Block\Html\Pager::class,
            'giftregistry.search.pager'
        )->setCollection(
            $results
        )->setIsOutputRequired(
            false
        );
        $this->setChild('pager', $pager);
    }

    /**
     * Return frontend registry link
     *
     * @param \Magento\GiftRegistry\Model\Entity $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getRegistryLink($item)
    {
        return $this->getUrl('*/view/index', ['id' => $item->getUrlKey()]);
    }

    /**
     * Retrieve item formated date
     *
     * @param \Magento\GiftRegistry\Model\Entity $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormattedDate($item)
    {
        if ($item->getEventDate()) {
            return $this->formatDate(
                $item->getEventDate(),
                \IntlDateFormatter::MEDIUM
            );
        }
    }
}
