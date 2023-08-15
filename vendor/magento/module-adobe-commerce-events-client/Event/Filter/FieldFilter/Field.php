<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Filter\FieldFilter;

/**
 * Contains information about event fields.
 */
class Field
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var bool
     */
    private bool $isArray;

    /**
     * @var array
     */
    private array $children = [];

    /**
     * @var Field|null
     */
    private ?Field $parent;

    /**
     * @var string|null
     */
    private ?string $path = null;

    /**
     * @param string $name
     * @param Field|null $parent
     * @param bool $isArray
     */
    public function __construct(string $name, Field $parent = null, bool $isArray = false)
    {
        $this->name = $name;
        $this->parent = $parent;
        $this->isArray = $isArray;
    }

    /**
     * Adds a child Field element.
     *
     * @param Field $field
     * @return void
     */
    public function addChildren(Field $field): void
    {
        $this->children[] = $field;
    }

    /**
     * Returns array of children Field elements.
     *
     * @return Field[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Checks if the object has children Fields elements.
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    /**
     * Checks if the Field is array type.
     *
     * It means that in field expression this part is marked as array [].
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->isArray;
    }

    /**
     * Returns parent field element.
     *
     * @return Field|null
     */
    public function getParent(): ?Field
    {
        return $this->parent;
    }

    /**
     * Returns field name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns path to the current Field element.
     *
     * @return string
     */
    public function getPath(): string
    {
        if ($this->path === null) {
            $this->path = $this->getName();

            $parent = $this->getParent();
            if ($parent !== null) {
                $this->path = $parent->getPath() . '.' . $this->path;
            }
        }

        return $this->path;
    }
}
