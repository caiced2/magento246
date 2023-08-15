<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model\Pdf;

use Magento\Rma\Model\Item;

/**
 * Rma PDF model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Rma extends \Magento\Sales\Model\Order\Pdf\AbstractPdf
{
    /**
     * Variable to store store-depended string values of attributes
     *
     * @var null|array
     */
    protected $_attributeOptionValues = null;

    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData;

    /**
     * Rma eav
     *
     * @var \Magento\Rma\Helper\Eav
     */
    protected $_rmaEav;

    /**
     * Core store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Sales\Model\Order\Pdf\Config $pdfConfig
     * @param \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory
     * @param \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Rma\Helper\Eav $rmaEav
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sales\Model\Order\Pdf\Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Rma\Helper\Eav $rmaEav,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        array $data = []
    ) {
        $this->_rmaEav = $rmaEav;
        $this->_rmaData = $rmaData;
        $this->_storeManager = $storeManager;
        $this->_localeResolver = $localeResolver;

        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $data
        );
    }

    /**
     * Retrieve PDF
     *
     * @param array $rmaArray
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Zend_Pdf
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getPdf($rmaArray = [])
    {
        $this->_beforeGetPdf();

        $pdf = new \Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new \Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        if (!(is_array($rmaArray) && count($rmaArray) == 1)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Only one RMA is available for printing'));
        }
        $rma = $rmaArray[0];

        $storeId = $rma->getOrder()->getStore()->getId();
        if ($storeId) {
            $this->_localeResolver->emulate($storeId);
            $this->_storeManager->setCurrentStore($storeId);
        }

        $page = $this->newPage();

        /* Add image */
        $this->insertLogo($page, $storeId);

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 5);

        $page->setLineWidth(0.5);
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));

        $page->setLineWidth(0);
        /* start y-position for next block */
        $this->y = 800;

        /* Add head */
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->drawRectangle(25, $this->y - 30, 570, $this->y - 75);

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $this->_setFontRegular($page);

        $page->drawText(
            __('Return # ') . $rma->getIncrementId() . ' - ' . $rma->getStatusLabel(),
            35,
            $this->y - 40,
            'UTF-8'
        );

        $page->drawText(
            __('Return Date: ') . $this->_localeDate->formatDate(
                $rma->getDateRequested(),
                \IntlDateFormatter::MEDIUM,
                false
            ),
            35,
            $this->y - 50,
            'UTF-8'
        );

        $page->drawText(__('Order # ') . $rma->getOrder()->getIncrementId(), 35, $this->y - 60, 'UTF-8');

        $text = __('Ordered: ');
        $text .= $this->_localeDate->formatDate(
            $rma->getOrder()->getCreatedAt(),
            \IntlDateFormatter::MEDIUM,
            false
        );
        $page->drawText($text, 35, $this->y - 70, 'UTF-8');

        /* start y-position for next block */
        $this->y = $this->y - 80;

        /* add address blocks */
        $shippingAddress = $this->_formatAddress(
            $this->addressRenderer->format(
                $rma->getOrder()->getShippingAddress(),
                'pdf'
            )
        );
        $returnAddress = $this->_formatAddress($this->_rmaData->getReturnAddress('pdf', [], $this->getStoreId()));
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 290, $this->y - 15);
        $page->drawRectangle(305, $this->y, 570, $this->y - 15);

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page);
        $page->drawText(__('Shipping Address:'), 35, $this->y - 10, 'UTF-8');

        $page->drawText(__('Return Address:'), 315, $this->y - 10, 'UTF-8');

        $y = $this->y - 15 - (max(count($shippingAddress), count($returnAddress)) * 10 + 5);

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, $this->y - 15, 290, $y);
        $page->drawRectangle(305, $this->y - 15, 570, $y);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page);

        $yStartAddresses = $this->y - 25;
        foreach ($shippingAddress as $value) {
            if ($value !== '') {
                $page->drawText(strip_tags(ltrim($value)), 35, $yStartAddresses, 'UTF-8');
                $yStartAddresses -= 10;
            }
        }
        $yStartAddresses = $this->y - 25;
        foreach ($returnAddress as $value) {
            if ($value !== '') {
                $page->drawText(strip_tags(ltrim($value)), 315, $yStartAddresses, 'UTF-8');
                $yStartAddresses -= 10;
            }
        }

        /* start y-position for next block */
        $this->y = $this->y - 20 - (max(count($shippingAddress), count($returnAddress)) * 10 + 5);

        /* Add table */
        $this->_setColumnXs();
        $this->_addItemTableHead($page);

        /* Add body */

        /** @var $collection \Magento\Rma\Model\ResourceModel\Item\Collection */
        $collection = $rma->getItemsForDisplay();

        foreach ($collection as $item) {
            if ($this->y < 15) {
                $page = $this->_addNewPage();
            }

            /* Draw item */
            $this->_drawRmaItem($item, $page);
        }

        if ($storeId) {
            $this->_localeResolver->revert();
        }

        $this->_afterGetPdf();
        return $pdf;
    }

    /**
     * Create new page, assign to PDF object and repeat table head there
     *
     * @return \Zend_Pdf_Page
     */
    protected function _addNewPage()
    {
        $page = $this->_getPdf()->newPage(\Zend_Pdf_Page::SIZE_A4);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;

        $this->_addItemTableHead($page);
        return $page;
    }

    /**
     * Add items table head
     *
     * @param \Zend_Pdf_Page $page
     * @return void
     */
    protected function _addItemTableHead($page)
    {
        $this->_setFontRegular($page);
        $page->setFillColor(new \Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 15);
        $this->y -= 10;

        $page->setFillColor(new \Zend_Pdf_Color_RGB(0.4, 0.4, 0.4));
        $page->drawText(__('Product Name'), $this->getProductNameX(), $this->y, 'UTF-8');
        $page->drawText(__('SKU'), $this->getProductSkuX(), $this->y, 'UTF-8');
        $page->drawText(__('Condition'), $this->getConditionX(), $this->y, 'UTF-8');
        $page->drawText(__('Resolution'), $this->getResolutionX(), $this->y, 'UTF-8');
        $page->drawText(__('Requested Qty'), $this->getQtyRequestedX(), $this->y, 'UTF-8');
        $page->drawText(__('Qty'), $this->getQtyX(), $this->y, 'UTF-8');
        $page->drawText(__('Status'), $this->getStatusX(), $this->y, 'UTF-8');

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));

        $this->y -= 15;
    }

    /**
     * Draw one line - rma item
     *
     * @param Item $item
     * @param \Zend_Pdf_Page $page
     * @return void
     */
    protected function _drawRmaItem($item, $page)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $productName = $this->string->split(html_entity_decode($item->getProductName()), 60, true, true);
        $productName = array_key_exists(0, $productName) ? $productName[0] : '';
        $page->drawText($productName, $this->getProductNameX(), $this->y, 'UTF-8');

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $productSku = $this->string->split(html_entity_decode($item->getProductSku()), 25);
        $productSku = array_key_exists(0, $productSku) ? $productSku[0] : '';
        $page->drawText($productSku, $this->getProductSkuX(), $this->y, 'UTF-8');

        $condition = $this->string->split($this->_getOptionAttributeStringValue($item->getCondition()), 25);
        $condition = array_key_exists(0, $condition) ? $condition[0] : '';
        $page->drawText($condition, $this->getConditionX(), $this->y, 'UTF-8');

        $resolution = $this->string->split($this->_getOptionAttributeStringValue($item->getResolution()), 25);
        $resolution = array_key_exists(0, $resolution) ? $resolution[0] : '';
        $page->drawText($resolution, $this->getResolutionX(), $this->y, 'UTF-8');

        $page->drawText(
            $this->_rmaData->parseQuantity($item->getQtyRequested(), $item),
            $this->getQtyRequestedX(),
            $this->y,
            'UTF-8'
        );

        $page->drawText($this->_rmaData->getQty($item), $this->getQtyX(), $this->y, 'UTF-8');

        $status = $this->string->split($item->getStatusLabel(), 25);
        $status = array_key_exists(0, $status) ? $status[0] : '';
        $page->drawText($status, $this->getStatusX(), $this->y, 'UTF-8');

        $productOptions = $item->getOptions();
        if (is_array($productOptions) && !empty($productOptions)) {
            $this->_drawCustomOptions($productOptions, $page);
        }

        $this->y -= 10;
    }

    /**
     * Draw additional lines for item's custom options
     *
     * @param array $optionsArray
     * @param \Zend_Pdf_Page $page
     * @return void
     */
    protected function _drawCustomOptions($optionsArray, $page)
    {
        $this->_setFontRegular($page, 6);
        foreach ($optionsArray as $value) {
            $this->y -= 8;
            $optionRowString = $value['label'] . ': ' . (isset(
                $value['print_value']
            ) ? $value['print_value'] : $value['value']);
            $productOptions = $this->string->split($optionRowString, 60, true, true);
            $productOptions = array_key_exists(0, $productOptions) ? $productOptions[0] : '';
            $page->drawText($productOptions, $this->getProductNameX(), $this->y, 'UTF-8');
        }
        $this->_setFontRegular($page);
    }

    /**
     * Get string label of option-type item attributes
     *
     * @param int $attributeValue
     * @return string
     */
    protected function _getOptionAttributeStringValue($attributeValue)
    {
        if ($this->_attributeOptionValues === null) {
            $this->_attributeOptionValues = $this->_rmaEav->getAttributeOptionStringValues();
        }
        if (isset($this->_attributeOptionValues[$attributeValue])) {
            return $this->_attributeOptionValues[$attributeValue];
        } else {
            return '';
        }
    }

    /**
     * Sets X coordinates for columns
     *
     * @return void
     */
    protected function _setColumnXs()
    {
        $this->setProductNameX(35);
        $this->setProductSkuX(200);
        $this->setConditionX(280);
        $this->setResolutionX(360);
        $this->setQtyRequestedX(425);
        $this->setQtyX(490);
        $this->setStatusX(520);
    }
}
