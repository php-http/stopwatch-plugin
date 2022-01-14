<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Exception;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Measure the duration of a request call with the stopwatch component.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
final class StopwatchPlugin implements Plugin
{
    const CATEGORY = 'php_http.request';

    use VersionBridgePlugin;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * @return Promise Resolves a PSR-7 Response or fails with an Http\Client\Exception (The same as HttpAsyncClient)
     */
    protected function doHandleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $eventName = $this->getStopwatchEventName($request);
        $this->stopwatch->start($eventName, self::CATEGORY);

        return $next($request)->then(function (ResponseInterface $response) use ($eventName) {
            $this->stopwatch->stop($eventName);

            return $response;
        }, function (Exception $exception) use ($eventName) {
            $this->stopwatch->stop($eventName);

            throw $exception;
        });
    }

    /**
     * Generates the event name.
     *
     * @return string
     */
    private function getStopwatchEventName(RequestInterface $request)
    {
        return sprintf('%s %s', $request->getMethod(), $request->getUri()->__toString());
    }
}
