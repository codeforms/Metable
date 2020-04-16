<?php
namespace CodeForms\Repositories\Meta;

use Illuminate\Database\Eloquent\Builder;
use CodeForms\Repositories\Meta\Meta;
/**
 * @package CodeForms\Repositories\Meta\Metable
 */
trait Metable
{
    /**
     * @return Illuminate\Database\Eloquent\Model
     */
    public static function bootMetable()
    {
        static::deleted(function (self $model) {
            $model->deleteMeta();
        });
    }

    /**
     * @return object
     */
    public function allMeta()
    {
        return $this->meta()->select('value', 'key')->get();
    }

    /**
     * @param  string $key 
     * 
     * @return boolean
     */
    public function hasMeta($key): bool
    {
        return (bool) self::countMeta($key);
    }

    /**
     * @param  string $key
     * 
     * @return object|null
     */
    public function getMeta($key)
    {
        return self::hasMeta($key) ? self::rawMeta($key)->value : null;
    }

    /**
     * @param string|array $key
     * 
     * @return void
     */
    public function metaByKeys($key)
    {
        return $this->meta()->whereIn('key', (array)$key)->get();
    }

    /**
     * @param  string $key
     * 
     * @return object|null
     */
    public function rawMeta($key)
    {
        return $this->meta()->where('key', $key)->first();
    }

    /**
     * @param  string|array $key
     * @param  mixed        $value
     * 
     * @return bool|null
     */
    public function setMeta($key, $value = null)
    {
        if(is_array($key))
            foreach ($key as $k => $v)
                self::saveMeta($k, $v);
        elseif(is_string($key))
            return self::saveMeta($key, $value);
    }

    /**
     * @param string $key
     * @param $value
     * 
     * @return bool
     */
    public function addMeta($key, $value)
    {
        return self::createMeta($key, $value);
    }

    /**
     * @param Builder $query
     * 
     * @return object
     */
    public function scopeWithMeta(Builder $query)
    {
        return $query->with('meta');
    }

    /**
     * @param Builder $query
     * @param string $key
     * @param string $value
     * @param string $notation
     * 
     * @return object
     */
    public function scopeWhereMeta(Builder $query, string $key, string $value = null, string $notation = null)
    {
        return $query->whereHas('meta', function(Builder $query) use($key, $value, $notation) {
            $query->where('key', $key);
            $query->when(!is_null($value), function($query) use($value, $notation) {
                return !is_null($notation) ? 
                        $query->whereJsonContains("value->{$notation}", $value) : 
                            $query->where('value', 'like', '%'.$value.'%');
            });
        });
    }

    /**
     * @param Builder $query
     * @param $key
     * 
     * @return integer
     */
    public function countMeta($key = []): int
    {
        return $this->meta()->whereIn('key', (array)$key)->count();
    }

    /**
     * @param  string $key
     * @param  string $value
     * 
     * @return bool
     */
    public function deleteMeta($key = null, $value = null)
    {
        return $this->meta()->when(!is_null($key), function($query) use($key) {
                return $query->where("key", $key);
            })->when(!is_null($value), function($query) use($value) {
                return $query->where("value", $value);
            })->delete();
    }

    /**
     * @param string $key
     * @param $value
     * 
     * @return bool
     */
    private function createMeta($key, $value)
    {
        return $this->meta()->create([
            'key'   => $key,
            'value' => $value,
        ]);
    }

    /**
     * @param $key
     * @param $value
     * 
     * @return bool
     */
    private function saveMeta($key, $value)
    {
        if(isset($value))
            return self::hasMeta($key) ? self::updateMeta($key, $value) : self::createMeta($key, $value);

        return self::deleteMeta($key);
    }

    /**
     * @param  string $key
     * @param  mixed $value
     * 
     * @return object|bool
     */
    private function updateMeta($key, $value)
    {
        if ($meta = self::rawMeta($key))
            $meta->value = $value;
            $meta->save();
    }

    /**
     * @return Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function meta()
    {
        return $this->morphMany(Meta::class, 'metable');
    }
}