<?php
namespace CodeForms\Repositories\Meta;

use Illuminate\Database\Eloquent\Builder;
use CodeForms\Repositories\Meta\Meta;
/**
 * @version v1.3.8 09.03.2020
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
     * Bir model ile ilişkilendirilmiş tüm meta
     * kayıtlarını value ve key olarak dönderir
     *
     * @return object
     */
    public function allMeta()
    {
        return collect($this->meta()->pluck('value', 'key'));
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
     * @return mixed
     */
    public function getMeta($key)
    {
        return $this->hasMeta($key) ? $this->rawMeta($key)->value : null;
    }

    /**
     * Bir meta verisinin veri tabanındaki
     * tüm sütun kaydını alır
     * 
     * @param  string $key
     * 
     * @return mixed
     */
    public function rawMeta($key)
    {
        if ($meta = $this->meta()->where('key', $key)->first())
            return $meta;

        return null;
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
        else
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
     * Meta verileri içinde arama işlemi
     * 
     * Model scope kullanarak Builder yardımıyla
     * meta içinde arama yapıyoruz. Böylece meta ile
     * ilişkili olan veriyi de object olarak alabiliriz.
     * 
     * @param Builder $query
     * @param string $key
     * @param string $value
     * 
     * @example Post::whereMeta('author', 'Stephen King')->get()
     * 
     * @return object
     */
    public function scopeWhereMeta(Builder $query, string $key, string $value = null)
    {
        return $query->whereHas('meta', function (Builder $query) use ($key, $value) 
            {
                $query->where('key', $key);
                $query->where('value', $value);
            }
        );
    }

    /**
     * Json türü meta verileri için arama işlemi
     * 
     * @param Builder $query
     * @param string $key
     * @param string $notation
     * @param string $value
     * 
     * @example Post::whereMeta('book', 'publisher->cities', 'Ankara')->get()
     * 
     * @return object
     */
    public function scopeWhereJsonMeta(Builder $query, string $key, string $notation, string $value = null)
    {
        return $query->whereHas('meta', function (Builder $query) use ($key, $notation, $value) 
            {
                $query->where('key', $key);
                $query->whereJsonContains("value->{$notation}", $value);
            }
        );
    }

    /**
     * Bir nesnenin kayıtlı tüm meta sayısını
     * veya $key değişkenine göre toplam meta 
     * sayısını görüntüler.
     * 
     * @param $key
     * 
     * @return integer
     */
    public function countMeta($key = null): int
    {
        if($key)
            return $this->meta()->whereIn('key', is_array($key) ? $key : [$key])->count();

         return $this->meta()->count();
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
        if($key)
            return $this->meta()->where('key', $key)->where('value', $value)->delete();

        return $this->meta()->delete();
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

            return $meta->save();

        return false;
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
