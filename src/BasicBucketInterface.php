<?php
namespace Helmich\GridFS;

use Helmich\GridFS\Stream\DownloadStreamInterface;
use Helmich\GridFS\Stream\UploadStreamInterface;
use Helmich\GridFS\Options\FindOptions;
use Helmich\GridFS\Options\UploadOptions;
use MongoDB\BSON\ObjectID;
use Traversable;

/**
 * Interface definition for the basic GridFS bucket API as defined in the
 * GridFS specification.
 *
 * @author  Martin Helmich <kontakt@martin-helmich.de>
 * @package Helmich\GridFS
 */
interface BasicBucketInterface
{

    /**
     * Opens a Stream that the application can write the contents of the file to.
     *
     * @param string        $filename The file name
     * @param UploadOptions $options  Upload options
     * @return UploadStreamInterface A stream to which the application will write
     *                                the contents.
     */
    public function openUploadStream(string $filename, UploadOptions $options = null): UploadStreamInterface;

    /**
     * Uploads a user file to a GridFS bucket.
     *
     * Reads the contents of the user file from the $stream and uploads it as
     * chunks in the chunks collection. After all the chunks have been uploaded,
     * it creates a files collection document for $filename in the files
     * collection.
     *
     * @param string        $filename The file name
     * @param resource      $stream   The stream to read from
     * @param UploadOptions $options  Upload options
     * @return ObjectID The id of the uploaded file.
     */
    public function uploadFromStream(string $filename, $stream, UploadOptions $options = null): ObjectID;

    /**
     * Opens a Stream from which the application can read the contents of the
     * stored file specified by $id.
     *
     * @param ObjectID $id The ID of the file to download
     * @return DownloadStreamInterface A download stream
     */
    public function openDownloadStream(ObjectID $id): DownloadStreamInterface;

    /**
     * Downloads the contents of the stored file specified by $id and writes
     * the contents to the $destination stream.
     *
     * @param ObjectID $id     The ID of the file to download
     * @param resource $stream The stream to write into
     * @return void
     */
    public function downloadToStream(ObjectID $id, $stream);

    /**
     * Given a $id, delete this stored file’s files collection document and
     * associated chunks from a GridFS bucket.
     *
     * @param ObjectID $id The ID of the file to delete
     * @return void
     */
    public function delete(ObjectID $id);

    /**
     * Find and return the files collection documents that match $filter.
     *
     * @param array       $filter  The search filter
     * @param FindOptions $options Find options
     * @return Traversable A traversable collection of all matching files
     */
    public function find(array $filter, FindOptions $options = null): Traversable;
}
