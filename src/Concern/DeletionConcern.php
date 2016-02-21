<?php
namespace Helmich\GridFS\Concern;

use MongoDB\BSON\ObjectID;

class DeletionConcern
{
    use ConcernProperties;

    public function delete(ObjectID $id)
    {
        $this->files->deleteMany(['_id' => $id]);
        $this->chunks->deleteMany(['files_id' => $id]);
    }

    public function drop()
    {
        $this->files->drop();
        $this->chunks->drop();
    }
}
