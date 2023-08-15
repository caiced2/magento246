<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\TypeResolver;

/**
 * Class Builder
 */
class Builder
{
    /**
     * Building strategies per entity type
     *
     * @var array
     */
    private $strategies;

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * Initialize dependencies.
     *
     * @param TypeResolver $typeResolver
     * @param array $strategies
     */
    public function __construct(
        TypeResolver $typeResolver,
        $strategies
    ) {
        $this->strategies = $strategies;
        $this->typeResolver = $typeResolver;
    }

    /**
     * Build entity by prototype according to its type
     *
     * @param object $prototype
     * @return object
     * @throws \Exception
     * @codeCoverageIgnore
     */
    public function build($prototype)
    {
        $entityType = $this->typeResolver->resolve($prototype);

        $builderClass = array_key_exists($entityType, $this->strategies)
            ? $this->strategies[$entityType]
            : $this->strategies['default'];
        /** @var \Magento\Staging\Model\Entity\BuilderInterface $builder */
        $builder = ObjectManager::getInstance()->get($builderClass);
        return $builder->build($prototype);
    }
}
