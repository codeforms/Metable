<?php
namespace CodeForms\Repositories\Meta;

use Illuminate\Support\{Str, Arr};
use Illuminate\Database\Eloquent\Builder;
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
     * @param array $args
     * 
     * @return array
     */
    public function allMeta($args = []): array
    {
        $args += ['exceptions' => null, 'only' => null];

        return self::metaByKeys($args['only'], $args['exceptions']);
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
     * @return mixed
     */
    public function getMeta($key)
    {
        return self::hasMeta($key) ? self::rawMeta($key)->value : null;
    }

    /**
     * @param string|array $only
     * @param string|array $exceptions
     * 
     * @return array
     */
    private function metaByKeys($only = null, $exceptions = null): array
    {
        return $this->meta()->when(!is_null($only), function($query) use($only) {
            return $query->whereIn('key', (array)$only);
        })->when(!is_null($exceptions), function($query) use($exceptions) {
            return $query->whereNotIn('key', (array)$exceptions);
        })->get(['key', 'value'])->toArray();
    }

    /**
     * @param  string $key
     * 
     * @return object|null
     */
    public function rawMeta($key, $value = null)
    {
        return $this->meta()->where('key', $key)->orWhere('value', $value)->first();
    }

    /**
     * @param  string|array $key
     * @param  mixed        $value
     * 
     * @return object|null
     */
    public function setMeta($key, $value = null)
    {
        if(is_string($key))
            return self::saveMeta($key, $value);
        elseif(is_array($key))
            foreach ($key as $k => $v)
                self::setMeta($k, $v);
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
    public function countMeta($key = null): int
    {
        return collect(self::metaByKeys($key))->count();
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
            'key'   => Str::slug($key),
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
        if ($meta = self::rawMeta($key, $value))
            $meta->key   = Str::slug($key);
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