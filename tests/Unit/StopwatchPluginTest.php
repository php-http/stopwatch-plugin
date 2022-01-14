<?php

declare(strict_types=1);

namespace Http\Client\Common\Plugin\Tests\Unit;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Common\Plugin\StopwatchPlugin;
use Http\Client\Exception\HttpException;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class StopwatchPluginTest extends TestCase
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var StopwatchPlugin
     */
    private $plugin;

    public function setUp(): void
    {
        $this->stopwatch = new Stopwatch();
        $this->plugin = new StopwatchPlugin($this->stopwatch);
    }

    /**
     * @test
     */
    public function it_records_event(): void
    {
        // Arrange
        $request = new Request('GET', 'https://localhost');
        $response = new Response();

        $next = function (RequestInterface $request) use ($response) {
            return new FulfilledPromise($response);
        };
        $first = function (RequestInterface $request) {
            throw new LogicException('Should not be called');
        };

        // Act
        $this->plugin->handleRequest($request, $next, $first);

        // Assert
        $this->assertCount(1, $this->stopwatch->getSections());
        $this->assertFalse($this->stopwatch->getSectionEvents('__root__')['GET https://localhost']->isStarted());
    }

    /**
     * @test
     */
    public function it_records_event_on_error(): void
    {
        // Arrange
        $request = new Request('GET', 'https://localhost');
        $response = new Response();

        $next = function (RequestInterface $request) use ($response) {
            return new RejectedPromise(new HttpException('', $request, $response));
        };
        $first = function (RequestInterface $request) {
            throw new LogicException('Should not be called');
        };

        // Act
        $this->plugin->handleRequest($request, $next, $first);

        // Assert
        $this->assertCount(1, $this->stopwatch->getSections());
        $this->assertFalse($this->stopwatch->getSectionEvents('__root__')['GET https://localhost']->isStarted());
    }
}
