<?php
namespace Helmich\GridFS\Concern;

use Helmich\GridFS\BucketInterface;
use Helmich\GridFS\Options\BucketOptions;
use MongoDB\Collection;

trait ConcernProperties
{

    /** @var Collection */
    private $files;

    /** @var Collection */
    private $chunks;

    /** @var BucketOptions */
    private $options;

    /** @var BucketInterface */
    private $bucket;

    public function __construct(BucketInterface $bucket, Collection $files, Collection $chunks, BucketOptions $options)
    {
        $this->files   = $files;
        $this->chunks  = $chunks;
        $this->options = $options;
        $this->bucket  = $bucket;
    }
}
