<?php
namespace Helmich\GridFS;

use Helmich\GridFS\Concern\DeletionConcern;
use Helmich\GridFS\Concern\DownloadConcern;
use Helmich\GridFS\Concern\SearchConcern;
use Helmich\GridFS\Concern\UploadConcern;
use Helmich\GridFS\Options\BucketOptions;
use Helmich\GridFS\Options\DownloadByNameOptions;
use Helmich\GridFS\Options\FindOptions;
use Helmich\GridFS\Options\UploadOptions;
use Helmich\GridFS\Stream\DownloadStreamInterface;
use Helmich\GridFS\Stream\UploadStreamInterface;
use MongoDB\BSON\ObjectID;
use MongoDB\Database;

class Bucket implements BucketInterface
{

    /** @var UploadConcern */
    private $uploadConcern;

    /** @var DownloadConcern */
    private $downloadConcern;

    /** @var SearchConcern */
    private $searchConcern;

    /** @var DeletionConcern */
    private $deletionConcern;

    public function __construct(Database $database, BucketOptions $options = null)
    {
        $options  = $options ?? new BucketOptions();

        $files  = $database->selectCollection($options->bucketName() . '.' . $options->filesName());
        $chunks = $database->selectCollection($options->bucketName() . '.' . $options->chunksName());

        $this->uploadConcern   = new UploadConcern($this, $files, $chunks, $options);
        $this->downloadConcern = new DownloadConcern($this, $files, $chunks, $options);
        $this->searchConcern   = new SearchConcern($this, $files, $chunks, $options);
        $this->deletionConcern = new DeletionConcern($this, $files, $chunks, $options);
    }

    public function openUploadStream(string $filename, UploadOptions $options = null): UploadStreamInterface
    {
        return $this->uploadConcern->openUploadStream($filename, $options);
    }

    public function uploadFromStream(string $filename, $stream, UploadOptions $options = null): ObjectID
    {
        return $this->uploadConcern->uploadFromStream($filename, $stream, $options);
    }

    public function rename(ObjectID $id, string $newFilename)
    {
        $this->uploadConcern->rename($id, $newFilename);
    }

    public function openDownloadStream(ObjectID $id): DownloadStreamInterface
    {
        return $this->downloadConcern->openDownloadStream($id);
    }

    public function downloadToStream(ObjectID $id, $stream)
    {
        $this->downloadConcern->downloadToStream($id, $stream);
    }

    public function find(array $filter, FindOptions $options = null): \Traversable
    {
        return $this->searchConcern->find($filter, $options);
    }

    public function delete(ObjectID $id)
    {
        $this->deletionConcern->delete($id);
    }

    public function openDownloadStreamByName(
        string $filename,
        DownloadByNameOptions $options = null
    ): DownloadStreamInterface
    {
        return $this->downloadConcern->openDownloadStreamByName($filename, $options);
    }

    public function downloadToStreamByName(string $filename, $stream, DownloadByNameOptions $options = null)
    {
        $this->downloadConcern->downloadToStreamByName($filename, $stream, $options);
    }

    public function drop()
    {
        $this->deletionConcern->drop();
    }
}
