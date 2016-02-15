<?php
namespace Helmich\GridFS\Stream;

interface DownloadStreamInterface
{
    public function read(int $n): string;

    public function eof(): bool;
}