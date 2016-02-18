# GridFS implementation for the PHP MongoDB driver

This package provides a userspace implementation of the [GridFS specification][gridfs] for PHP's new [`mongodb`][phpext] extension (not to be confused with the similarly named `mongo` extension).

**This library requires PHP 7!**

## Installation

Use [Composer][composer]:

    $ composer require helmich/gridfs

## Usage

### Initialization

GridFS is oriented around *Buckets*. For a bucket named `$name`, this library will create two collections `$name.files` and `$name.chunks` in which file metadata and content blocks will be stored. Create a new bucket by instantiating the `Helmich\GridFS\Bucket` class. You will need a `MongoDB\Database` instance as dependency and can optionally pass an instance of the `Helmich\GridFS\Options\BucketOptions` class to configure your bucket:

```php
$manager = new \MongoDB\Driver\Manager('mongodb://localhost:27017');
$database = new \MongoDB\Database($manager, 'yourdatabase');

$bucketOptions = (new \Helmich\GridFS\Options\BucketOptions)
  ->withBucketName('yourbucket');
$bucket = new \Helmich\GridFS\Bucket($database, $bucketOptions);
```

### Uploading files into a bucket

Uploading of files is done via streams. You can open a new upload stream using the `openUploadStream` function:

```php
$uploadOptions = (new \Helmich\GridFS\Options\UploadOptions)
  ->withChunkSizeBytes(4 << 10)
  ->withMetadata(['foo' => 'bar']);

$uploadStream = $bucket->openUploadStream("helloworld.txt", $uploadOptions);
$uploadStream->write("Hello World!");
$uploadStream->close();

echo "File ID is: " . $uploadStream->fileId();
```

Alternatively, use the `uploadFromStream` method, which takes a PHP stream as a parameter:

```php
$fileStream = fopen('humungousfile.blob', 'r');
$fileId = $bucket->uploadFromStream('humungousfile.blob', $fileStream, $uploadOptions);
```

### Finding uploaded files

Use the `find` method to find files in your bucket. The `find` method takes a MongoDB query object as first parameter that is applied to the `$bucketName.files` collection. The second parameter is an instance of the `Helmich\GridFS\Options\FindOptions` class in which you can specify advanced options for the search:

```php
$options = (new \Helmich\GridFS\Options\FindOptions)
  ->withBatchSize(32)
  ->withLimit(128)
  ->withSkip(13)
  ->withSort(['filename' => 1])
  ->withNoCursorTimeout();

$query = [
  'filename' => [
    '$in': ['foo.txt', 'bar.txt']
  ],
  'uploadDate' => [
    '$gt' => new \MongoDB\BSON\UTCDatetime(time() - 86400)
  ]
];

$files = $bucket->find($query, $options);
```

### Downloading files from a bucket

Downloading files is also stream-oriented. Given the ID of an already existing file, open a new download stream using the `openDownloadStream` function:

```php
$file = $bucket->find(['filename' => 'foo.txt'], (new \Helmich\GridFS\Options\FindOptions)->withLimit(1))[0];

$downloadStream = $bucket->openDownloadStream($file['_id']);
echo $downloadStream->getContents();

// alternatively:
while (!$downloadStream->eof()) {
  echo $downloadStream->read(4096);
}
```

Given an already existing (and writeable) PHP stream, you can also use the `downloadToStream` method to pipe the file directly into the stream:

```php
$fileStream = fopen('humungousfile.blob', 'w');
$bucket->downloadToStream($id, $fileStream);
```

There is also a `byName` variant of both `openDownloadStream` (`openDownloadStreamByName()`) and `downloadToStream` (`downloadToStreamByName`). Both of these functions take a file name and a `Helmich\GridFS\Options\DownloadOptions` instance as parameter. The `$options` parameter allows you to specify which revision of the given filename should be downloaded:

```php
$options = (new \Helmich\GridFS\Options\DownloadByNameOptions)
  ->withRevision(-1); // also the default; will download the latest revision of the file

$stream = $bucket->openDownloadStreamByName('yourfile.txt', $options);
```

### Deleting files

Delete files from the bucket using the `delete` method:

```php
$bucket->delete($fileId);
```

[composer]: http://getcomposer.org
[gridfs]: https://github.com/mongodb/specifications/blob/master/source/gridfs/gridfs-spec.rst
[phpext]: http://php.net/manual/en/set.mongodb.php
