<?php
namespace Helmich\GridFS\Stream;

use Helmich\GridFS\Exception\FileNotFoundException;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;

class DownloadStream implements DownloadStreamInterface
{

    /** @var ObjectID */
    private $id;

    /** @var Collection */
    private $files;

    /** @var Collection */
    private $chunks;

    /** @var Cursor */
    private $cursor;

    /** @var bool */
    private $eof = false;

    /** @var string */
    private $buf = '';

    public function __construct(ObjectID $id, Collection $files, Collection $chunks)
    {
        $this->id     = $id;
        $this->files  = $files;
        $this->chunks = $chunks;
    }

    public function read(int $n): string
    {
        $this->initCursor();

        foreach ($this->cursor as $chunk) {
            /** @var Binary $data */
            $data = $chunk['data'];

            if (!$data instanceof Binary) {
                var_dump($data);
                break;
            }

            $this->buf .= $data->getData();

            if (strlen($this->buf) < $n) {
                $data      = substr($this->buf, 0, $n);
                $this->buf = substr($this->buf, $n);
                return $data;
            }
        }

        $this->eof = true;
        return $this->buf;
    }

    public function eof(): bool
    {
        return $this->eof;
    }

    private function initCursor()
    {
        if (!$this->cursor) {
            $fileObj = $this->files->findOne(['_id' => $this->id]);
            if (!$fileObj) {
                throw new FileNotFoundException($this->id);
            }

            $this->cursor = $this->chunks->find(['files_id' => $this->id], ['sort' => ['n' => 1]]);
        }
    }
}