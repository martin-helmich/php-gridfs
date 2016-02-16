<?php
namespace Helmich\GridFS\Stream;

use MongoDB\Model\BSONDocument;

interface DownloadStreamInterface
{
    public function read(int $n): string;

    public function eof(): bool;

    public function file(): BSONDocument;
}