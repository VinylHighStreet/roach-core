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

namespace RoachPHP\Http;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Psr\Http\Message\RequestInterface;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Support\Droppable;
use RoachPHP\Support\DroppableInterface;
use RoachPHP\Support\HasMetaData;

final class Request implements DroppableInterface
{
    use HasMetaData;
    use Droppable;

    public URL $url;

    /**
     * @var \Closure(Response): \Generator<ParseResult>
     */
    private \Closure $parseCallback;

    private RequestInterface $psrRequest;

    private ?Response $response = null;

    /**
     * @param callable(Response): \Generator<ParseResult> $parseMethod
     */
    public function __construct(string $method, string $uri, callable $parseMethod, /**
     * An array of Guzzle request options.
     * See https://docs.guzzlephp.org/en/stable/request-options.html.
     */
    private array $options = [])
    {
        $this->psrRequest = new GuzzleRequest($method, $uri);
        $this->parseCallback = \Closure::fromCallable($parseMethod);
        $this->url = URL::parse($uri);
    }

    public function getUri(): string
    {
        return (string) $this->psrRequest->getUri();
    }

    public function hasHeader(string $name): bool
    {
        return $this->psrRequest->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->psrRequest->getHeader($name);
    }

    public function getPath(): string
    {
        return $this->psrRequest->getUri()->getPath();
    }

    /**
     * @param list<string>|string $value
     */
    public function addHeader(string $name, mixed $value): self
    {
        /** @var GuzzleRequest $request */
        $request = $this->psrRequest->withHeader($name, $value);

        $clone = clone $this;
        $clone->psrRequest = $request;

        return $clone;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function addOption(string $option, mixed $value): self
    {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * @param \Closure(RequestInterface): RequestInterface $callback
     */
    public function withPsrRequest(\Closure $callback): self
    {
        $this->psrRequest = $callback($this->psrRequest);

        return $this;
    }

    public function callback(Response $response): \Generator
    {
        return ($this->parseCallback)($response);
    }

    public function getPsrRequest(): RequestInterface
    {
        return $this->psrRequest;
    }

    public function getParseCallback(): \Closure
    {
        return $this->parseCallback;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function withResponse(?Response $response): self
    {
        $clone = clone $this;
        $clone->response = $response;

        return $clone;
    }
}
