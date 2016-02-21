# GridFS implementation for the PHP MongoDB driver

This package provides a userspace implementation of the [GridFS specification][gridfs] for PHP's new [`mongodb`][phpext] extension (not to be confused with the similarly named `mongo` extension).

**This library requires PHP 7!**

## Author and license

Martin Helmich  
This library is [MIT-licensed](LICENSE.txt).

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
echo $downloadStream->readAll();

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

## Gimmicks

### PSR-7 adapters

I've implemented this package for a [PSR-7 compliant][psr7] web application. PSR-7 also heavily relies on streams, so I've found it useful to add an adapter class to map a `Helmich\GridFS\Stream\DownloadStreamInterface` to a `Psr\Http\Message\StreamInterface`. This is especially useful if you want to return GridFS files as a response body stream. The following example uses the [Slim framework][slim], but should be easily adaptable to other PSR-7 compliant frameworks:

```php
$app->get(
  '/documents/{name}',
  function(RequestInterface $req, ResponseInterface $res, array $args) use ($bucket): ResponseInterface
  {
    $stream = $bucket->openDownloadStreamByName($args['name']);
    return $res
      ->withHeader('content-type', $stream->file()['metadata']['contenttype'])
      ->withBody(new \Helmich\GridFS\Stream\Psr7\DownloadStreamAdapter($stream));
  }
);
```

[composer]: http://getcomposer.org
[gridfs]: https://github.com/mongodb/specifications/blob/master/source/gridfs/gridfs-spec.rst
[phpext]: http://php.net/manual/en/set.mongodb.php
[psr7]: http://www.php-fig.org/psr/psr-7/
[slim]: http://www.slimframework.com/
