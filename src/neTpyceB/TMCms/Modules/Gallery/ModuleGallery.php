<?php
namespace neTpyceB\TMCms\Modules\Gallery;

use neTpyceB\TMCms\Admin\Messages;
use neTpyceB\TMCms\Admin\Users;
use neTpyceB\TMCms\DB\SQL;
use neTpyceB\TMCms\Files\FileSystem;
use neTpyceB\TMCms\HTML\BreadCrumbs;
use neTpyceB\TMCms\HTML\Cms\CmsForm;
use neTpyceB\TMCms\HTML\Cms\Columns;
use neTpyceB\TMCms\HTML\Cms\CmsGallery as GalleryHtml;
use neTpyceB\TMCms\HTML\Cms\Element\CmsHtml;
use neTpyceB\TMCms\HTML\Cms\Widget\FileManager;
use neTpyceB\TMCms\Modules\Gallery\Object\Gallery;
use neTpyceB\TMCms\Modules\Gallery\Object\GalleryCategoryCollection;
use neTpyceB\TMCms\Modules\Gallery\Object\GalleryCollection;
use neTpyceB\TMCms\Modules\Images\ModuleImages;
use neTpyceB\TMCms\Modules\Images\Object\Image;
use neTpyceB\TMCms\Modules\Images\Object\ImageCollection;
use neTpyceB\TMCms\Modules\IModule;
use neTpyceB\TMCms\Orm\Entity;

defined('INC') or exit;

class ModuleGallery implements IModule {
	/** @var $this */
	private static $instance;

	public static function getInstance() {
		if (!self::$instance) self::$instance = new self;
		return self::$instance;
	}

	public static $tables = array(
		'galleries' => 'm_gallery',
		'categories' => 'm_gallery_categories'
	);

    public static function getCategoryPairs() {
        $category_collection = new GalleryCategoryCollection();
        return $category_collection->getPairs('title');
    }

    public static function getGalleryImagesPath($id)
    {
        return DIR_PUBLIC_URL . 'galleries/images/'. $id .'/';
    }

    public static function getGalleryImages(Gallery $gallery = NULL)
    {
        $images_collection = new ImageCollection();
        $images_collection->setWhereItemType('gallery');
        if ($gallery) {
            $images_collection->setWhereItemId($gallery->getId());
        }

        return $images_collection->getAsArrayOfObjects();
    }

    public static function getViewForCmsModules(Entity $item) {
        ob_start();

        $class = strtolower(join('', array_slice(explode('\\', get_class($item)), -1)));

        // Get existing images in DB
        $image_collection = new ImageCollection;
        $image_collection->setWhereItemType($class);
        $image_collection->setWhereItemId($item->getId());
        $image_collection->addOrderByField();
        $images = $image_collection->getAsArrayOfObjectData();

        // Get images on disk
        $path = ModuleImages::getPathForItemImages($class, $item->getId());

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
            $image->setItemType($class);
            $image->setItemId($item->getId());
            $image->setImage($file_path);
            $image->setOrder(SQL::getNextOrder(ModuleImages::$tables['images'], 'order', 'item_type', $class));
            $image->save();
        }

        // Delete entries where no more files
        foreach ($diff_non_file_db as $id => $file_path) {
            $image = new Image($id);
            $image->deleteObject();
        }
        $image_collection->clearCollectionCache(); // Clear cache, because we may have deleted as few images

        echo Columns::getInstance()
            ->add(BreadCrumbs::getInstance()->addCrumb($item->getTitle(), '?p='. P .'&highlight='. $item->getId()))
        ;

        echo  CmsForm::getInstance()
                ->addField('', CmsHtml::getInstance('images')->setWidget(FileManager::getInstance()->enablePageReloadOnClose()->path($path)))
            . '<br>' ;

        echo GalleryHtml::getInstance($image_collection->getAsArrayOfObjectData(true))
            ->linkActive('_images_active')
            ->linkMove('_images_move')
            ->linkDelete('_images_delete')
            ->enableResizeProcessor()
            ->imageWidth(270)
            ->imageHeight(200)
        ;

        return ob_get_clean();
    }

    public static function orderImageForCmsModules($id, $direct) {
        $image = new Image($id);

        SQL::orderCat($id, ModuleImages::$tables['images'], $image->getItemId(), 'item_id', $direct);

        // Show message to user
        Messages::sendMessage('Images reordered');
    }

    public static function deleteImageForCmsModules($id) {
        // Delete file
        $image = new Image($id);
        // Delete object from DB
        $image->deleteObject();

        // Show message to user
        Messages::sendMessage('Image removed');
    }

    public static function getGalleryPairs()
    {
        $category_collection = new GalleryCollection();
        return $category_collection->getPairs('title');
    }
}