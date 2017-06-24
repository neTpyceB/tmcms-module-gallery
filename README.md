# tmcms-module-gallery
Module Gallery for TMCms

Module adds image gallery functionality as separate module AND as possibility to auto-create internal galleries for every Entity presented in any other module, e.g. Products, Articles, etc.

# Usage for Entities

Create Gallery column

```php
// Data for table
...
$product = new ProductEntity();
$entity_class = strtolower(Converter::classWithNamespaceToUnqualifiedShort($product));

$images = new ImageEntityRepository();
$images->setWhereItemType($entity_class);
...
// Table Helper
...
'columns' => [
  ...,
  'gallery'    => [
    'type'   => 'gallery',
    'images' => $images,
  ],
  ...,
],
...
```
And add method for managing gallery images in `CmsClass`
```php
...
public function images()
{
  $product_id = ... // whatever you need
  $product = new ProductEntity($product_id);
  echo ModuleGallery::getViewForCmsModules(product);
}
```
