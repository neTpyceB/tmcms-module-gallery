<?php

namespace TMCms\Modules\Gallery;

use TMCms\Admin\Messages;
use TMCms\DB\SQL;
use TMCms\Files\FileSystem;
use TMCms\HTML\Cms\CmsForm;
use TMCms\HTML\Cms\CmsGallery;
use TMCms\HTML\Cms\Element\CmsHtml;
use TMCms\HTML\Cms\Widget\FileManager;
use TMCms\Modules\Gallery\Entity\GalleryCategoryEntity;
use TMCms\Modules\Gallery\Entity\GalleryCategoryEntityRepository;
use TMCms\Modules\Gallery\Entity\GalleryEntityRepository;
use TMCms\Modules\Images\ModuleImages;
use TMCms\Modules\Images\Entity\ImageEntity;
use TMCms\Modules\Images\Entity\ImageEntityRepository;
use TMCms\Modules\IModule;
use TMCms\Orm\Entity;
use TMCms\Strings\Converter;
use TMCms\Traits\singletonInstanceTrait;

defined('INC') or exit;

class ModuleGallery implements IModule {
    use singletonInstanceTrait;

    /**
     * @param string $slug
     * @return GalleryCategoryEntity
     */
    public static function getCategoryBySlug($slug)
    {
        /** @var GalleryCategoryEntity $category */
        $category = GalleryCategoryEntityRepository::findOneEntityByCriteria([
            'slug' => $slug,
            'active' => 1,
        ]);

        return $category;
    }

    public static function getGalleryImagesPath($id)
    {
        return DIR_PUBLIC_URL . 'galleries/images/'. $id .'/';
    }

    /**
     * @param null $gallery
     *
     * @return array
     */
    public static function getGalleryImages($gallery = NULL)
    {
        $entity_class = strtolower(Converter::classWithNamespaceToUnqualifiedShort($gallery));

        $image_repository = new ImageEntityRepository();
        $image_repository->setWhereItemType($entity_class);
        $image_repository->setWhereActive(1);

        if ($gallery) {
            $image_repository->setWhereItemId($gallery->getId());
        }

        return $image_repository->getAsArrayOfObjects();
    }

    public static function getViewForCmsModules(Entity $item) {
        ob_start();

        $entity_class = strtolower(Converter::classWithNamespaceToUnqualifiedShort($item));

        // Images

        // Get existing images in DB
        $image_collection = new ImageEntityRepository;
        $image_collection->setWhereItemType($entity_class);
        $image_collection->setWhereItemId($item->getId());
        $image_collection->addOrderByField();

        $existing_images_in_db = $image_collection->getPairs('image');

        // Get images on disk
        $path = ModuleImages::getPathForItemImages($entity_class, $item->getId());

        $dir_Base_no_slash = rtrim(DIR_BASE, '/');

        // Files on disk
        FileSystem::mkDir($dir_Base_no_slash . $path);

        $existing_images_on_disk = [];
        foreach (array_diff(scandir($dir_Base_no_slash . $path), ['.', '..']) as $image) {
            /** @var string $image */
            $existing_images_on_disk[] = $path . $image;
        }

        // Find difference
        $diff_non_file_db = array_diff($existing_images_in_db, $existing_images_on_disk);
        $diff_new_files = array_diff($existing_images_on_disk, $existing_images_in_db);

        // Add new files
        foreach ($diff_new_files as $file_path) {
            /** @var ImageEntity $image */
            $image = new ImageEntity;
            $image->setActive(1);
            $image->setItemType($entity_class);
            $image->setItemId($item->getId());
            $image->setImage($file_path);
            $image->setOrder(SQL::getNextOrder($image->getDbTableName(), 'order', 'item_type', $entity_class));
            $image->save();
        }

        // Delete entries where no more files
        foreach ($diff_non_file_db as $id => $file_path) {
            $image = new ImageEntity($id);
            $image->deleteObject();
        }

        $image_collection->addSimpleSelectFields(['id', 'image', 'active', 'order']);
        $image_collection->clearCollectionCache(); // Clear cache, because we may have deleted as few images

        echo  CmsForm::getInstance()
                ->addField('', CmsHtml::getInstance('images')
                    ->setWidget(FileManager::getInstance()
                        ->enablePageReloadOnClose()
                        ->path($path)
                    )
                )
            . '<br>';

        echo CmsGallery::getInstance($image_collection->getAsArrayOfObjectData(true))
            ->linkActive('_images_active')
            ->activeAjax(true)
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

    public static function getGalleryPairs($filters = [])
    {
        $gallery_repository = new GalleryEntityRepository();
        $gallery_repository->addOrderByField('title');

        if (isset($filters['category_id'])) {
            $gallery_repository->setWhereCategoryId($filters['category_id']);
        }

        return $gallery_repository->getPairs('title');
    }

    public static function getGalleryView($gallery)
    {
        $entity_class = strtolower(Converter::classWithNamespaceToUnqualifiedShort($gallery));

        // Get gallery items
        $gallery_items = new GalleryEntityRepository();
        $gallery_items->setWhereActive(1);
        $gallery_items = $gallery_items->getAsArrayOfObjects();

        // Get gallery categories
        $gallery_categories = ModuleGallery::getCategoryPairs();
        $gallery_cat_classes = [];

        // Get gallery images
        $gallery_images = new ImageEntityRepository();
        $gallery_images->setWhereItemType($entity_class);
        $gallery_images = $gallery_images->getPairs('item_id', 'image');

        // Gallery navigation
        ob_start(); ?>
        <ul id="filters" data-option-key="filter" class="nav nav-pills nav-pills-portfolio">
            <li class="active"><a href="#" data-toggle="pill" data-filter="*"><?= w('all'); ?></a></li>
            <?php foreach ($gallery_categories as $category_id => $category):
                ?>

            <?php
                $category_class = strtolower(htmlspecialchars(str_replace(' ','-',$category)));
                $gallery_cat_classes[$category_id] = $category_class;

                if ($gallery->getId() && $category_id != $gallery->getId()) {
                    continue;
                }
            ?>

            <li><a href="#" data-toggle="pill" data-filter=".<?= $category_class ?>"><?= htmlspecialchars($category) ?></a></li>

            <?php endforeach; ?>
        </ul>
        <?php $res['nav'] = ob_get_clean();

        // Gallery grid
        ob_start(); ?>
            <?php foreach ($gallery_items as $item_id => $item):
            if (!isset($gallery_cat_classes[$item->getCategoryId()])) {
                continue;
            }
            ?>
                <?php $first_image = array_search($item->getId(), $gallery_images); ?>
                <!-- PORTFOLIO ITEM 1 -->
                <div class="col-md-3 col-sm-3 small hp-wrapper element <?= $gallery_cat_classes[$item->getCategoryId()]; ?>">
                    <a href="<?= $item->getId() ?>" class="hover-shade"></a>
                    <a href="<?= $item->getId() ?>" class="top-link">
                        <img alt="" style="width: 220px; height: 160px;" src="<?= $first_image; ?>"></a>
                    <div class="bottom-block">
                        <a href="<?= $item->getId() ?>"><?= $item->getTitle(); ?></a>
                        <p><?= $gallery_categories[$item->getCategoryId()]; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php $res['grid'] = ob_get_clean();
        return $res;
    }

    public static function getCategoryPairs()
    {
        $category_repository = new GalleryCategoryEntityRepository();
        $category_repository->addOrderByField('title');

        return $category_repository->getPairs('title');
    }
}