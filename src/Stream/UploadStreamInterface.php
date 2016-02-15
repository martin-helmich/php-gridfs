<?php
namespace Helmich\GridFS\Stream;

use MongoDB\BSON\ObjectID;

interface UploadStreamInterface
{
    public function write(string $data): int;

    public function abort();

    public function close();

    public function fileId(): ObjectID;
}