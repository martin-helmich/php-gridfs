<?php
namespace Helmich\GridFS\Concern;

use Helmich\GridFS\Options\FindOptions;

class SearchConcern
{
    use ConcernProperties;

    public function find(array $filter, FindOptions $options = null): \Traversable
    {
        $options     = $options ?? new FindOptions();
        $optionArray = [];

        if ($options->batchSize()) {
            $optionArray['batchSize'] = $options->batchSize();
        }

        if ($options->limit()) {
            $optionArray['limit'] = $options->limit();
        }

        if ($options->maxTimeMs()) {
            $optionArray['maxTimeMS'] = $options->maxTimeMs();
        }

        if ($options->noCursorTimeout()) {
            $optionArray['noCursorTimeout'] = $options->noCursorTimeout();
        }

        if ($options->skip()) {
            $optionArray['skip'] = $options->skip();
        }

        if ($options->sort()) {
            $optionArray['sort'] = $options->sort();
        }

        $files = $this->files->find($filter, $optionArray);
        return $files;
    }
}