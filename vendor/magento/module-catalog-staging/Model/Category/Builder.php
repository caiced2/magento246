<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Category;

use Magento\Staging\Model\Entity\Builder\DefaultBuilder;
use Magento\Staging\Model\Entity\BuilderInterface;

class Builder implements BuilderInterface
{
    /**
     * @var DefaultBuilder
     */
    private $defaultBuilder;

    /**
     * @param DefaultBuilder $builder
     */
    public function __construct(DefaultBuilder $builder)
    {
        $this->defaultBuilder = $builder;
    }

    /**
     * @param object $prototype
     * @return object
     */
    public function build($prototype)
    {
        $newProto = $this->defaultBuilder->build($prototype);
        $newProto->isObjectNew(true);
        $newProto->setRowId(null);
        return $newProto;
    }
}
