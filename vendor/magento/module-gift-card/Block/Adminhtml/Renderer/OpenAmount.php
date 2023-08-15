<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Block\Adminhtml\Renderer;

use Magento\Framework\Data\Form;

/**
 * HTML select element block
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class OpenAmount extends \Magento\Framework\Data\Form\Element\Select
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Checkbox
     */
    protected $_element;

    /**
     * @param \Magento\Framework\Data\Form\Element\Factory $factoryElement
     * @param \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection
     * @param \Magento\Framework\Escaper $escaper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        array $data = []
    ) {
        $this->_element = $factoryElement->create('checkbox');
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    /**
     * Set form to element
     *
     * @param Form $form
     * @return $this
     */
    public function setForm($form)
    {
        $this->_element->setForm($form);
        return parent::setForm($form);
    }

    /**
     * Return rendered field
     *
     * @return string
     */
    public function getElementHtml()
    {
        $this->_element->setId(
            $this->getHtmlId()
        )->setName(
            $this->getData('name')
        )->setChecked(
            $this->getValue()
        )->setValue(
            \Magento\GiftCard\Model\Giftcard::OPEN_AMOUNT_ENABLED
        )->setDisabled(
            $this->getReadonlyDisabled()
        );
        $hiddenField = '<input type="hidden" name="' .
            $this->getName() .
            '" value="' .
            \Magento\GiftCard\Model\Giftcard::OPEN_AMOUNT_DISABLED .
            '"/>';
        return $hiddenField . $this->_element->getElementHtml();
    }
}
