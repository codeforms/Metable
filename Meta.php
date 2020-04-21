<?php
namespace CodeForms\Repositories\Meta;

use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    /**
     * @var string
     */
    protected $table = 'meta';

    /**
     * @var array
     */
    protected $casts = [
        'value' => 'json',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['key', 'value'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function metable()
    {
        return $this->morphTo();
    }
}
