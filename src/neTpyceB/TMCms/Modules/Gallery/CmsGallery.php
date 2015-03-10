<?
namespace neTpyceB\TMCms\Modules\Gallery;

use neTpyceB\TMCms\Admin\Menu;
use neTpyceB\TMCms\Admin\Messages;
use neTpyceB\TMCms\DB\SQL;
use neTpyceB\TMCms\Files\FileSystem;
use neTpyceB\TMCms\HTML\BreadCrumbs;
use neTpyceB\TMCms\HTML\Cms\CmsForm;
use neTpyceB\TMCms\HTML\Cms\CmsFormHelper;
use neTpyceB\TMCms\HTML\Cms\CmsTable;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnActive;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnData;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnDelete;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnEdit;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnOrder;
use neTpyceB\TMCms\HTML\Cms\Columns;
use neTpyceB\TMCms\HTML\Cms\Element\CmsButton;
use neTpyceB\TMCms\HTML\Cms\Element\CmsHtml;
use neTpyceB\TMCms\HTML\Cms\Element\CmsInputText;
use neTpyceB\TMCms\HTML\Cms\Element\CmsSelect;
use neTpyceB\TMCms\HTML\Cms\Filter\Text;
use neTpyceB\TMCms\HTML\Cms\FilterForm;
use neTpyceB\TMCms\HTML\Cms\Widget\FileManager;
use neTpyceB\TMCms\Modules\Gallery\Object\Gallery;
use neTpyceB\TMCms\Modules\Gallery\Object\GalleryCategory;
use neTpyceB\TMCms\Modules\Images\ModuleImages;
use neTpyceB\TMCms\Modules\Images\Object\Image;
use neTpyceB\TMCms\Modules\Images\Object\ImageCollection;
use neTpyceB\TMCms\HTML\Cms\CmsGallery as AdminGallery;


defined('INC') or exit;

Menu::getInstance()
    ->addSubMenuItem('categories')
;

class CmsGallery
{


    /** gallery */

    public static function _default()
    {
        echo self::__gallery_add_edit_form()
            ->setAction('?p='. P .'&do=_gallery_add')
            ->setSubmitButton(new CmsButton('Create new gallery'))
        ;

        echo '<br><br>';

        $sql = '
SELECT
    `g`.`id`,
    `g`.`active`,
	`d1`.`' . LNG . '` AS `title`,
	`d2`.`' . LNG . '` AS `category`
FROM `' . ModuleGallery::$tables['galleries'] . '` AS `g`
LEFT JOIN `'. ModuleGallery::$tables['categories'] .'` AS `c` ON `c`.`id` = `g`.`category_id`
LEFT JOIN `cms_dstrings` AS `d1` ON `d1`.`id` = `g`.`title`
LEFT JOIN `cms_dstrings` AS `d2` ON `d2`.`id` = `c`.`title`
ORDER BY `g`.`order`
        ';

        echo CmsTable::getInstance()
            ->addDataSql($sql)
            ->addColumn(ColumnData::getInstance('title')->enableOrderableColumn())
            ->addColumn(ColumnData::getInstance('category')->enableOrderableColumn()->width('1%')->nowrap(true))
            ->addColumn(ColumnEdit::getInstance('edit')->href('?p=' . P . '&do=gallery_edit&id={%id%}')->width('1%')->value('edit'))
            ->addColumn(ColumnActive::getInstance('active')->href('?p=' . P . '&do=_gallery_active&id={%id%}')->enableOrderableColumn())
            ->addColumn(ColumnDelete::getInstance()->href('?p=' . P . '&do=_gallery_delete&id={%id%}'))
        ;
    }

    private static function __gallery_add_edit_form()
    {
        return CmsForm::getInstance()
            ->addField('Title', CmsInputText::getInstance('title')->enableMultiLng())
            ->addField('Category', CmsSelect::getInstance('category_id')->setOptions(ModuleGallery::getCategoryPairs()))
        ;
    }

    public static function add()
    {
        echo self::__gallery_add_edit_form()
            ->setAction('?p=' . P . '&do=_add')
            ->setSubmitButton(new CmsButton('Add'));
    }

    public static function gallery_edit()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = (int)$_GET['id'];

        $gallery = new Gallery($id);

        echo BreadCrumbs::getInstance()
            ->addCrumb($gallery->getTitle(), '?p='. P .'&highlight='. $gallery->getId())
            ->addCrumb('Images')
        ;

        echo self::__gallery_add_edit_form()
            ->addData(q_assoc_row('SELECT * FROM `' . ModuleGallery::$tables['galleries'] . '` WHERE `id` = "' . $id . '"'))
            ->setAction('?p=' . P . '&do=_gallery_edit&id=' . $id)
            ->setSubmitButton(new CmsButton('Update'));


        // Images

        // Get existing images in DB
        $image_collection = new ImageCollection;
        $image_collection->setWhereItemType('gallery');
        $image_collection->setWhereItemId($gallery->getId());
        $image_collection->setOrderByField('order');
        $images = $image_collection->getAsArrayOfObjectData();

        // Get images on disk
        $path = ModuleImages::getPathForItemImages('gallery', $gallery->getId());

        // Files in DB
        $existing_images_in_db = [];
        foreach ($images as $image) {
            /** @var Image $image */
            $existing_images_in_db[$image['id']] = $image['image'];
        }

        // Files on disk
        FileSystem::mkDir(DIR_BASE . $path);
        $existing_files = array_diff(scandir(DIR_BASE . $path), ['.', '..']);
        $existing_images_on_disk = [];
        foreach ($existing_files as $image) {
            /** @var string $image */
            $existing_images_on_disk[] = $path . $image;
        }

        // Find difference
        $diff_non_file_db = array_diff($existing_images_in_db, $existing_images_on_disk);
        $diff_new_files = array_diff($existing_images_on_disk, $existing_images_in_db);

        // Add new files
        foreach ($diff_new_files as $file_path) {
            $image = new Image;
            $image->setItemType('gallery');
            $image->setItemId($gallery->getId());
            $image->setImage($file_path);
            $image->setOrder(SQL::getNextOrder(ModuleImages::$tables['images'], 'order', 'item_type', 'gallery'));
            $image->save();
        }

        // Delete entries where no more files
        foreach ($diff_non_file_db as $id => $file_path) {
            $image = new Image($id);
            $image->deleteObject();
        }
        $image_collection->clearCollectionCache(); // Clear cache, because we may have deleted as few images

        echo  CmsForm::getInstance()
                ->addField('', CmsHtml::getInstance('images')->setWidget(FileManager::getInstance()->enablePageReloadOnClose()->path($path)))
            . '<br>' ;

        echo AdminGallery::getInstance($image_collection->getAsArrayOfObjectData())
            ->linkActive('_images_active')
            ->linkMove('_images_move')
            ->linkDelete('_images_delete')
            ->enableResizeProcessor()
            ->imageWidth(270)
            ->imageHeight(200)
        ;
    }

    public function _images_delete() {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = $_GET['id'];

        // Delete file
        $image = new Image($id);
        if (file_exists(DIR_BASE . $image->getImage())) unlink(DIR_BASE . $image->getImage());

        // Delete object from DB
        $image->deleteObject();

        // Show message to user
        Messages::getInstance()->setFlash('Image removed');

        back();
    }

    public function _images_move() {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = $_GET['id'];

        $image = new Image($id);
        $product_id = $image->getItemId();

        SQL::orderCat($id, ModuleImages::$tables['images'], $product_id, 'item_id', $_GET['direct']);

        // Show message to user
        Messages::getInstance()->setFlash('Images reordered');

        back();
    }


    public static function _add()
    {
        if (!$_POST) return;

        $_POST['password'] = ModuleGallery::generateHash($_POST['password']);

        $client = new Client();
        $client->loadDataFromArray($_POST);
        $client->save();

        go('?p=' . P . '&highlight=' . $client->getId());
    }

    public static function _gallery_add()
    {
        if (!$_POST) return;

        $gallery = new Gallery();
        $gallery->loadDataFromArray($_POST);
        $gallery->save();

        go('?p=' . P . '&highlight=' . $gallery->getId());
    }

    public static function _gallery_edit()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = (int)$_GET['id'];


        $client = new Gallery($id);
        $client->loadDataFromArray($_POST);
        $client->save();

        go('?p=' . P . '&highlight=' . $id);
    }

    public static function _gallery_active()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = (int)$_GET['id'];

        $Category = new Gallery($id);
        $Category->flipBoolValue('active');
        $Category->save();

        go(REF);
    }

    public static function _gallery_delete()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = (int)$_GET['id'];

        $Category = new Gallery($id);
        $Category->deleteObject();

        go(REF);
    }



    /** categories */

    public static function categories()
    {
        echo CmsTable::getInstance()
            ->addDataSql('
SELECT
	`g`.`id`,
	`d`.`' . LNG . '` AS `title`,
	`g`.`active`,
(SELECT COUNT(*) FROM `'. ModuleGallery::$tables['galleries'] .'` AS `l` WHERE `l`.`category_id` = `g`.`id`) AS `galleries`
FROM `' . ModuleGallery::$tables['categories'] . '` AS `g`
LEFT JOIN `cms_dstrings` AS `d` ON `d`.`id` = `g`.`title`
ORDER BY `g`.`order`
		')
            ->addColumn(ColumnData::getInstance('title')->enableOrderableColumn())
            ->addColumn(ColumnEdit::getInstance('edit')->href('?p=' . P . '&do=categories_edit&id={%id%}')->width('1%')->value('edit'))
            ->addColumn(ColumnData::getInstance('galleries')->align('right')->nowrap(true)->width('1%')) // TODO link to galleries with filter
            ->addColumn(ColumnOrder::getInstance('order')->href('?p=' . P . '&do=_categories_order&id={%id%}')->width('1%')->value('edit'))
            ->addColumn(ColumnActive::getInstance('active')->href('?p=' . P . '&do=_categories_active&id={%id%}')->enableOrderableColumn())
            ->addColumn(ColumnDelete::getInstance()->href('?p=' . P . '&do=_categories_delete&id={%id%}'))
            ->attachFilterForm(
                FilterForm::getInstance()->setCaption('<a href="?p=' . P . '&do=categories_add">Add New Category</a>')
                    ->addFilter('Title', Text::getInstance('title')->actAs('like'))
            );
    }

    private static function __categories_add_edit_form($data = [])
    {
        return CmsFormHelper::outputForm(ModuleGallery::$tables['categories'], [
            'dara' => $data,
            'fields' => [
                'title' => [
                    'multilng' => true
                ]
            ],
            'combine' => true,
            'unset' => ['order', 'active']
        ]);
    }

    public static function categories_add()
    {
        echo self::__categories_add_edit_form()
            ->setAction('?p=' . P . '&do=_categories_add')
            ->setSubmitButton(new CmsButton('Add'));
    }

    public static function categories_edit()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        echo self::__categories_add_edit_form()
            ->addData(q_assoc_row('SELECT `title` FROM `' . ModuleGallery::$tables['categories'] . '` WHERE `id` = "' . $id . '"'))
            ->setAction('?p=' . P . '&do=_categories_edit&id=' . $id)
            ->setSubmitButton(new CmsButton('Update'));
    }

    public static function _categories_add()
    {
        $category = new GalleryCategory();
        $category->loadDataFromArray($_POST);
        $category->setOrder(SQL::getNextOrder(ModuleGallery::$tables['categories']));
        $category->save();

        go('?p=' . P . '&do=categories&highlight=' . $category->getId());
    }

    public static function _categories_edit()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = (int)$_GET['id'];

        $Category = new GalleryCategory();
        $Category->loadDataFromDB($id);
        $Category->loadDataFromArray($_POST);
        $Category->save();

        go('?p=' . P . '&do=categories&highlight=' . $id);
    }

    public static function _categories_order()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = (int)$_GET['id'];

        SQL::order($id, ModuleGallery::$tables['categories'], $_GET['direct']);

        go(REF);
    }

    public static function _categories_delete()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = (int)$_GET['id'];

        $Category = new GalleryCategory();
        $Category->setId($id);
        $Category->deleteObject();

        go(REF);
    }

    public static function _categories_active()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = (int)$_GET['id'];

        $Category = new GalleryCategory();
        $Category->setId($id);
        $Category->flipBoolValue('active');
        $Category->save();

        go(REF);
    }



    /** Collections */

    public static function collections() {
        if (!isset($_GET['category_id']) || !ctype_digit((string)$_GET['category_id'])) return;
        $category_id = &$_GET['category_id'];

        $Category = new Category($category_id);
        $Category->loadDataFromDB();

        echo Columns::getInstance()
                ->add(BreadCrumbs::getInstance()->addCrumb($Category->getTitle(), '?p='. P .'&do=categories'))
                ->add('<a href="?p=' . P . '&do=collection_add&category_id='. $category_id .'">Add Collection</a>', ['align' => 'right'])
            . '<br>';

        $sql = 'SELECT
    `l`.`id`,
    `l`.`active`,
    `l`.`templates_count`,
	`d1`.`' . LNG . '` AS `title`
FROM `' . ModuleGallery::$tables['collections'] . '` AS `l`
LEFT JOIN `cms_dstrings` AS `d1` ON `d1`.`id` = `l`.`title`
WHERE `l`.`category_id` = "'. $category_id .'"
ORDER BY `l`.`title`';

        echo CmsTable::getInstance()
            ->addDataSql($sql)
            ->addColumn(ColumnData::getInstance('title')->enableOrderableColumn())
            ->addColumn(ColumnData::getInstance('templates_count')->enableOrderableColumn()->width('1%')->title('Templates')->align('right')->href('?p='. P .'&do=templates&collection_id={%id%}'))
            ->addColumn(ColumnEdit::getInstance('edit')->href('?p=' . P . '&do=collection_edit&id={%id%}')->width('1%')->value('edit'))
            ->addColumn(ColumnActive::getInstance('active')->href('?p=' . P . '&do=_collection_active&id={%id%}')->enableOrderableColumn())
            ->addColumn(ColumnDelete::getInstance()->href('?p=' . P . '&do=_collection_delete&id={%id%}'))
        ;
    }

    private static function __collections_add_edit_form()
    {
        return CmsForm::getInstance()
            ->addField('Title', CmsInputText::getInstance('title')->multilng(1));
    }

    public static function collection_add() {
        if (!isset($_GET['category_id']) || !ctype_digit((string)$_GET['category_id'])) return;
        $category_id = &$_GET['category_id'];

        $Category = new Category($category_id);
        $Category->loadDataFromDB();

        echo Columns::getInstance()
            ->add(BreadCrumbs::getInstance()->addCrumb($Category->getTitle(), '?p='. P .'&do=categories'))
        ;

        echo self::__collections_add_edit_form()
            ->setSubmitButton('Add')
            ->setAction('?p='. P .'&do=_collection_add&category_id='. $category_id)
        ;
    }

    public static function collection_edit() {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $collection = new Collection($id);
        $collection->loadDataFromDB();

        $Category = new Category($collection->getCategoryId());
        $Category->loadDataFromDB();

        echo Columns::getInstance()
            ->add(BreadCrumbs::getInstance()->addCrumb($Category->getTitle(), '?p='. P .'&do=categories'))
        ;

        echo self::__collections_add_edit_form()
            ->setSubmitButton('Edit')
            ->setAction('?p='. P .'&do=_collection_edit&id='. $id)
            ->addData($collection->getAsArray())
        ;
    }

    public static function _collection_add()
    {
        if (!isset($_GET['category_id']) || !ctype_digit((string)$_GET['category_id'])) return;
        $category_id = &$_GET['category_id'];

        $Category = new Category($category_id);
        $Category->loadDataFromDB();


        $collection = new Collection();
        $collection->loadDataFromArray($_POST);
        $collection->setCategoryId($category_id);
        $collection->save();

        go('?p=' . P . '&do=collections&category_id='. $category_id .'&highlight=' . $collection->getId());
    }

    public static function _collection_edit()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $collection = new Collection($id);
        $collection->loadDataFromArray($_POST);
        $collection->save();

        go('?p=' . P . '&do=collections&category_id='. $collection->getCategoryId() .'&highlight=' . $id);
    }

    public static function _collection_active()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $Category = new Collection($id);
        $Category->flipBoolValue('active');
        $Category->save();

        go(REF);
    }

    public static function _collection_delete()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $Category = new Collection($id);
        $Category->deleteObject();

        go(REF);
    }


    /** Templates */

    public static function templates() {
        if (!isset($_GET['collection_id']) || !ctype_digit((string)$_GET['collection_id'])) return;
        $collection_id = &$_GET['collection_id'];

        $collection = new Collection($collection_id);

        $Category = new Category($collection->getCategoryId());

        $path = ModuleGallery::getCollectionTemplatesPath($Category->getId(), $collection->getId());


        echo Columns::getInstance()
            ->add(BreadCrumbs::getInstance()->addCrumb($Category->getTitle(), '?p='. P .'&do=categories')->addCrumb($collection->getTitle(), '?p='. P .'&do=collections&category_id='. $Category->getId()))
        ;

        echo  CmsForm::getInstance()
            ->addField('', CmsHtml::getInstance('templates')->setWidget(FileManager::getInstance()->enablePageReloadOnClose()->path($path)))
        . '<br>' ;

        FileSystem::mkDir(DIR_BASE . $path);

        $files = array_diff(scandir(DIR_BASE . $path), ['.', '..']);
        // Set count in DB
        $collection->loadDataFromDB()->setField('templates_count', count($files))->save();

        // Echo list
        $form = CmsForm::getInstance();
        foreach ($files as $v) {
            $form->addField('', CmsHtml::getInstance($v)->value($v));
        }

        echo $form;
    }
}