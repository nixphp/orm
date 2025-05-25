<div style="text-align: center;">

![Logo](https://nixphp.github.io/docs/assets/nixphp-logo-small-square.png)

[![NixPHP ORM Plugin](https://github.com/nixphp/orm/actions/workflows/php.yml/badge.svg)](https://github.com/nixphp/orm/actions/workflows/php.yml)

</div>

[← Back to NixPHP](https://github.com/nixphp/framework)

---

# nixphp/orm

> **Minimalistic object mapper for your NixPHP application.**

This plugin adds basic ORM support to NixPHP:  
lightweight, readable, and ideal for small to medium use cases.

It supports nested entity saving (including pivot tables),  
auto-discovery of related entities, and lazy-loading on read.

> 🧩 Part of the official NixPHP plugin collection.  
> Use it if you want structured object handling – but without the complexity of full-stack ORM systems.

---

## 📦 Features

* ✅ Save any entity using `em()->save($entity)`
* ✅ Detects and stores relations automatically
* ✅ Supports `One-to-Many` and `Many-to-Many` out of the box
* ✅ Uses simple PHP classes, no annotations or metadata
* ✅ Includes lazy-loading via regular `getX()` methods
* ✅ Comes with a clean `AbstractRepository` for queries

---

## 📥 Installation

```bash
composer require nixphp/orm
````

You also need `nixphp/database` for PDO access.

---

## 🛠 Configuration

This plugin uses the shared PDO instance from [`nixphp/database`](https://github.com/nixphp/database).
Make sure your `/app/config.php` contains a working `database` section.

### Example: MySQL

```php
return [
    // ...
    'database' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'database' => 'myapp',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ]
];
```

### Example: SQLite

```php
return [
    // ...
    'database' => [
        'driver'   => 'sqlite',
        'database' => __DIR__ . '/../storage/database.sqlite',
    ]
];
```

Or for in-memory usage (great for testing):

```php
return [
    // ...
    'database' => [
        'driver'   => 'sqlite',
        'database' => ':memory:',
    ]
];
```

---

## 🧩 Usage

### Define your models

Models extend `AbstractModel` and use the `EntityTrait`.

```php
class Product extends AbstractModel
{
    protected ?int $id = null;
    protected string $name = '';
    protected ?Category $category = null;
    protected array $tags = [];

    public function getTags(): array
    {
        if ($this->tags === []) {
            $this->tags = (new TagRepository())->findByPivot(Product::class, $this->id);
        }
        return $this->tags;
    }

    public function getCategory(): ?Category
    {
        if ($this->category === null && $this->category_id) {
            $this->category = (new CategoryRepository())->findOneBy('id', $this->category_id);
        }
        return $this->category;
    }
}
```

### Saving data

```php
$category = (new CategoryRepository())->findOrCreateByName('Books');
$tagA     = (new TagRepository())->findOrCreateByName('Bestseller');
$tagB     = (new TagRepository())->findOrCreateByName('Limited');

$product = new Product();
$product->name = 'NixPHP for Beginners';
$product->addCategory($category);
$product->addTag($tagA);
$product->addTag($tagB);

em()->save($product);
```

### Reading data

```php
$product = (new ProductRepository())->findOneBy('id', 1);

echo $product->name;
print_r($product->getCategory());
print_r($product->getTags());
```

Relations are lazy-loaded automatically when accessed.

---

## 📚 Philosophy

This ORM is intentionally small and predictable.
It provides just enough structure to manage entities and relations –
without introducing complex abstractions or hidden behavior.

If you need validation, eager loading, event hooks, or advanced query building,
you can integrate any larger ORM of your choice alongside it.

---

## ✅ Requirements

* PHP >= 8.1
* `nixphp/framework` >= 1.0
* `nixphp/database`

---

## 📄 License

MIT License.
