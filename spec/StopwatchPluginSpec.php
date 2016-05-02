<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Exception\NetworkException;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use PhpSpec\ObjectBehavior;

class StopwatchPluginSpec extends ObjectBehavior
{
    function let(Stopwatch $stopwatch)
    {
        $this->beConstructedWith($stopwatch);
    }

    function it_is_initializable(Stopwatch $stopwatch)
    {
        $this->shouldHaveType('Http\Client\Common\Plugin\StopwatchPlugin');
    }

    function it_is_a_plugin()
    {
        $this->shouldImplement('Http\Client\Common\Plugin');
    }

    function it_records_event(Stopwatch $stopwatch, RequestInterface $request, ResponseInterface $response)
    {
        $request->getMethod()->willReturn('GET');
        $request->getRequestTarget()->willReturn('/');

        $stopwatch->start('GET /', 'php_http.request')->shouldBeCalled();
        $stopwatch->stop('GET /', 'php_http.request')->shouldBeCalled();

        $next = function (RequestInterface $request) use ($response) {
            return new FulfilledPromise($response->getWrappedObject());
        };

        $this->handleRequest($request, $next, function () {});
    }

    function it_records_event_on_error(Stopwatch $stopwatch, RequestInterface $request)
    {
        $request->getMethod()->willReturn('GET');
        $request->getRequestTarget()->willReturn('/');

        $stopwatch->start('GET /', 'php_http.request')->shouldBeCalled();
        $stopwatch->stop('GET /', 'php_http.request')->shouldBeCalled();

        $next = function (RequestInterface $request) {
            return new RejectedPromise(new NetworkException('', $request));
        };

        $this->handleRequest($request, $next, function () {});
    }
}
