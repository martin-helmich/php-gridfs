<?php
namespace Helmich\GridFS\Options;

class DownloadByNameOptions
{
    /** @var int */
    private $revision;

    public function __construct(int $revision = -1)
    {
        $this->revision = $revision;
    }

    /**
     * @param int $revision
     * @return self
     */
    public function withRevision(int $revision): DownloadByNameOptions
    {
        $clone           = clone $this;
        $clone->revision = $revision;
        return $clone;
    }

    /**
     * @return int
     */
    public function revision(): int
    {
        return $this->revision;
    }

}