<?php

declare(strict_types=1);

namespace ro0NL\HttpResponder;

use ro0NL\HttpResponder\Respond\NoContent;
use ro0NL\HttpResponder\Respond\Raw;
use ro0NL\HttpResponder\Respond\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Roland Franssen <franssen.roland@gmail.com>
 */
final class DefaultResponder extends AggregatedResponder
{
    protected function getAggregates(): iterable
    {
        yield Raw::class => function (Raw $respond): Response {
            return new Response($respond->contents, $respond->status, $respond->headers);
        };

        yield Redirect::class => function (Redirect $respond): Response {
            return new RedirectResponse($respond->url, $respond->status, $respond->headers);
        };

        yield NoContent::class => function (NoContent $respond): Response {
            return new Response('', $respond->status, $respond->headers);
        };
    }
}
