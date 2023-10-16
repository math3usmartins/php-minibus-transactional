<?php

namespace MiniBus\Transactional\Sender;

use MiniBus\Envelope;
use MiniBus\Transactional\EnvelopeRepository;
use MiniBus\Transactional\Sender\Stamp\TransactionalSenderStamp;
use MiniBus\Transport\Sender;

final class TransactionalSender implements Sender
{
    /**
     * @var EnvelopeRepository
     */
    private $repository;

    public function __construct(
        EnvelopeRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function send(Envelope $envelope)
    {
        $this->repository->insert($envelope);

        return $envelope->withStamp(new TransactionalSenderStamp());
    }
}
