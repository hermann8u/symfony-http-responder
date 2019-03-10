<?php

declare(strict_types=1);

use ro0NL\HttpResponder\ChainResponder;
use ro0NL\HttpResponder\OuterResponder;
use ro0NL\HttpResponder\Responder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged;

return function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->private()
            ->autowire()

        // main responder
        ->set('http_responder', OuterResponder::class)
            ->arg('$responder', inline(ChainResponder::class)
                ->arg('$responders', tagged('http_responder')))
        ->alias(Responder::class, 'http_responder')
    ;
};
