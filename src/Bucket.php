<?php
namespace Helmich\GridFS;

use Helmich\GridFS\Stream\DownloadStream;
use Helmich\GridFS\Stream\DownloadStreamInterface;
use Helmich\GridFS\Stream\UploadStream;
use Helmich\GridFS\Stream\UploadStreamInterface;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use MongoDB\Database;

class Bucket
{

    /** @var Database */
    private $database;

    /** @var BucketOptions */
    private $options;

    /** @var Collection */
    private $chunks;

    /** @var Collection */
    private $files;

    public function __construct(Database $database, BucketOptions $options)
    {
        $this->database = $database;
        $this->options  = $options;

        $this->files  = $database->selectCollection($options->bucketName() . '.files');
        $this->chunks = $database->selectCollection($options->bucketName() . '.chunks');
    }

    public function openUploadStream(string $filename, UploadOptions $options): UploadStreamInterface
    {
        $this->initIfNecessary();

        $chunkSize  = $options->chunkSizeBytes() ?? $this->options->chunkSizeBytes();
        $documentId = new ObjectID();

        return new UploadStream($filename, $chunkSize, $documentId, $options, $this->files, $this->chunks);
    }

    public function uploadFromStream(string $filename, $stream, UploadOptions $options): ObjectID
    {
        $this->initIfNecessary();

        $chunkSize    = $options->chunkSizeBytes() ?? $this->options->chunkSizeBytes();
        $targetStream = $this->openUploadStream($filename, $options);

        while ($data = fread($stream, $chunkSize)) {
            $targetStream->write($data);
        }

        $targetStream->close();
        return $targetStream->fileId();
    }

    public function openDownloadStream(ObjectID $objectID): DownloadStreamInterface
    {
        return new DownloadStream($objectID, $this->files, $this->chunks);
    }

    public function downloadToStream(ObjectID $objectID, $stream)
    {
        $downloadStream = $this->openDownloadStream($objectID);
        while(!$downloadStream->eof()) {
            $next = $downloadStream->read(4096);
            fwrite($stream, $next);
        }
    }

    public function delete(ObjectID $objectID)
    {
        $this->files->deleteMany(['_id' => $objectID]);
        $this->chunks->deleteMany(['files_id' => $objectID]);
    }

    private function initIfNecessary()
    {
        if ($this->files->count() === 0) {
            $this->files->createIndex(['filename' => 1, 'uploadDate' => 1]);
            $this->chunks->createIndex(['files_id' => 1, 'n' => 1], ['unique' => true]);
        }
    }

}