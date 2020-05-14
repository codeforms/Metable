# Metable
[![GitHub license](https://img.shields.io/github/license/codeforms/Metable)](https://github.com/codeforms/Metable/blob/master/LICENSE)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/codeforms/Metable)
[![stable](http://badges.github.io/stability-badges/dist/stable.svg)](https://github.com/codeforms/Metable/releases)

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
<?php
$post = Post::find($id);
```

### setMeta()
Varolan bir meta kaydı varsa günceller, yoksa yeni bir meta kaydı oluşturur.
```php
<?php
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
<?php
# son kaydettiğimiz 'author' ve 'book' meta verilerini görüntüleyelim
$post->getMeta('author'); // Frank Schatzing
$post->getMeta('book'); // Limit
```

### addMeta()
Bu metot, bir nesne için aynı "key" adı ile birden fazla meta kaydı oluşturabilir. Veri türüne veya bir projedeki kullanım şekline göre kullanışlı olabilir.
> Bir veri türü için $key değişkeni tekil (unique) olacaksa, bu metot yerine setMeta() kullanılmalıdır. Bu metot veri veya proje türüne göre opsiyoneldir.
```php
<?php
$post->addMeta('author', 'Frank Schatzing');
$post->addMeta('author', 'Stephen King');
```

### hasMeta()
Belirtilen anahtarla ($key) alakalı meta kaydını sorgular.
> hasMeta(), her zaman bool döner. $key değeri, tek bir string veya array içinde tanımlı string'ler de olabilir.
```php
<?php
$post->hasMeta('author');
$post->hasMeta(['biography', 'author'])
```

### rawMeta()
rawMeta() metodu sayesinde bir meta verisinin ($key'e göre) tüm sütun bilgileri alınır.
(metable_id, metable_type, key, value)
```php
<?php
$post->rawMeta('author');
```

### whereMeta()
whereMeta() ile meta verileri içinde belirtilen spesifik bir $value'yu arayabiliriz.
```php
<?php
Post::whereMeta('author', 'Stephen King')->get();
```
> 'value' sütunu veri tabanında json formatında kaydedildiği için json'ın alt anahtarlarında da istenilen arama yapılabilir (publisher->cities gibi)
```php
<?php
Post::whereMeta('book', 'Ankara', 'publisher->cities')->get()
```

### allMeta()
Bir nesne ile ilişkilendirilmiş tüm meta kayıtlarını value ve key olarak dönderir. Aynı zamanda aşağıdaki gibi bir ```only``` veya ```exceptions``` tanımlanarak metaları dilediğimiz şekilde alabiliriz.
> Önceki sürümlerde kullanılan ```metaByKeys()``` metodu yerine bu metot kullanılmalıdır. ```metaByKeys()``` metodu private yapılarak erişime kapatıldı. 
> addMeta() metoduyla kaydedilmiş aynı key değerine sahip tüm metalar da bu metotla alınabilir.
```php
<?php
# post için kayıtlı tüm metaları alır
$post->allMeta();

# sadece 'author' ve 'book' metalarını alır
$post->allMeta([
	'only' => ['author', 'book']
]);

# 'book' ve 'pages' metaları haricinde tüm metaları alır
$post->allMeta([
	'exceptions' => ['book', 'pages']
]);
```

### withMeta()
Bir model'a ait tüm meta kayıtlarını object içine ekler
```php
<?php
Post::withMeta()->get(); // veya Post::with('meta')->get();
```

### deleteMeta()
deleteMeta() metodu sayesinde bir nesneye ait tüm meta verisini veya $key - $value kıstasına göre tüm meta verilerini silebiliriz
```php
<?php
$post->deleteMeta(); // bir post'a ait tüm meta verileri siler
$post->deleteMeta('author'); // bir post'a ait 'author' meta verilerini siler
$post->deleteMeta('author', 'Stephen King'); // bir post'a ait meta verilerini, 'key / value' değişkenine göre siler
```

### countMeta()
countMeta() metodu sayesinde bir nesnenin sahip olduğu tüm meta sayısını veya $key değişkenine göre toplam meta sayısını görüntüler. Bu işlemi $key içinde array kullanarak çoklu biçiminde de yapabiliriz.
```php
<?php
$post->countMeta(); // bir post verisine ait tüm meta sayısı
$post->countMeta('author'); // bir post verisindeki tüm 'author' ($key) toplamı
$post->countMeta(['author', 'publisher']); // çoklu sayım (array)
```