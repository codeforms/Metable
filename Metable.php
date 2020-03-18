<?php
namespace CodeForms\Repositories\Meta;

use Illuminate\Database\Eloquent\Builder;
use CodeForms\Repositories\Meta\Meta;
/**
 * @version v1.5.20 18.03.2020 05:27
 * @package CodeForms\Repositories\Meta\Metable
 */
trait Metable
{
    /**
     * Bir nesne silindiğinde, nesneye ait
     * tüm metalar da beraberinde silinir
     * 
     * @return Illuminate\Database\Eloquent\Model
     */
    public static function bootMetable()
    {
        static::deleted(function (self $model) {
            $model->deleteMeta();
        });
    }

    /**
     * Bir nesne ile ilişkilendirilmiş tüm meta
     * kayıtlarını value ve key olarak dönderir
     * 
     * @since 1.5.5 : pluck() yerine select()
     * 
     * @return object
     */
    public function allMeta()
    {
        return collect($this->meta()->select('value', 'key')->get());
    }

    /**
     * Belirtilen anahtar(lar) ile alakalı meta
     * kaydı olup olmadığını sorgular.
     * 
     * $key değeri, tek bir string veya 
     * array içinde tanımlı string'ler de olabilir.
     * 
     * @example $author->hasMeta('biography');
     * @example $author->hasMeta(['biography', 'publisher']);
     *
     * @param  string $key 
     * 
     * @return boolean
     */
    public function hasMeta($key): bool
    {
        return (bool) $this->countMeta($key);
    }

    /**
     * Bir meta verisinin $key'e karşılık gelen
     * değerini dönderir 
     *
     * @param  string $key
     * 
     * @return object|null
     */
    public function getMeta($key)
    {
        return $this->rawMeta($key)->value;
    }

    /**
     * Bir meta verisinin veri tabanındaki
     * tüm sütun kaydını alır
     * 
     * @param  string $key
     * 
     * @return object|null
     */
    public function rawMeta($key)
    {
        return $this->meta()->where('key', $key)->first();
    }

    /**
     * Yeni meta ekleme veya güncelleme.
     * 
     * Varolan bir meta kaydı varsa günceller, yoksa
     * yeni bir meta kaydı oluşturur. $key değişkeni
     * bir array ise, çoklu ekleme için foreach kullanılır
     * ve $value değişkeni null bırakılır.
     * 
     * @param  string|array $key
     * @param  mixed        $value
     * 
     * @return object|bool
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
     * Yeni meta ekleme
     * 
     * Bu metot, bir nesne için aynı "key" adı ile 
     * birden fazla meta kaydı oluşturabilir. Veri türüne
     * veya bir projedeki kullanım şekline göre kullanışlı
     * olabilir. 
     * 
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
     * Bir model'a ait tüm meta kayıtlarını
     * object içine ekler
     * 
     * @param Builder $query
     * 
     * @example Post::withMeta()->get()
     *
     * @return object
     */
    public function scopeWithMeta(Builder $query)
    {
        return $query->with('meta');
    }

    /**
     * Meta verileri içinde arama işlemi
     * 
     * @param Builder $query
     * @param string $key
     * @param string $value
     * @param string $notation
     * 
     * @example Post::whereMeta('author')->get()
     * @example Post::whereMeta('author', 'Stephen King')->get()
     * @example Post::whereMeta('book', 'Ankara', 'publisher->cities')->get()
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
     * Bir nesnenin kayıtlı tüm meta sayısını
     * veya $key değişkenine göre toplam meta 
     * sayısını görüntüler.
     * 
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
     * Bir nesneye ait kayıtlı tüm meta verisini
     * veya $key - $value kıstasına göre kayıtlı olan
     * tüm meta verilerini silme işlemi.
     *
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
     * Yeni meta ekleme
     * 
     * Bu metodu, sadece saveMeta() ve addMeta() 
     * metodları kullanır. Diğer hallerde public 
     * erişime kapalıdır.
     * 
     * @param string $key
     * @param $value
     * @access private
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
     * Bir meta verisinin kaydedilme işlemi
     * 
     * Not: Bu metodu sadece setMeta() metodu kullanır.
     * Diğer hallerde bu metodun kullanımı tüm erişime
     * kapalıdır (private).
     * 
     * $value değişkeni boş olan tüm meta kayıtları,
     * veri tabanından her zaman silinir. Boş bir meta
     * kaydı oluşturulamaz.
     * 
     * @param $key
     * @param $value
     * @access private
     * 
     * @return bool
     */
    private function saveMeta($key, $value)
    {
        if(isset($value))
            return $this->hasMeta($key) ? $this->updateMeta($key, $value) : $this->createMeta($key, $value);

        return $this->deleteMeta($key);
    }

    /**
     * Meta verisini güncelleme
     * 
     * Bu metodu sadece saveMeta() metodu kullanır ve
     * public erişim için kapalıdır. Her metanın kayıt 
     * ve güncelleme işlemi setMeta() metodu ile yapılır.
     *
     * @param  string $key
     * @param  mixed $value
     * @access private
     * 
     * @return object|bool
     */
    private function updateMeta($key, $value)
    {
        if ($meta = $this->rawMeta($key))
            $meta->value = $value;
            $meta->save();
    }

    /**
     * morphMany ilişkisi
     *
     * @return object
     */
    public function meta()
    {
        return $this->morphMany(Meta::class, 'metable');
    }
}
