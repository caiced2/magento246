<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model;

use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Phrase;

/**
 * Class EntityStaging
 */
class EntityStaging
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var array
     */
    private $stagingServices;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * EntityStaging constructor.
     *
     * @param ObjectManager $objectManager
     * @param TypeResolver $typeResolver
     * @param array $stagingServices
     */
    public function __construct(
        ObjectManager $objectManager,
        TypeResolver $typeResolver,
        $stagingServices = []
    ) {
        $this->typeResolver = $typeResolver;
        $this->stagingServices = $stagingServices;
        $this->objectManager = $objectManager;
    }

    /**
     * @param object $entity
     * @param string $version
     * @param array $arguments
     * @return bool
     * @throws ConfigurationMismatchException
     * @throws \Exception
     */
    public function schedule($entity, $version, $arguments = [])
    {
        $type = $this->typeResolver->resolve($entity);
        if (!isset($this->stagingServices[$type])) {
            throw new ConfigurationMismatchException(
                new Phrase(
                    "The type that was requested doesn't have a corresponding implementation. "
                    . "Verity the type and try again."
                )
            );
        }
        $staging = $this->objectManager->get($this->stagingServices[$type]);
        return $staging->schedule($entity, $version, $arguments);
    }

    /**
     * @param object $entity
     * @param string $version
     * @return bool
     * @throws ConfigurationMismatchException
     * @throws \Exception
     */
    public function unschedule($entity, $version)
    {
        $type = $this->typeResolver->resolve($entity);
        if (!isset($this->stagingServices[$type])) {
            throw new ConfigurationMismatchException(
                new Phrase(
                    "The type that was requested doesn't have a corresponding implementation. "
                    . "Verity the type and try again."
                )
            );
        }
        $staging = $this->objectManager->get($this->stagingServices[$type]);
        return $staging->unschedule($entity, $version);
    }
}
