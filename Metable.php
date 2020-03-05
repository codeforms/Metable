<?php
namespace CodeForms\Repositories\Meta;

use CodeForms\Repositories\Meta\Meta;
/**
 * @version v1.1.150 24.02.2020
 * @package CodeForms\Repositories\Meta\Metable
 */
trait Metable
{
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
     * Meta verileri için arama
     * 
     * @param array $args  : key, notation, value
     * 
     * @return object
     */
    public function whereMeta(array $args)
    {
        $args += [
            'key'      => null,
            'notation' => null,
            'value'    => null
        ];

        if($args['key'] and $args['value'])
            return $this->meta()->where('key', $args['key'])
                    ->whereJsonContains($jsonKey ? "value->{$args['notation']}" : "value", $args['value'])
                    ->get();
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
     * veya $key kıstasına göre kayıtlı olan
     * tüm meta verilerini silme işlemi.
     *
     * @param  string $key
     * 
     * @return bool
     */
    public function deleteMeta($key = null)
    {
        if($key)
            return $this->meta()->where('key', $key)->delete();

        return $this->meta()->delete();
    }

    /**
     * Yeni meta ekleme
     * 
     * Bu metodu, sadece saveMeta() metodu kullanır.
     * Diğer hallerde public erişime kapalıdır.
     * 
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
        {
            $meta->value = $value;

            return $meta->save();
        }

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
