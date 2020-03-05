# Meta
Meta yapıları sayesinde, çoğu veriyi ekstra tablo veya sütunlarda tutmak yerine tek bir meta tablosuna kaydedebilir ve kolayca erişebiliriz. Bu yapı, Laravel için geliştirilmiş birçok meta paketine benzer bir yapıdadır. Meta verileri için tek bir tablo kullanır.

[![stable](http://badges.github.io/stability-badges/dist/stable.svg)](http://github.com/badges/stability-badges)

> Veri tabanı için gerekli tabloyu "/migrations" dizinindeki dosyada bulabilirsiniz.

### Kurulum

* /migrations dizininde yer alan dosyayı kullanarak meta veriler için gerekli veri tabanı tablosunu oluşturun
``` php artisan migrate```

* Son adım ise meta yapısını kullanmak istediğiniz model dosyanıza/dosyalarınıza Metable trait dosyasını ekleyin.
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
Örnek bir PostController'da önce veriyi tanımlayalım;
```php
$post = Post::find($id);
```
### setMeta()
setMeta() ile bir $post nesnesine, örneğin 'author' bilgisini meta olarak kaydedelim.
```php
$post->setMeta('author', 'Stephen King');

# ayrıca array olarak çoklu biçimde de kaydedebiliriz
$post->setMeta([
	'author'    => 'Frank Schatzing',
	'book'      => 'Limit',
	'published' => '2009'
	'pages'     => '1328'
]);

# son kaydettiğimiz meta verilerini görüntüleyelim
$post->getMeta('author'); // Frank Schatzing
$post->getMeta('book'); // Limit
```
### addMeta()
Bu metot, bir nesne için aynı "key" adı ile birden fazla meta kaydı oluşturabilir. Veri türüne veya bir projedeki kullanım şekline göre kullanışlı olabilir.
> Bir veri türü için $key değişkeni tekil (unique) olacaksa, bu metot yerine setMeta() kullanılmalıdır. Bu metot veri veya proje türüne göre opsiyoneldir.
```php
$post->addMeta('author', 'Frank Schatzing');
```
### hasMeta()
hasMeta() ile 'author' bilgisini sorgulayalım. 
> hasMeta(), her zaman bool döner.
```php
$post->hasMeta('author');
```
### rawMeta()
rawMeta() ile bir meta verisinin ($key'e göre) tüm sütun bilgilerini alırız.
(metable_id, metable_type, key, value)
```php
$post->rawMeta('author');
```
### updateMeta()
updateMeta() ile bir meta verisini güncelleriz.
```php
$post->updateMeta('author', 'Stephen King');
```
### whereMeta()
whereMeta() ile meta verileri içinde belirtilen spesifik bir $value'yu arayabiliriz.
```php
Post::whereMeta([
	'key'   => 'author',
	'value' => 'Stephen King'
]);
```
'value' sütunu veri tabanında json formatında kaydedildiği için json'ın alt anahtarlarında da istenilen arama yapılabilir (publisher->cities gibi)
```php
Post::whereMeta([
	'key'      => 'book',
	'value'    => 'Ankara'
	'notation' => 'publisher->cities'
]);
```
### deleteMeta()
deleteMeta() ile bir nesneye ait meta verilerini sileriz
```php
$post->deleteMeta(); // bir post'a ait tüm meta verileri siler
Post::deleteMeta(); // tüm Post verilerindeki bütün meta verilerini siler

# belirli bir post'un 'author' meta verilerini siler
$post->deleteMeta('author');
# belirli bir post'un 'key / value' değişkenine göre siler
$post->deleteMeta('author', 'Stephen King');

# tüm Post verilerindeki bütün 'author' meta verilerini siler
Post::deleteMeta('author');
# tüm Post verilerindeki bütün 'key / value' 
# değişkenine uyan meta verilerini siler
Post::deleteMeta('author', 'Stephen King');
```
### countMeta()
countMeta() ile bir $key değerine göre toplam meta veri sayısını öğreniriz. Bu işlemi $key içinde array kullanarak çoklu biçiminde de yapabiliriz.
```php
$post->countMeta(); // bir post verisine ait tüm meta sayısı
Post::countMeta(); // Post verilerindeki tüm metaların toplamı

$post->countMeta('author'); // bir post verisindeki tüm 'author' ($key) toplamı
Post::countMeta('author'); // Post verilerindeki tüm 'author' ($key) toplamı

// çoklu sayım (array)
$post->countMeta(['author', 'publisher']); 
Post::countMeta(['author', 'publisher']);
```