<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Quote;

use Magento\CustomerCustomAttributes\Model\Sales\QuoteFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Persist relation between quote and user defined customer custom attributes.
 */
class Relation implements RelationInterface
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        QuoteFactory $quoteFactory
    ) {
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function processRelation(AbstractModel $object)
    {
        if ($object instanceof CartInterface) {
            $customAttributes = $object->getCustomer()->getCustomAttributes();
            if (!empty($customAttributes)) {
                /** @var $quoteModel \Magento\CustomerCustomAttributes\Model\Sales\Quote */
                $quoteModel = $this->quoteFactory->create();
                $quoteModel->saveAttributeData($object);
            }
        }
    }
}
