<?php
namespace Helmich\GridFS\Concern;

use Helmich\GridFS\Exception\FileNotFoundException;
use Helmich\GridFS\Options\DownloadByNameOptions;
use Helmich\GridFS\Options\FindOptions;
use Helmich\GridFS\Stream\DownloadStream;
use Helmich\GridFS\Stream\DownloadStreamInterface;
use MongoDB\BSON\ObjectID;

class DownloadConcern
{
    use ConcernProperties;

    public function openDownloadStream(ObjectID $id): DownloadStreamInterface
    {
        $file = $this->files->findOne(['_id' => $id]);
        if (!$file) {
            throw new FileNotFoundException($id);
        }
        return new DownloadStream($file, $this->chunks);
    }

    public function downloadToStream(ObjectID $objectID, $stream)
    {
        $downloadStream = $this->openDownloadStream($objectID);
        while (!$downloadStream->eof()) {
            $next = $downloadStream->read(4096);
            fwrite($stream, $next);
        }
    }

    public function openDownloadStreamByName(
        string $filename,
        DownloadByNameOptions $options = null
    ): DownloadStreamInterface {
        $options     = $options ?? new DownloadByNameOptions();
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

        $files = $this->bucket->find(['filename' => $filename], $findOptions);
        foreach ($files as $file) {
            return new DownloadStream($file, $this->chunks);
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
}
