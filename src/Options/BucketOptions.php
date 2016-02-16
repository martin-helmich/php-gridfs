<?php
namespace Helmich\GridFS\Options;

class BucketOptions
{
    /** @var string */
    private $bucketName;

    /** @var int */
    private $chunkSizeBytes;

    /** @var string */
    private $filesName = 'files';

    /** @var string */
    private $chunksName = 'chunks';

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
     * @param string $filesName
     * @return self
     */
    public function withFilesName(string $filesName): BucketOptions
    {
        $clone            = clone $this;
        $clone->filesName = $filesName;
        return $clone;
    }

    /**
     * @param string $chunksName
     * @return self
     */
    public function withChunksName(string $chunksName): BucketOptions
    {
        $clone             = clone $this;
        $clone->chunksName = $chunksName;
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

    /**
     * @return string
     */
    public function filesName(): string
    {
        return $this->filesName;
    }

    /**
     * @return string
     */
    public function chunksName(): string
    {
        return $this->chunksName;
    }

}