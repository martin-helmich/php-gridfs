<?php
namespace Helmich\GridFS\Stream;

use IteratorIterator;
use MongoDB\BSON\Binary;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;

class DownloadStream implements DownloadStreamInterface
{

    /** @var Collection */
    private $chunks;

    /** @var IteratorIterator */
    private $cursor;

    /** @var bool */
    private $eof = false;

    /** @var int */
    private $pos = 0;

    /** @var string */
    private $buf = '';

    /** @var BSONDocument */
    private $file;

    public function __construct($file, Collection $chunks)
    {
        $this->chunks = $chunks;
        $this->file   = $file;
    }

    public function read(int $n): string
    {
        $this->initCursor();

        while ((strlen($this->buf) < $n) && ($chunk = $this->cursor->current())) {
            $chunkData = $chunk['data'];
            if ($chunkData instanceof Binary) {
                $this->buf .= $chunkData->getData();
            }

            $this->cursor->next();
        }

        if (strlen($this->buf) >= $n) {
            $data      = substr($this->buf, 0, $n);
            $this->buf = substr($this->buf, $n);
            $this->pos += $n;
            return $data;
        } else {
            $this->eof = true;
            $this->pos += strlen($this->buf);
            return $this->buf;
        }
    }

    public function readAll(): string
    {
        $contents = '';

        while (!$this->eof()) {
            $contents .= $this->read($this->file['chunkSize']);
        }

        return $contents;
    }

    public function reset()
    {
        $this->cursor = null;
        $this->eof    = false;
        $this->buf    = '';
        $this->pos    = 0;
    }

    public function tell(): int
    {
        return $this->pos;
    }

    public function eof(): bool
    {
        return $this->eof;
    }

    public function file(): BSONDocument
    {
        return $this->file;
    }

    public function seek(int $n)
    {
        $this->reset();

        $chunk = intdiv($n, $this->file['chunkSize']);
        $this->cursor = $this->chunks->find(
            ['files_id' => $this->file['_id']],
            ['sort' => ['n' => 1], 'skip' => $chunk]
        );

        $remaining = $n % $this->file['chunkSize'];
        $this->read($remaining);
    }

    private function initCursor()
    {
        if (!$this->cursor) {
            $cursor = $this->chunks->find(['files_id' => $this->file['_id']], ['sort' => ['n' => 1]]);

            // Credits to [1] for the IteratorIterator trick
            //   [1] http://php.net/manual/de/class.mongodb-driver-cursor.php#118824
            $this->cursor = new IteratorIterator($cursor);
            $this->cursor->rewind();
        }
    }
}