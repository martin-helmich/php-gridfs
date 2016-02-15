<?php
namespace Helmich\GridFS;

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
     * @return string
     */
    public function bucketName()
    {
        return $this->bucketName;
    }

    /**
     * @return int
     */
    public function chunkSizeBytes()
    {
        return $this->chunkSizeBytes;
    }

}