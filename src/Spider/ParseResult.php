<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Spider;

use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\ItemPipeline\ItemInterface;

final readonly class ParseResult
{
    private function __construct(private ItemInterface|Request $value)
    {
    }

    public static function fromValue(ItemInterface|Request $value): self
    {
        return new self($value);
    }

    public static function item(array $item): self
    {
        return new self(new Item($item));
    }

    public function value(): ItemInterface|Request
    {
        return $this->value;
    }

    /**
     * @param callable(Response): \Generator<ParseResult> $parseCallback
     */
    public static function request(
        string $method,
        string $url,
        callable $parseCallback,
        array $options = [],
    ): self {
        return new self(new Request($method, $url, $parseCallback, $options));
    }

    /**
     * @param \Closure(Request): void       $ifRequest
     * @param \Closure(ItemInterface): void $ifItem
     */
    public function apply(\Closure $ifRequest, \Closure $ifItem): void
    {
        if ($this->value instanceof Request) {
            $ifRequest($this->value);
        } else {
            $ifItem($this->value);
        }
    }
}
