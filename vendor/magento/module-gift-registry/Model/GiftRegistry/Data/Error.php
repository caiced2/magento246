<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\GiftRegistry\Data;

/**
 * DTO represents error item
 */
class Error
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $code;

    /**
     * @var int
     */
    private $productId;

    /**
     * @param string $message
     * @param int $productId
     * @param string $code
     */
    public function __construct(string $message, int $productId, string $code)
    {
        $this->message = $message;
        $this->productId = $productId;
        $this->code = $code;
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get error code
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get product id
     *
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }
}
