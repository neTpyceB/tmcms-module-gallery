<?php
namespace neTpyceB\TMCms\Modules\Gallery;

use neTpyceB\TMCms\Admin\Users;
use neTpyceB\TMCms\Files\FileSystem;
use neTpyceB\TMCms\Modules\Clients\Object\Client;
use neTpyceB\TMCms\Modules\Clients\Object\Collection;
use neTpyceB\TMCms\Modules\Clients\Object\Offer;
use neTpyceB\TMCms\Modules\Gallery\Object\Gallery;
use neTpyceB\TMCms\Modules\Gallery\Object\GalleryCategory;
use neTpyceB\TMCms\Modules\Gallery\Object\GalleryCategoryCollection;
use neTpyceB\TMCms\Modules\Images\Object\ImageCollection;
use neTpyceB\TMCms\Modules\IModule;
use neTpyceB\TMCms\Modules\Templater\ModuleTemplater;
use \neTpyceB\TMCms\Strings\UID;

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

    public static function getGalleryImages(Gallery $gallery)
    {
        $images_collection = new ImageCollection();
        $images_collection->setWhereItemId('gallery');
        $images_collection->setWhereItemId($gallery->getId());

        return $images_collection->getAsArrayOfObjects();
    }
}