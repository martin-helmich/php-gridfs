<?php
namespace Helmich\GridFS\Tests;

use Helmich\GridFS\Bucket;
use Helmich\GridFS\BucketInterface;
use Helmich\GridFS\Exception\FileNotFoundException;
use Helmich\GridFS\Options\BucketOptions;
use Helmich\GridFS\Options\UploadOptions;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDatetime;
use MongoDB\Collection;
use MongoDB\Database;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class BucketTest extends \PHPUnit_Framework_TestCase
{

    /** @var BucketInterface */
    private $bucket;

    /** @var MockCollection */
    private $files, $chunks;

    private $filesDocs = [];
    private $chunksDocs = [];

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

        assertThat($this->files->hasIndex(['filename' => 1, 'uploadDate' => 1]), isTrue());
        assertThat($this->chunks->hasIndex(['files_id' => 1, 'n' => 1], ['unique' => true]), isTrue());
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

        assertThat($this->chunks->documents[0]['files_id'], equalTo($id));
        assertThat($this->chunks->documents[0]['n'], equalTo(0));
        assertThat($this->chunks->documents[0]['data']->getData(), equalTo('1234'));
        assertThat($this->chunks->documents[1]['files_id'], equalTo($id));
        assertThat($this->chunks->documents[1]['n'], equalTo(1));
        assertThat($this->chunks->documents[1]['data']->getData(), equalTo('5678'));

        assertThat($this->files->documents[0]['_id'], equalTo($id));
        assertThat($this->files->documents[0]['length'], equalTo(8));
        assertThat($this->files->documents[0]['chunkSize'], equalTo(4));
        assertThat($this->files->documents[0]['uploadDate'], isInstanceOf(UTCDatetime::class));
        assertThat($this->files->documents[0]['md5'], equalTo(md5('12345678')));
        assertThat($this->files->documents[0]['filename'], equalTo('test.txt'));
        assertThat($this->files->documents[0]['metadata'], equalTo(['foo' => 'bar']));
    }

    public function testChunksAreUploadedWhenWritingToUploadStreamWithOddLength()
    {
        $stream = $this->bucket->openUploadStream('test.txt');
        $stream->write('1234567');
        $stream->close();

        $id = $stream->fileId();

        assertThat($this->chunks->count(), equalTo(2));
        assertThat($this->files->count(), equalTo(1));

        assertThat($this->chunks->documents[0]['files_id'], equalTo($id));
        assertThat($this->chunks->documents[0]['n'], equalTo(0));
        assertThat($this->chunks->documents[0]['data']->getData(), equalTo('1234'));
        assertThat($this->chunks->documents[1]['files_id'], equalTo($id));
        assertThat($this->chunks->documents[1]['n'], equalTo(1));
        assertThat($this->chunks->documents[1]['data']->getData(), equalTo('567'));
    }

    public function testChunksAreUploadedWhenReadingFromPhpStream()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, '12345678');
        fseek($handle, 0);

        $id = $this->bucket->uploadFromStream('test.txt', $handle);

        assertThat($this->chunks->count(), equalTo(2));
        assertThat($this->files->count(), equalTo(1));

        assertThat($this->chunks->documents[0]['files_id'], equalTo($id));
        assertThat($this->chunks->documents[0]['n'], equalTo(0));
        assertThat($this->chunks->documents[0]['data']->getData(), equalTo('1234'));
        assertThat($this->chunks->documents[1]['files_id'], equalTo($id));
        assertThat($this->chunks->documents[1]['n'], equalTo(1));
        assertThat($this->chunks->documents[1]['data']->getData(), equalTo('5678'));
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
        $id = new ObjectID();

        $this->files->documents[] = ['_id' => $id, 'filename' => 'old.txt'];
        $this->bucket->rename($id, 'new.txt');

        assertThat($this->files->documents[0]['filename'], equalTo('new.txt'));
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
}