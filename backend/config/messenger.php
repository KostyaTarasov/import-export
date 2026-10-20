<?php

declare(strict_types=1);

use App\Message\ImportProductsMessage;
use App\MessageHandler\ImportFailureListener;
use App\MessageHandler\ImportProductsHandler;
use App\Messenger\ServiceMap;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransportFactory;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\TransportFactory;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function DI\autowire;
use function DI\get;

return [
    TransportInterface::class => static fn(ContainerInterface $container): TransportInterface => new TransportFactory([
        new AmqpTransportFactory(),
        new InMemoryTransportFactory(),
    ])->createTransport($container->get('messenger.dsn'), [], new PhpSerializer()),

    MessageBusInterface::class => static fn(ContainerInterface $container): MessageBusInterface => new MessageBus([
        new SendMessageMiddleware(new SendersLocator(
            [ImportProductsMessage::class => ['async']],
            new ServiceMap(['async' => $container->get(TransportInterface::class)]),
        )),
        new HandleMessageMiddleware(new HandlersLocator([
            ImportProductsMessage::class => [
                static fn(ImportProductsMessage $message) => $container->get(ImportProductsHandler::class)($message),
            ],
        ])),
    ]),

    EventDispatcher::class => static function (ContainerInterface $container): EventDispatcher {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new SendFailedMessageForRetryListener(
            new ServiceMap(['async' => $container->get(TransportInterface::class)]),
            new ServiceMap(['async' => new MultiplierRetryStrategy(maxRetries: 3, delayMilliseconds: 5000, multiplier: 2)]),
        ));
        $dispatcher->addSubscriber($container->get(ImportFailureListener::class));

        return $dispatcher;
    },

    ImportProductsHandler::class => autowire()->constructorParameter('storagePath', get('path.storage')),
];
