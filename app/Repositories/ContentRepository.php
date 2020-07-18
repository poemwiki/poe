<?php

namespace App\Repositories;

use App\Models\Content;
use App\Repositories\BaseRepository;

/**
 * Class ContentRepository
 * @package App\Repositories
 * @version July 14, 2020, 6:24 pm UTC
*/

class ContentRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'hash',
        'new_hash',
        'type',
        'entry_id',
        'content'
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Content::class;
    }
}
