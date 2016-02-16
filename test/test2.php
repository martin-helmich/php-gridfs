<?php

require __DIR__ . '/../vendor/autoload.php';

$manager = new \MongoDB\Driver\Manager('mongodb://db');
$database = new \MongoDB\Database($manager, 'gridfs');

$bucket = new \Helmich\GridFS\BasicBucket($database, new \Helmich\GridFS\Options\BucketOptions());
#$bucket->uploadFromStream('test1.jpg', fopen('test1.jpg', 'r'), new \Helmich\GridFS\Options\UploadOptions());
$bucket->downloadToStream(new \MongoDB\BSON\ObjectID('56c258782865b0008628c8e1'), fopen('test2.jpg', 'w'));