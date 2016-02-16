<?php
namespace Helmich\GridFS\Options;

class BucketOptions
{
    /** @var string */
    private $bucketName;

    /** @var int */
    private $chunkSizeBytes;

    public function __construct(string $bucketName = 'fs', int $chunkSizeBytes = 255 << 10)
    {
        $this->bucketName     = $bucketName;
        $this->chunkSizeBytes = $chunkSizeBytes;
    }

    /**
     * @param string $bucketName
     * @return self
     */
    public function withBucketName(string $bucketName): BucketOptions
    {
        $clone             = clone $this;
        $clone->bucketName = $bucketName;
        return $clone;
    }

    /**
     * @param int $chunkSizeBytes
     * @return self
     */
    public function withChunkSizeBytes(int $chunkSizeBytes): BucketOptions
    {
        $clone                 = clone $this;
        $clone->chunkSizeBytes = $chunkSizeBytes;
        return $clone;
    }

    /**
     * @return string
     */
    public function bucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * @return int
     */
    public function chunkSizeBytes(): int
    {
        return $this->chunkSizeBytes;
    }

}