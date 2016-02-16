<?php
namespace Helmich\GridFS;

use Helmich\GridFS\Exception\FileNotFoundException;
use Helmich\GridFS\Options\BucketOptions;
use Helmich\GridFS\Options\DownloadByNameOptions;
use Helmich\GridFS\Options\FindOptions;
use Helmich\GridFS\Options\UploadOptions;
use Helmich\GridFS\Stream\DownloadStream;
use Helmich\GridFS\Stream\DownloadStreamInterface;
use Helmich\GridFS\Stream\UploadStream;
use Helmich\GridFS\Stream\UploadStreamInterface;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use MongoDB\Database;

class Bucket implements BucketInterface
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

    public function openUploadStream(string $filename, UploadOptions $options = null): UploadStreamInterface
    {
        $this->initIfNecessary();

        $options    = $options ?? new UploadOptions();
        $chunkSize  = $options->chunkSizeBytes() ?? $this->options->chunkSizeBytes();
        $documentId = new ObjectID();

        return new UploadStream($filename, $chunkSize, $documentId, $options, $this->files, $this->chunks);
    }

    public function uploadFromStream(string $filename, $stream, UploadOptions $options = null): ObjectID
    {
        $this->initIfNecessary();

        $options      = $options ?? new UploadOptions();
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
        while (!$downloadStream->eof()) {
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

    public function openDownloadStreamByName(string $filename, DownloadByNameOptions $options = null): DownloadStreamInterface
    {
        $findOptions = (new FindOptions())->withSort(['uploadDate' => 1]);

        if ($options->revision() >= 0) {
            $findOptions = $findOptions
                ->withSkip($options->revision())
                ->withLimit(1);
        } else {
            $findOptions = $findOptions
                ->withSort(['uploadDate' => -1])
                ->withSkip(abs($options->revision() + 1))
                ->withLimit(1);
        }

        $files = $this->find(['filename' => $filename], $findOptions);
        foreach ($files as $file) {
            return $this->openDownloadStream($file['_id']);
        }

        throw new FileNotFoundException($filename);
    }

    public function downloadToStreamByName(string $filename, $stream, DownloadByNameOptions $options = null)
    {
        $downloadStream = $this->openDownloadStreamByName($filename, $options);
        while (!$downloadStream->eof()) {
            $next = $downloadStream->read(4096);
            fwrite($stream, $next);
        }
    }

    public function rename(ObjectID $id, string $newFilename)
    {
        $this->files->updateOne(['_id' => $id], ['$set' => ['filename' => $newFilename]]);
    }

    public function drop()
    {
        $this->files->drop();
        $this->chunks->drop();
    }
}