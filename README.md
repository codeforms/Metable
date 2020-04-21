# Metable
[![GitHub license](https://img.shields.io/github/license/codeforms/Metable)](https://github.com/codeforms/Metable/blob/master/LICENSE)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/codeforms/Metable)
[![stable](http://badges.github.io/stability-badges/dist/stable.svg)](http://github.com/badges/stability-badges)

### Kurulum
* Meta veriler için gerekli veri tabanı tablosunu oluşturun
``` php artisan migrate```

* Meta yapısını kullanmak istediğiniz model dosyanıza/dosyalarınıza Metable trait dosyasını ekleyin.
```php
<?php
namespace App;

use CodeForms\Repositories\Meta\Metable; // kendi yapınıza göre namespace'i değiştirin
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
  use Metable;
}
```

# Kullanım
```php
$post = Post::find($id);
```
### setMeta()
Varolan bir meta kaydı varsa günceller, yoksa yeni bir meta kaydı oluşturur.
```php
$post->setMeta('author', 'Stephen King');

# ayrıca array olarak çoklu biçimde de kaydedebiliriz
$post->setMeta([
	'author'    => 'Frank Schatzing',
	'book'      => 'Limit',
	'published' => '2009'
	'pages'     => '1328'
]);
```
### getMeta()
Kayıtlı olan meta değerlerini görüntülemek için kullanılır.
```php
# son kaydettiğimiz 'author' ve 'book' meta verilerini görüntüleyelim
$post->getMeta('author'); // Frank Schatzing
$post->getMeta('book'); // Limit
```
### addMeta()
Bu metot, bir nesne için aynı "key" adı ile birden fazla meta kaydı oluşturabilir. Veri türüne veya bir projedeki kullanım şekline göre kullanışlı olabilir.
> Bir veri türü için $key değişkeni tekil (unique) olacaksa, bu metot yerine setMeta() kullanılmalıdır. Bu metot veri veya proje türüne göre opsiyoneldir.
```php
$post->addMeta('author', 'Frank Schatzing');
$post->addMeta('author', 'Stephen King');
```
### metaByKeys()
Bu metot, tek bir string veya array içinde belirtilmiş birden fazla string'lere ($key) göre tüm meta değerlerini object olarak dönderir.
> addMeta() metoduyla kaydedilmiş aynı key değerine sahip tüm metalar da bu metotla alınabilir. 
```php
$post->metaByKeys('author');
$post->metaByKeys(['author', 'book']);
```
### hasMeta()
Belirtilen anahtarla ($key) alakalı meta kaydını sorgular.
> hasMeta(), her zaman bool döner. $key değeri, tek bir string veya array içinde tanımlı string'ler de olabilir.
```php
$post->hasMeta('author');
$post->hasMeta(['biography', 'author'])
```
### rawMeta()
rawMeta() metodu sayesinde bir meta verisinin ($key'e göre) tüm sütun bilgileri alınır.
(metable_id, metable_type, key, value)
```php
$post->rawMeta('author');
```
### whereMeta()
whereMeta() ile meta verileri içinde belirtilen spesifik bir $value'yu arayabiliriz.
```php
Post::whereMeta('author', 'Stephen King')->get();
```
> 'value' sütunu veri tabanında json formatında kaydedildiği için json'ın alt anahtarlarında da istenilen arama yapılabilir (publisher->cities gibi)
```php
Post::whereMeta('book', 'Ankara', 'publisher->cities')->get()
```
### allMeta()
Bir nesne ile ilişkilendirilmiş tüm meta kayıtlarını value ve key olarak dönderir
```php
$post->allMeta();
```
### withMeta()
Bir model'a ait tüm meta kayıtlarını object içine ekler
```php
Post::withMeta()->get(); // veya Post::with('meta')->get();
```
### deleteMeta()
deleteMeta() metodu sayesinde bir nesneye ait tüm meta verisini veya $key - $value kıstasına göre tüm meta verilerini silebiliriz
```php
$post->deleteMeta(); // bir post'a ait tüm meta verileri siler
$post->deleteMeta('author'); // bir post'a ait 'author' meta verilerini siler
$post->deleteMeta('author', 'Stephen King'); // bir post'a ait meta verilerini, 'key / value' değişkenine göre siler
```
### countMeta()
countMeta() metodu sayesinde bir nesnenin sahip olduğu tüm meta sayısını veya $key değişkenine göre toplam meta sayısını görüntüler. Bu işlemi $key içinde array kullanarak çoklu biçiminde de yapabiliriz.
```php
$post->countMeta(); // bir post verisine ait tüm meta sayısı
$post->countMeta('author'); // bir post verisindeki tüm 'author' ($key) toplamı
$post->countMeta(['author', 'publisher']); // çoklu sayım (array)
```