<?php
namespace Helmich\GridFS\Stream;

use MongoDB\BSON\Binary;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use MongoDB\Model\BSONDocument;

class DownloadStream implements DownloadStreamInterface
{

    /** @var Collection */
    private $chunks;

    /** @var Cursor */
    private $cursor;

    /** @var bool */
    private $eof = false;

    /** @var string */
    private $buf = '';
    /**
     * @var BSONDocument
     */
    private $file;

    public function __construct($file, Collection $chunks)
    {
        $this->chunks = $chunks;
        $this->file   = $file;
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

    public function file(): BSONDocument
    {
        return $this->file;
    }

    private function initCursor()
    {
        if (!$this->cursor) {
            $this->cursor = $this->chunks->find(['files_id' => $this->file['_id']], ['sort' => ['n' => 1]]);
        }
    }
}