<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Discussion extends Model
{
    use HasFactory;
    use Searchable;

    protected $table = 'discussion';
    protected $guarded = [];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        //$array = $this->toArray();

        $array = $this->only('id', 'topic', 'tags');

        return $array;
    }

    public function shouldBeSearchable(){

        return $this->type === 'public';
    }
}