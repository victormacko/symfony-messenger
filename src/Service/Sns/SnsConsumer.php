<?php declare(strict_types=1);

namespace Bref\Symfony\Messenger\Service\Sns;

use Bref\Context\Context;
use Bref\Event\Sns\SnsEvent;
use Bref\Event\Sns\SnsHandler;
use Bref\Symfony\Messenger\Service\BusDriver;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class SnsConsumer extends SnsHandler
{
    /** @var MessageBusInterface */
    private $bus;
    /** @var SerializerInterface */
    protected $serializer;
    /** @var SnsTransportNameResolver */
    private $transportNameResolver;
    /** @var BusDriver */
    private $busDriver;
    /** @var string|null */
    private $transportName;

    public function __construct(
        BusDriver $busDriver,
        MessageBusInterface $bus,
        SerializerInterface $serializer,
        SnsTransportNameResolver $transportNameResolver,
        string $transportName = null
    ) {
        $this->busDriver = $busDriver;
        $this->bus = $bus;
        $this->serializer = $serializer;
        $this->transportName = $transportName;
        $this->transportNameResolver = $transportNameResolver;
    }

    public function handleSns(SnsEvent $event, Context $context): void
    {
        foreach ($event->getRecords() as $record) {
            $attributes = $record->getMessageAttributes();
            $headers = isset($attributes['Headers']) ? $attributes['Headers']->getValue() : '[]';
            $envelope = $this->serializer->decode(['body' => $record->getMessage(), 'headers' => json_decode($headers, true)]);

            $this->busDriver->putEnvelopeOnBus($this->bus, $envelope, $this->transportName ?? ($this->transportNameResolver)($record));
        }
    }
}
