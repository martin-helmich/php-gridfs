<?php
namespace Helmich\GridFS\Options;

class UploadOptions
{
    private $chunkSizeBytes;
    private $metadata;

    public function __construct(int $chunkSizeBytes = 0, array $metadata = [])
    {
        $this->chunkSizeBytes = $chunkSizeBytes;
        $this->metadata       = $metadata;
    }

    /**
     * @param int $chunkSizeBytes
     * @return self
     */
    public function withChunkSizeBytes(int $chunkSizeBytes): UploadOptions
    {
        $clone                 = clone $this;
        $clone->chunkSizeBytes = $chunkSizeBytes;
        return $clone;
    }

    /**
     * @param array $metadata
     * @return self
     */
    public function withMetadata(array $metadata): UploadOptions
    {
        $clone           = clone $this;
        $clone->metadata = $metadata;
        return $clone;
    }

    /**
     * @return int
     */
    public function chunkSizeBytes(): int
    {
        return $this->chunkSizeBytes;
    }

    /**
     * @return array
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
