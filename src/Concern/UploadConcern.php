<?php
namespace Helmich\GridFS\Concern;

use Helmich\GridFS\Options\UploadOptions;
use Helmich\GridFS\Stream\UploadStream;
use Helmich\GridFS\Stream\UploadStreamInterface;
use MongoDB\BSON\ObjectID;

class UploadConcern
{
    use ConcernProperties;

    public function openUploadStream(string $filename, UploadOptions $options = null): UploadStreamInterface
    {
        $this->initIfNecessary();

        $options    = $options ?? new UploadOptions();
        $chunkSize  = $options->chunkSizeBytes() ?: $this->options->chunkSizeBytes();
        $documentId = new ObjectID();

        return new UploadStream($filename, $chunkSize, $documentId, $options, $this->files, $this->chunks);
    }

    public function uploadFromStream(string $filename, $stream, UploadOptions $options = null): ObjectID
    {
        $this->initIfNecessary();

        $options      = $options ?? new UploadOptions();
        $chunkSize    = $options->chunkSizeBytes() ?: $this->options->chunkSizeBytes();
        $targetStream = $this->openUploadStream($filename, $options);

        while ($data = fread($stream, $chunkSize)) {
            $targetStream->write($data);
        }

        $targetStream->close();
        return $targetStream->fileId();
    }

    public function rename(ObjectID $id, string $newFilename)
    {
        $this->files->updateOne(['_id' => $id], ['$set' => ['filename' => $newFilename]]);
    }

    private function initIfNecessary()
    {
        if ($this->files->count() === 0) {
            $this->files->createIndex(['filename' => 1, 'uploadDate' => 1]);
            $this->chunks->createIndex(['files_id' => 1, 'n' => 1], ['unique' => true]);
        }
    }
}
