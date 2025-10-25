<?php

declare(strict_types=1);

namespace Extcode\CartPdf;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder) {
    $services = $containerConfigurator->services();
    $services->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $services->load('Extcode\\CartPdf\\', __DIR__ . '/../Classes/')
        ->exclude(__DIR__ . '/../Classes/Domain/Model/');

    // Make PdfService public
    $services->set(\Extcode\CartPdf\Service\PdfService::class)
        ->public();

    // Conditionally register event listeners based on cart_payone availability
    if (class_exists('\\Extcode\\CartPayone\\Event\\Order\\FinishEvent')) {
        // Register listeners for cart_payone events
        $services->set('cart_pdf.event_listener.document_renderer.payone')
            ->class(\Extcode\CartPdf\EventListener\Order\Finish\DocumentRenderer::class)
            ->tag('event.listener', [
                'identifier' => 'cart-pdf--order--finish--document-renderer-payone',
                'event' => \Extcode\CartPayone\Event\Order\FinishEvent::class,
                'before' => 'cart--order--finish--email',
            ]);
    } else {
        // Register listeners for standard cart events
        $services->set('cart_pdf.event_listener.document_renderer.cart')
            ->class(\Extcode\CartPdf\EventListener\Order\Finish\DocumentRenderer::class)
            ->tag('event.listener', [
                'identifier' => 'cart--order--finish--document-renderer',
                'event' => \Extcode\Cart\Event\Order\FinishEvent::class,
                'before' => 'cart--order--finish--email',
            ]);
    }
};
