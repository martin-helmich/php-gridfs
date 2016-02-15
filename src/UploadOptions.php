<?php
/**
 * Created by PhpStorm.
 * User: mhelmich
 * Date: 15.02.16
 * Time: 21:57
 */

namespace Helmich\GridFS;

class UploadOptions
{
    private $chunkSizeBytes;
    private $metadata;

    public function __construct(int $chunkSizeBytes = null, array $metadata = null)
    {
        $this->chunkSizeBytes = $chunkSizeBytes;
        $this->metadata       = $metadata;
    }

    /**
     * @return int
     */
    public function chunkSizeBytes()
    {
        return $this->chunkSizeBytes;
    }

    /**
     * @return array
     */
    public function metadata()
    {
        return $this->metadata;
    }

}