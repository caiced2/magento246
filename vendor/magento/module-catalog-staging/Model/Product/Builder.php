<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product;

use Magento\Catalog\Model\Product\Link\Resolver as LinkResolver;
use Magento\Catalog\Model\Product\Link\Converter as LinkConverter;
use Magento\Catalog\Model\ProductLink\Repository as LinkRepository;
use Magento\Staging\Model\Entity\BuilderInterface;

class Builder implements BuilderInterface
{
    /**
     * @var LinkRepository
     */
    private $linkRepository;

    /**
     * @var LinkResolver
     */
    private $linkResolver;

    /**
     * @var LinkConverter
     */
    private $linkConverter;

    /**
     * @param LinkResolver $linkResolver
     * @param LinkConverter $linkConverter
     * @param LinkRepository $linkRepository
     */
    public function __construct(
        LinkResolver $linkResolver,
        LinkConverter $linkConverter,
        LinkRepository $linkRepository
    ) {
        $this->linkConverter = $linkConverter;
        $this->linkResolver = $linkResolver;
        $this->linkRepository = $linkRepository;
    }

    /**
     * Build entity copy by prototype
     *
     * @param object $prototype
     * @return object
     */
    public function build($prototype)
    {
        $entityToSave = clone $prototype;
        $groupedLinkData = $this->linkConverter->convertLinksToGroupedArray($prototype);
        $associatedProduct = $this->linkRepository->getList($prototype);
        $entityToSave->setProductLinks($associatedProduct);
        $this->linkResolver->override($groupedLinkData);
        return $entityToSave;
    }
}
