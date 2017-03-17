<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Exception\NetworkException;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Stopwatch\StopwatchEvent;

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

    function it_records_event(
        Stopwatch $stopwatch,
        RequestInterface $request,
        ResponseInterface $response,
        UriInterface $uri,
        StopwatchEvent $event
    ) {
        $request->getMethod()->willReturn('GET');
        $request->getUri()->willReturn($uri);
        $uri->__toString()->willReturn('http://foo.com/bar');
        $event->getDuration()->willReturn(123);

        $stopwatch->start('GET http://foo.com/bar', 'php_http.request')->shouldBeCalled();
        $stopwatch->stop('GET http://foo.com/bar')->shouldBeCalled()->willReturn($event);
        $response->withHeader('X-Duration', 123)->shouldBeCalled();

        $next = function (RequestInterface $request) use ($response) {
            return new FulfilledPromise($response->getWrappedObject());
        };

        $this->handleRequest($request, $next, function () {});
    }

    function it_records_event_on_error(Stopwatch $stopwatch, RequestInterface $request, UriInterface $uri)
    {
        $request->getMethod()->willReturn('GET');
        $request->getUri()->willReturn($uri);
        $uri->__toString()->willReturn('http://foo.com/bar');

        $stopwatch->start('GET http://foo.com/bar', 'php_http.request')->shouldBeCalled();
        $stopwatch->stop('GET http://foo.com/bar')->shouldBeCalled();

        $next = function (RequestInterface $request) {
            return new RejectedPromise(new NetworkException('', $request));
        };

        $this->handleRequest($request, $next, function () {});
    }
}
