<?php
namespace Helmich\GridFS\Options;

class FindOptions
{
    /** @var int */
    private $batchSize;

    /** @var int */
    private $limit;

    /** @var int */
    private $maxTimeMs;

    /** @var bool */
    private $noCursorTimeout;

    /** @var int */
    private $skip;

    /** @var array */
    private $sort;

    public function __construct(
        int $batchSize = 0,
        int $limit = 0,
        int $maxTimeMs = 0,
        bool $noCursorTimeout = false,
        int $skip = 0,
        array $sort = []
    ) {
        $this->batchSize       = $batchSize;
        $this->limit           = $limit;
        $this->maxTimeMs       = $maxTimeMs;
        $this->noCursorTimeout = $noCursorTimeout;
        $this->skip            = $skip;
        $this->sort            = $sort;
    }

    /**
     * @param int $batchSize
     * @return self
     */
    public function withBatchSize(int $batchSize): FindOptions
    {
        $clone            = clone $this;
        $clone->batchSize = $batchSize;
        return $clone;
    }

    /**
     * @param int $limit
     * @return self
     */
    public function withLimit(int $limit): FindOptions
    {
        $clone        = clone $this;
        $clone->limit = $limit;
        return $clone;
    }

    /**
     * @param int $maxTimeMs
     * @return self
     */
    public function withMaxTimeMs(int $maxTimeMs): FindOptions
    {
        $clone            = clone $this;
        $clone->maxTimeMs = $maxTimeMs;
        return $clone;
    }

    /**
     * @return self
     */
    public function withNoCursorTimeout(): FindOptions
    {
        $clone                  = clone $this;
        $clone->noCursorTimeout = true;
        return $clone;
    }

    /**
     * @return self
     */
    public function withCursorTimeout(): FindOptions
    {
        $clone                  = clone $this;
        $clone->noCursorTimeout = false;
        return $clone;
    }

    /**
     * @param int $skip
     * @return self
     */
    public function withSkip(int $skip): FindOptions
    {
        $clone       = clone $this;
        $clone->skip = $skip;
        return $clone;
    }

    /**
     * @param array $sort
     * @return self
     */
    public function withSort(array $sort): FindOptions
    {
        $clone       = clone $this;
        $clone->sort = $sort;
        return $clone;
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * @return int
     */
    public function limit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function maxTimeMs(): int
    {
        return $this->maxTimeMs;
    }

    /**
     * @return boolean
     */
    public function noCursorTimeout(): bool
    {
        return $this->noCursorTimeout;
    }

    /**
     * @return int
     */
    public function skip(): int
    {
        return $this->skip;
    }

    /**
     * @return array
     */
    public function sort(): array
    {
        return $this->sort;
    }


}