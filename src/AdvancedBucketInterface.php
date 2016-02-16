<?php
namespace Helmich\GridFS;

use Helmich\GridFS\Options\DownloadByNameOptions;
use Helmich\GridFS\Stream\DownloadStreamInterface;
use MongoDB\BSON\ObjectID;

/**
 * Interface definition for the advanced GridFS bucket API as defined in the
 * GridFS specification.
 *
 * @author  Martin Helmich <kontakt@martin-helmich.de>
 * @package Helmich\GridFS
 */
interface AdvancedBucketInterface
{

    /**
     * Opens a Stream from which the application can read the contents of the
     * stored file specified by $filename and the revision in $options.
     *
     * @param string                $filename The name of the file to download
     * @param DownloadByNameOptions $options  Download options
     * @return DownloadStreamInterface A download stream
     */
    public function openDownloadStreamByName(
        string $filename,
        DownloadByNameOptions $options = null
    ): DownloadStreamInterface;

    /**
     * Downloads the contents of the stored file specified by $filename and by
     * the revision in $options and writes the contents to the $destination
     * stream.
     *
     * @param string                $filename The name of the file to download
     * @param resource              $stream   The stream into which to write the downloaded file
     * @param DownloadByNameOptions $options  Download options
     * @return void
     */
    public function downloadToStreamByName(string $filename, $stream, DownloadByNameOptions $options = null);

    /**
     * Renames the stored file with the specified @id.
     *
     * @param ObjectID $id          The object ID
     * @param string   $newFilename The new filename
     * @return void
     */
    public function rename(ObjectID $id, string $newFilename);

    /**
     * Drops the files and chunks collections associated with this bucket.
     *
     * @return void
     */
    public function drop();

}