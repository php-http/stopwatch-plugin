<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Exception;
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

    /**
     * @param Stopwatch $stopwatch
     */
    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    protected function doHandleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $eventName = $this->getStopwatchEventName($request);
        $this->stopwatch->start($eventName, self::CATEGORY);

        return $next($request)->then(function (ResponseInterface $response) use ($eventName) {
            $this->stopwatch->stop($eventName, self::CATEGORY);

            return $response;
        }, function (Exception $exception) use ($eventName) {
            $this->stopwatch->stop($eventName, self::CATEGORY);

            throw $exception;
        });
    }

    /**
     * Generates the event name.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    private function getStopwatchEventName(RequestInterface $request)
    {
        return sprintf('%s %s', $request->getMethod(), $request->getUri()->__toString());
    }
}
