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

### Kullanım
Metable.php dosyasında metotlarla ilgili bilgileri comment'ler içinde bulabilirsiniz. 
Örnek bir PostController'da ;
```php
/**
 * setMeta() ile $post nesnesine, örneğin author bilgisini meta olarak kaydediyoruz.
 */
$post = Post::find($id);
$post->setMeta('author', 'Stephen King');
// ayrıca array olarak çoklu biçimde kaydedebiliriz
$post->setMeta([
	'author'    => 'Frank Schatzing',
	'book'      => 'Limit',
	'published' => '2009'
	'pages'     => '1328'
]);

/**
 * hasMeta() ile $post için örneğin author bilgisini sorgulayabiliriz. 
 * hasMeta her zaman bool olarak döner (true/false).
 */
$post->hasMeta('author');
 
/**
 * rawMeta() ile bir meta verisinin ($key'e göre) tüm sütun bilgilerini alırız.
 */
$post->rawMeta('author'); // metable_id, metable_type, key, value
 
/**
 * updateMeta() ile bir meta verisini güncelleyebiliriz.
 */
$post->updateMeta('author', 'Stephen King');
 
/**
 * whereMeta() ile bir nesneye ait meta verileri içinde belirtilen spesifik bir $value'yu arayabiliriz.
 * 'value' sütunu veri tabanında json formatında 
 * kaydedildiği için json'ın alt anahtarlarında
 * istenilen arama da yapılabilir (publisher->cities gibi).
 */
$post->whereMeta('author', 'Stephen King');
$post->whereMeta('book', 'Ankara', 'publisher->cities');
 
/**
 * deleteMeta() ile bir nesneye ait meta verilerini silebiliriz
 */
$post->deleteMeta(); // bir post'a ait tüm meta verileri siler
Post::deleteMeta(); // tüm Post verilerindeki bütün meta verilerini siler

$post->deleteMeta('author'); // belirli bir post'un 'author' meta verisini siler
Post::deleteMeta('author'); // tüm Post verilerindeki bütün 'author' meta verilerini siler

 
/**
 * countMeta() ile bir $key değerine göre toplam meta veri sayısını öğreniriz.
 * Bu işlemi $key içinde array kullanarak çoklu biçiminde de yapabiliriz.
 */
$post->countMeta(); // bir post verisine ait tüm meta sayısı
Post::countMeta(); // Post verilerindeki tüm metaların toplamı

$post->countMeta('author'); // bir post verisindeki tüm 'author' ($key) toplamı
Post::countMeta('author'); // Post verilerindeki tüm 'author' ($key) toplamı

// çoklu sayım (array)
$post->countMeta(['author', 'publisher']); 
Post::countMeta(['author', 'publisher']);
```
