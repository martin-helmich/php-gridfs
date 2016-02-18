<?php
namespace Helmich\GridFS\Stream;

use Helmich\GridFS\Options\UploadOptions;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDatetime;
use MongoDB\Collection;

class UploadStream implements UploadStreamInterface
{

    private $data = '';

    /** @var int */
    private $chunkSize;

    /** @var ObjectID */
    private $objectID;

    /** @var Collection */
    private $files;
    /**
     * @var string
     */
    private $filename;
    /**
     * @var UploadOptions
     */
    private $options;
    /**
     * @var Collection
     */
    private $chunks;

    /** @var int */
    private $chunkCounter = 0;

    /** @var int */
    private $length = 0;

    /** @var resource */
    private $hashContext;

    public function __construct(
        string $filename,
        int $chunkSize,
        ObjectID $objectID,
        UploadOptions $options,
        Collection $files,
        Collection $chunks
    ) {
        $this->chunkSize   = $chunkSize;
        $this->objectID    = $objectID;
        $this->files       = $files;
        $this->filename    = $filename;
        $this->options     = $options;
        $this->chunks      = $chunks;
        $this->hashContext = hash_init('md5');
    }

    public function write(string $data): int
    {
        $length = strlen($data);

        $this->data .= $data;
        $this->length += $length;
        hash_update($this->hashContext, $data);

        while (strlen($this->data) >= $this->chunkSize) {
            $nextChunk  = substr($this->data, 0, $this->chunkSize);
            $this->data = substr($this->data, $this->chunkSize);

            $chunk = [
                'files_id' => $this->objectID,
                'n' => $this->chunkCounter++,
                'data' => new Binary($nextChunk, Binary::TYPE_GENERIC)
            ];

            $this->chunks->insertOne($chunk);
        }
        return $length;
    }

    public function close()
    {
        $lastChunk = [
            'files_id' => $this->objectID,
            'n' => $this->chunkCounter++,
            'data' => new Binary($this->data, Binary::TYPE_GENERIC)
        ];
        $this->chunks->insertOne($lastChunk);

        $document = [
            '_id' => $this->objectID,
            'length' => $this->length,
            'chunkSize' => $this->chunkSize,
            'uploadDate' => new UTCDatetime(time() * 1000),
            'md5' => hash_final($this->hashContext),
            'filename' => $this->filename,
            'metadata' => $this->options->metadata()
        ];
        $this->files->insertOne($document);
    }

    public function fileId(): ObjectID
    {
        return $this->objectID;
    }

    public function abort()
    {
        $this->chunks->deleteMany(['files_id' => $this->objectID]);
        $this->files->deleteMany(['_id' => $this->objectID]);
    }
}