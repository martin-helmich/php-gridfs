<?php
namespace Helmich\GridFS\Stream;

use MongoDB\Model\BSONDocument;

interface DownloadStreamInterface
{
    public function read(int $n): string;

    public function readAll(): string;

    public function reset();

    public function tell(): int;

    public function seek(int $n);

    public function eof(): bool;

    public function file(): BSONDocument;
}
