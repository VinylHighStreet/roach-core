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

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface;

final readonly class Client implements ClientInterface
{
    public function __construct(private GuzzleClient $client = new GuzzleClient())
    {
    }

    /**
     * @param list<Request> $requests
     */
    public function pool(
        array $requests,
        ?callable $onFulfilled = null,
        ?callable $onRejected = null,
    ): void {
        $makeRequests = function () use ($requests): \Generator {
            foreach ($requests as $request) {
                yield (fn() => $this->client
                    ->sendAsync($request->getPsrRequest(), $request->getOptions())
                    ->then(
                        static fn (ResponseInterface $response) => new Response($response, $request),
                        static function (GuzzleException $reason) use ($request) {
                            // If we got back a response, we want to return a Response object
                            // so it can get sent through the middleware stack.
                            if ($reason instanceof BadResponseException) {
                                return new Response($reason->getResponse(), $request);
                            }

                            // For all other cases, we'll wrap the exception in our own
                            // exception so it can be handled by any request exception middleware.
                            throw new RequestException($request, $reason);
                        },
                    ));
            }
        };

        $pool = new Pool($this->client, $makeRequests(), [
            'concurrency' => 0,
            'fulfilled' => $onFulfilled,
            'rejected' => $onRejected,
        ]);

        $pool->promise()->wait();
    }
}
