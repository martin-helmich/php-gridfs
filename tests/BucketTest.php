<?php
namespace Helmich\GridFS\Tests;

use Helmich\GridFS\Bucket;
use Helmich\GridFS\BucketInterface;
use Helmich\GridFS\Options\BucketOptions;
use Helmich\GridFS\Options\DownloadByNameOptions;
use Helmich\GridFS\Options\FindOptions;
use Helmich\GridFS\Options\UploadOptions;
use Helmich\MongoMock\MockCollection;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDatetime;
use MongoDB\Database;
use Prophecy\Argument;

class BucketTest extends \PHPUnit_Framework_TestCase
{

    /** @var BucketInterface */
    private $bucket;

    /** @var MockCollection */
    private $files, $chunks;

    public function setUp()
    {
        $this->files = new MockCollection();
        $this->chunks = new MockCollection();

        $database = $this->prophesize(Database::class);
        $database->selectCollection('mybucket.dateien')->shouldBeCalled()->willReturn($this->files);
        $database->selectCollection('mybucket.brocken')->shouldBeCalled()->willReturn($this->chunks);

        $options = (new BucketOptions())
            ->withBucketName('mybucket')
            ->withChunkSizeBytes(4)
            ->withFilesName('dateien')
            ->withChunksName('brocken');

        $this->bucket = new Bucket($database->reveal(), $options);
    }

    public function testOpenUploadStreamCreatesIndicesWhenCollectionsAreEmpty()
    {
        $stream = $this->bucket->openUploadStream('test.txt');

        assertThat($this->files, collectionHasIndex(['filename' => 1, 'uploadDate' => 1]));
        assertThat($this->chunks, collectionHasIndex(['files_id' => 1, 'n' => 1], ['unique' => true]));
    }

    public function testOpenUploadStreamDoesNotCreateIndicesWhenCollectionsAreNotEmpty()
    {
        $this->files->documents[] = ['foo' => 'bar'];

        $stream = $this->bucket->openUploadStream('test.txt');

        assertThat(count($this->files->indices), equalTo(0));
        assertThat(count($this->chunks->indices), equalTo(0));
    }

    public function testChunksAreUploadedWhenWritingToUploadStream()
    {
        $options = (new UploadOptions)->withMetadata(['foo' => 'bar']);

        $stream = $this->bucket->openUploadStream('test.txt', $options);
        $stream->write('12345678');
        $stream->close();

        $id = $stream->fileId();

        assertThat($this->chunks->count(), equalTo(2));
        assertThat($this->files->count(), equalTo(1));

        assertThat($this->chunks, collectionHasDocument([
            'files_id' => $id,
            'n'        => 0,
            'data'     => '1234'
        ]));
        assertThat($this->chunks, collectionHasDocument([
            'files_id' => $id,
            'n'        => 1,
            'data'     => '5678'
        ]));
        assertThat($this->files, collectionHasDocument([
            '_id'        => $id,
            'length'     => 8,
            'chunkSize'  => 4,
            'uploadDate' => isInstanceOf(UTCDatetime::class),
            'md5'        => md5('12345678'),
            'filename'   => 'test.txt',
            'metadata'   => equalTo(['foo' => 'bar'])
        ]));
    }

    public function testChunksAreUploadedWhenWritingToUploadStreamWithOddLength()
    {
        $stream = $this->bucket->openUploadStream('test.txt');
        $stream->write('1234567');
        $stream->close();

        $id = $stream->fileId();

        assertThat($this->chunks->count(), equalTo(2));
        assertThat($this->files->count(), equalTo(1));

        assertThat($this->chunks, collectionHasDocument([
            'files_id' => $id,
            'n'        => 0,
            'data'     => '1234'
        ]));
        assertThat($this->chunks, collectionHasDocument([
            'files_id' => $id,
            'n'        => 1,
            'data'     => '567'
        ]));
    }

    public function testChunksAreUploadedWhenReadingFromPhpStream()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, '12345678');
        fseek($handle, 0);

        $id = $this->bucket->uploadFromStream('test.txt', $handle);

        assertThat($this->chunks->count(), equalTo(2));
        assertThat($this->files->count(), equalTo(1));

        assertThat($this->chunks, collectionHasDocument([
            'files_id' => $id,
            'n'        => 0,
            'data'     => '1234'
        ]));
        assertThat($this->chunks, collectionHasDocument([
            'files_id' => $id,
            'n'        => 1,
            'data'     => '5678'
        ]));
    }

    public function testUploadCanBeAborted()
    {
        $stream = $this->bucket->openUploadStream('test.txt');
        $stream->write('1234567');
        assertThat($this->chunks->count(), greaterThan(0));

        $stream->abort();
        assertThat($this->chunks->count(), equalTo(0));
    }

    public function testUploadCanBeAborted2()
    {
        $stream = $this->bucket->openUploadStream('test_first.txt');
        $stream->write('1234567');
        $stream->close();

        $stream = $this->bucket->openUploadStream('test.txt');
        $stream->write('1234567');
        assertThat($this->chunks->count(), greaterThan(2));

        $stream->abort();
        assertThat($this->chunks->count(), equalTo(2));
        assertThat($this->files->count(), equalTo(1));
    }

    public function testFilesCanBeRenamed()
    {
        $id = $this->files->insertOne(['filename' => 'old.txt'])->getInsertedId();
        $this->bucket->rename($id, 'new.txt');

        assertThat($this->files, collectionHasDocument(['_id' => $id, 'filename' => 'new.txt']));
    }

    public function testFileContentsCanBeDownloadedFromStream()
    {
        $stream = $this->bucket->openUploadStream('test.txt');
        $stream->write('12345678');
        $stream->close();

        $id = $stream->fileId();

        $downStream = $this->bucket->openDownloadStream($id);

        assertThat($downStream->file()['filename'], equalTo('test.txt'));
        assertThat($downStream->readAll(), equalTo('12345678'));
        assertThat($downStream->tell(), equalTo(8));
    }

    public function testCanSeekInDownloadStream()
    {
        $stream = $this->bucket->openUploadStream('test.txt');
        $stream->write('12345678');
        $stream->close();

        $id = $stream->fileId();

        $downStream = $this->bucket->openDownloadStream($id);

        assertThat($downStream->readAll(), equalTo('12345678'));
        assertThat($downStream->tell(), equalTo(8));

        $downStream->seek(4);
        assertThat($downStream->tell(), equalTo(4));
        assertThat($downStream->readAll(), equalTo('5678'));
    }

    /**
     * @expectedException \Helmich\GridFS\Exception\FileNotFoundException
     */
    public function testDownloadOfNonExistingFileThrowsException()
    {
        $id = new ObjectID();
        $downStream = $this->bucket->openDownloadStream($id);
    }

    public function testFileContentsCanBeDownloadedToPhpStream()
    {
        $fileStream = fopen('php://temp', 'r+');

        $stream = $this->bucket->openUploadStream('test.txt');
        $stream->write('12345678');
        $stream->close();

        $id = $stream->fileId();

        $this->bucket->downloadToStream($id, $fileStream);

        rewind($fileStream);
        assertThat(stream_get_contents($fileStream), equalTo('12345678'));
    }

    public function testFileContentsCanBeDownloadedFromStreamByName()
    {
        $firstStream = $this->bucket->openUploadStream('test.txt');
        $firstStream->write('12345678');
        $firstStream->close();
        $id = $firstStream->fileId();

        $secondStream = $this->bucket->openUploadStream('not-test.txt');
        $secondStream->write('NONONO');
        $secondStream->close();

        $down = $this->bucket->openDownloadStreamByName('test.txt');

        assertThat($down->file()['_id'], equalTo($id));
    }

    /**
     * @expectedException \Helmich\GridFS\Exception\FileNotFoundException
     */
    public function testOpenDownloadStreamByNameThrowsExceptionWhenFileIsNotFound()
    {
        $firstStream = $this->bucket->openUploadStream('test.txt');
        $firstStream->write('12345678');
        $firstStream->close();

        $secondStream = $this->bucket->openUploadStream('not-test.txt');
        $secondStream->write('NONONO');
        $secondStream->close();

        $this->bucket->openDownloadStreamByName('foobar.txt');
    }

    public function dataForRevisionDownload()
    {
        yield [-1, 'c'];
        yield [-2, 'b'];
        yield [-3, 'a'];
        yield [ 0, 'a'];
        yield [ 1, 'b'];
        yield [ 2, 'c'];
    }

    /**
     * @param $revision
     * @param $expectedId
     * @dataProvider dataForRevisionDownload
     */
    public function testOpenDownloadStreamByNameSelectsLatestRevision($revision, $expectedId)
    {
        $this->files->insertMany([
            ['_id' => 'a', 'filename' => 'test.txt', 'uploadDate' => new UTCDatetime(time() - 120)],
            ['_id' => 'b', 'filename' => 'test.txt', 'uploadDate' => new UTCDatetime(time() -  60)],
            ['_id' => 'c', 'filename' => 'test.txt', 'uploadDate' => new UTCDatetime(time())],
        ]);

        $options = (new DownloadByNameOptions)->withRevision($revision);
        $down = $this->bucket->openDownloadStreamByName('test.txt', $options);

        assertThat($down->file()['_id'], equalTo($expectedId));
    }

    /**
     * @param $revision
     * @param $expectedId
     * @dataProvider dataForRevisionDownload
     */
    public function testDownloadToStreamByNameSelectsLatestRevision($revision, $expectedId)
    {
        $fileStream = fopen('php://temp', 'r+');

        $ids = $this->files->insertMany([
            ['filename' => 'test.txt', 'uploadDate' => new UTCDatetime(time() - 120)],
            ['filename' => 'test.txt', 'uploadDate' => new UTCDatetime(time() -  60)],
            ['filename' => 'test.txt', 'uploadDate' => new UTCDatetime(time())],
        ])->getInsertedIds();

        $this->chunks->insertMany([
            ['files_id' => $ids[0], 'n' => 0, 'data' => new Binary('a', Binary::TYPE_GENERIC)],
            ['files_id' => $ids[1], 'n' => 0, 'data' => new Binary('b', Binary::TYPE_GENERIC)],
            ['files_id' => $ids[2], 'n' => 0, 'data' => new Binary('c', Binary::TYPE_GENERIC)],
        ]);

        $options = (new DownloadByNameOptions)->withRevision($revision);

        $this->bucket->downloadToStreamByName('test.txt', $fileStream, $options);

        rewind($fileStream);
        assertThat(stream_get_contents($fileStream), equalTo($expectedId));
    }

    public function testFindReturnsMatchingDocuments()
    {
        $this->files->insertMany([
            ['filename' => 'foo.txt', 'uploadDate' => new UTCDatetime(time() - 120)],
            ['filename' => 'bar.txt', 'uploadDate' => new UTCDatetime(time() -  60)],
            ['filename' => 'baz.txt', 'uploadDate' => new UTCDatetime(time())],
        ]);

        $files = $this->bucket->find(['filename' => 'foo.txt']);
        assertThat(count($files), equalTo(1));
    }

    public function testOptionsArePassedToFind()
    {
        $this->files->insertMany([
            ['filename' => 'foo.txt', 'uploadDate' => new UTCDatetime(time() - 120)],
            ['filename' => 'bar.txt', 'uploadDate' => new UTCDatetime(time() -  60)],
            ['filename' => 'baz.txt', 'uploadDate' => new UTCDatetime(time())],
        ]);

        $options = (new FindOptions())
            ->withBatchSize(1234)
            ->withLimit(2345)
            ->withMaxTimeMs(3456)
            ->withNoCursorTimeout()
            ->withSkip(4567)
            ->withSort(['uploadDate' => 1]);

        $this->bucket->find(['filename' => 'foo.txt'], $options);
        assertThat($this->files, collectionExecutedQuery(['filename' => 'foo.txt'], [
            'batchSize' => 1234,
            'limit' => 2345,
            'maxTimeMS' => 3456,
            'noCursorTimeout' => true,
            'skip' => 4567,
            'sort' => ['uploadDate' => 1]
        ]));
    }

    public function testDeleteRemotesFilesAndChunks()
    {
        $fileOne = $this->files->insertOne(['filename' => 'foo.txt', 'uploadDate' => new UTCDatetime(time() - 120)])->getInsertedId();
        $fileTwo = $this->files->insertOne(['filename' => 'bar.txt', 'uploadDate' => new UTCDatetime(time() -  60)])->getInsertedId();

        $this->chunks->insertMany([
            ['files_id' => $fileOne, 'n' => 0, 'data' => new Binary('a', Binary::TYPE_GENERIC)],
            ['files_id' => $fileOne, 'n' => 1, 'data' => new Binary('a', Binary::TYPE_GENERIC)],
            ['files_id' => $fileTwo, 'n' => 0, 'data' => new Binary('a', Binary::TYPE_GENERIC)],
        ]);

        $this->bucket->delete($fileOne);

        assertThat($this->files->count(), equalTo(1));
        assertThat($this->files->count(['_id' => $fileOne]), equalTo(0));

        assertThat($this->chunks->count(), equalTo(1));
        assertThat($this->chunks->count(['files_id' => $fileOne]), equalTo(0));
    }

    public function testDropDropsCollections()
    {
        $this->bucket->drop();
        assertThat($this->files->dropped, isTrue());
        assertThat($this->chunks->dropped, isTrue());
    }
}