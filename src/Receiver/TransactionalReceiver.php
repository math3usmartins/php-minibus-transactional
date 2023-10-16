<?php

namespace MiniBus\Transactional\Receiver;

use MiniBus\Envelope;
use MiniBus\Envelope\EnvelopeCollection;
use MiniBus\Transactional\Database\Exception\FailedToAckEnvelope;
use MiniBus\Transactional\Database\Exception\TransactionalReceiverStampNotFound;
use MiniBus\Transactional\EnvelopeRepository;
use MiniBus\Transactional\Receiver\Stamp\TransactionalReceiverStamp;
use MiniBus\Transport\Receiver;

final class TransactionalReceiver implements Receiver
{
    /**
     * @var EnvelopeRepository
     */
    private $repository;

    /**
     * @var int
     */
    private $limit;

    /**
     * @param int $limit
     */
    public function __construct(
        EnvelopeRepository $repository,
        $limit
    ) {
        $this->repository = $repository;
        $this->limit = $limit;
    }

    public function fetch()
    {
        return $this->repository->find($this->limit);
    }

    public function ack(EnvelopeCollection $envelopes)
    {
        $this->delete($envelopes);
    }

    public function reject(EnvelopeCollection $envelopes)
    {
        $this->delete($envelopes);
    }

    public function retry(EnvelopeCollection $envelopes, $retryAt)
    {
        foreach ($envelopes->items() as $envelope) {
            /** @var TransactionalReceiverStamp $transactionalReceiverStamp */
            $transactionalReceiverStamp = $envelope->stamps()->last(TransactionalReceiverStamp::NAME);

            if (!$transactionalReceiverStamp) {
                throw TransactionalReceiverStampNotFound::fromEnvelope($envelope);
            }

            $this->repository->update($transactionalReceiverStamp->id(), $envelope);
        }
    }

    private function delete(EnvelopeCollection $envelopes)
    {
        /** @var int[] $idValues */
        $idValues = $envelopes->map(function (Envelope $envelope) {
            /** @var TransactionalReceiverStamp $idStamp */
            $idStamp = $envelope->stamps()->last(TransactionalReceiverStamp::NAME);

            if (!$idStamp) {
                throw FailedToAckEnvelope::fromEnvelope($envelope);
            }

            return $idStamp->id();
        });

        $this->repository->deleteById($idValues);
    }
}
