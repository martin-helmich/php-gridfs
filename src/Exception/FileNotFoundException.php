<?php
namespace Helmich\GridFS\Exception;

class FileNotFoundException extends \Exception
{

    /** @var string */
    private $filename;

    public function __construct(string $filename, int $code = 0, \Exception $previous = null)
    {
        $this->filename = $filename;
        parent::__construct('the file "' . $filename . '" does not exist in GridFS', $code, $previous);
    }

    public function getFilename()
    {
        return $this->filename;
    }
}