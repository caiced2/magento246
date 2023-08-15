<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Plugin\Customer\Block\Form;

use Magento\CustomAttributeManagement\Block\Form;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Attribute;

/**
 * Class CustomerCustomAttributePlugin
 *
 * Add the attribute form code for customer custom attribute
 */
class CustomerCustomAttributePlugin
{
    /**
     * Before getAttributeHtml plugin.
     *
     * @param Form $customerForm
     * @param Attribute $attribute
     * @return void
     */
    public function beforeGetAttributeHtml(Form $customerForm, Attribute $attribute): void
    {
        $layoutName = $customerForm->getNameInLayout();
        if ($customerForm->getEntity() instanceof Customer &&
            ($layoutName === 'customer_form_user_attributes_create' ||
            $layoutName === 'customer_form_user_attributes_edit')) {
            $fieldIdFormat = '%1$s';
            $attributeId = $attribute->getFormCode() . '-' . $fieldIdFormat;
            $customerForm->setFieldIdFormat($attributeId);
            $customerForm->setFieldNameFormat($attributeId);
        }
    }
}
