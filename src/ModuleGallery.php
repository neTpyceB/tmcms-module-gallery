<?php

namespace TMCms\Modules\Gallery;

use TMCms\Admin\Messages;
use TMCms\Admin\Users;
use TMCms\DB\SQL;
use TMCms\Files\FileSystem;
use TMCms\HTML\BreadCrumbs;
use TMCms\HTML\Cms\CmsForm;
use TMCms\HTML\Cms\Columns;
use TMCms\HTML\Cms\CmsGallery as GalleryHtml;
use TMCms\HTML\Cms\Element\CmsHtml;
use TMCms\HTML\Cms\Widget\FileManager;
use TMCms\Modules\Gallery\Entity\GalleryCategoryEntityRepository;
use TMCms\Modules\Gallery\Entity\GalleryEntity;
use TMCms\Modules\Gallery\Entity\GalleryEntityRepository;
use TMCms\Modules\Images\ModuleImages;
use TMCms\Modules\Images\Entity\ImageEntity;
use TMCms\Modules\Images\Entity\ImageEntityRepository;
use TMCms\Modules\IModule;
use TMCms\Orm\Entity;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

class ModuleGallery implements IModule {
    use singletonInstanceTrait;

	public static $tables = array(
		'galleries' => 'm_gallery',
		'categories' => 'm_gallery_categories'
	);

    public static function getCategoryPairs() {
        $category_repository = new GalleryCategoryEntityRepository();
        $category_repository->addOrderByField('title');

        return $category_repository->getPairs('title');
    }

    public static function getGalleryImagesPath($id)
    {
        return DIR_PUBLIC_URL . 'galleries/images/'. $id .'/';
    }

    public static function getGalleryImages(GalleryEntity $gallery = NULL)
    {
        $image_repository = new ImageEntityRepository();
        $image_repository->setWhereItemType('gallery');

        if ($gallery) {
            $image_repository->setWhereItemId($gallery->getId());
        }

        return $image_repository->getAsArrayOfObjects();
    }

    public static function getViewForCmsModules(Entity $item) {
        ob_start();

        $class = strtolower(join('', array_slice(explode('\\', get_class($item)), -1)));

        // Get existing images in DB
        $image_collection = new ImageEntityRepository;
        $image_collection->setWhereItemType($class);
        $image_collection->setWhereItemId($item->getId());
        $image_collection->addOrderByField();
        $images = $image_collection->getAsArrayOfObjectData();

        // Get images on disk
        $path = ModuleImages::getPathForItemImages($class, $item->getId());

        // Files in DB
        $existing_images_in_db = [];
        foreach ($images as $image) {
            /** @var ImageEntity $image */
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
            $image = new ImageEntity;
            $image->setItemType($class);
            $image->setItemId($item->getId());
            $image->setImage($file_path);
            $image->setOrder(SQL::getNextOrder(ModuleImages::$tables['images'], 'order', 'item_type', $class));
            $image->save();
        }

        // Delete entries where no more files
        foreach ($diff_non_file_db as $id => $file_path) {
            $image = new ImageEntity($id);
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
        $image = new ImageEntity($id);

        SQL::orderCat($id, $image->getDbTableName(), $image->getItemId(), 'item_id', $direct);

        // Show message to user
        Messages::sendGreenAlert('Images reordered');
    }

    public static function deleteImageForCmsModules($id) {
        // Delete file
        $image = new ImageEntity($id);
        // Delete object from DB
        $image->deleteObject();

        // Show message to user
        Messages::sendGreenAlert('Image removed');
    }

    public static function getGalleryPairs()
    {
        $gallery_repository = new GalleryEntityRepository();
        $gallery_repository->addOrderByField('title');

        return $gallery_repository->getPairs('title');
    }
}