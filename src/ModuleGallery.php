<?php
declare(strict_types=1);

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
use TMCms\Modules\Gallery\Entity\GalleryEntity;
use TMCms\Modules\Gallery\Entity\GalleryEntityRepository;
use TMCms\Modules\Images\Entity\ImageEntity;
use TMCms\Modules\Images\Entity\ImageEntityRepository;
use TMCms\Modules\Images\ModuleImages;
use TMCms\Modules\IModule;
use TMCms\Orm\Entity;
use TMCms\Traits\singletonInstanceTrait;

\defined('INC') or exit;

/**
 * Class ModuleGallery
 * @package TMCms\Modules\Gallery
 */
class ModuleGallery implements IModule {
    use singletonInstanceTrait;

    /**
     * @param string $slug
     * @return GalleryCategoryEntity
     */
    public static function getCategoryBySlug($slug): GalleryCategoryEntity
    {
        /** @var GalleryCategoryEntity $category */
        $category = GalleryCategoryEntityRepository::findOneEntityByCriteria([
            'slug' => $slug,
            'active' => 1,
        ]);

        return $category;
    }

    /**
     * @param $id
     * @return string
     */
    public static function getGalleryImagesPath($id): string
    {
        return DIR_PUBLIC_URL . 'galleries/images/'. $id .'/';
    }

    /**
     * @param Entity $item
     * @return string
     */
    public static function getViewForCmsModules(Entity $item): string
    {
        ob_start();

        $entity_class = strtolower($item->getUnqualifiedShortClassName());

        // Images

        // Get existing images in DB
        $image_collection = new ImageEntityRepository;
        $image_collection->setGenerateOutputWithIterator(false);
        $image_collection->setWhereItemType($entity_class);
        $image_collection->setWhereItemId($item->getId());
        $image_collection->addOrderByField();

        $existing_images_in_db = (clone $image_collection)->getPairs('image');

        // Get images on disk
        $path = ModuleImages::getPathForItemImages($entity_class, $item->getId());

        $dir_Base_no_slash = rtrim(DIR_BASE, '/');

        // Files on disk
        FileSystem::mkDir($dir_Base_no_slash . $path);

        $existing_images_on_disk = [];
        foreach (array_diff(scandir($dir_Base_no_slash . $path, SCANDIR_SORT_NONE), ['.', '..']) as $image) {
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
            $image->setOrder(q_value('SELECT `order` FROM `' . $image->getDbTableName() . '` WHERE `item_type` = "' . sql_prepare($entity_class) . '" AND `item_id` = "' . $item->getId() . '" ORDER BY `order` DESC LIMIT 1') + 1);
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
                        ->setPath($path)
                    )
                )
            . '<br>';

        echo CmsGallery::getInstance($image_collection->getAsArrayOfObjectData(true));

        return ob_get_clean();
    }

    /**
     * @param $id
     * @param $direct
     */
    public static function orderImageForCmsModules($id, $direct) {
        $image = new ImageEntity($id);

        SQL::orderCat($id, $image->getDbTableName(), [$image->getItemId(), $image->getItemType()], ['item_id', 'item_type'], $direct);

        // Show message to user
        Messages::sendGreenAlert('Images reordered');
    }

    /**
     * @param $id
     */
    public static function activeImageForCmsModules($id)
    {
        $image = new ImageEntity($id);
        $image->flipBoolValue('active');
        $image->save();

        Messages::sendGreenAlert('Image updated');

        if (IS_AJAX_REQUEST) {
            die('1');
        }

        back();
    }

    /**
     * @param $id
     */
    public static function deleteImageForCmsModules($id) {
        // Delete file
        $image = new ImageEntity($id);
        // Delete object from DB
        $image->deleteObject();

        if (file_exists(DIR_BASE . $image->getImage())) {
            unlink(DIR_BASE . $image->getImage());
        }

        // Show message to user
        Messages::sendGreenAlert('Image removed');
    }

    /**
     * @param array $filters ['category_id' => 10]
     *
     * @return array
     */
    public static function getGalleryPairs(array $filters = []): array
    {
        $gallery_repository = new GalleryEntityRepository();
        $gallery_repository->addOrderByField('title');

        if (isset($filters['category_id'])) {
            $gallery_repository->setWhereCategoryId($filters['category_id']);
        }
        if (isset($filters['active'])) {
            $gallery_repository->setWhereActive($filters['active']);
        }

        return $gallery_repository->getPairs('title');
    }

    /**
     * @param Entity $gallery
     *
     * @return array
     */
    public static function getGalleryView($gallery): array
    {
        // Get gallery items
        $gallery_items = new GalleryEntityRepository();
        $gallery_items->setWhereActive(1);
        $gallery_items = $gallery_items->getAsArrayOfObjects();

        // Get gallery categories
        $gallery_categories = self::getCategoryPairs();
        $gallery_cat_classes = [];

        // Get gallery images
        $gallery_images = new ImageEntityRepository();
        $gallery_images->setWhereItemType($gallery);
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
        ob_start();
            /** @var GalleryEntity $item */
            foreach ($gallery_items as $item_id => $item):
            if (!isset($gallery_cat_classes[$item->getCategoryId()])) {
                continue;
            }
            ?>
                <?php $first_image = \array_search($item->getId(), $gallery_images, true); ?>
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

    /**
     * @return array
     */
    public static function getCategoryPairs(): array
    {
        $category_repository = new GalleryCategoryEntityRepository();
        $category_repository->addOrderByField('title');

        return $category_repository->getPairs('title');
    }

    /**
     * @param int $gallery_id
     *
     * @return array
     */
    public static function getImagesByGalleryId($gallery_id): array
    {
        $gallery = new GalleryEntity($gallery_id);

        return self::getGalleryImages($gallery);
    }

    /**
     * @param Entity $entity
     *
     * @param int    $limit
     *
     * @return array
     */
    public static function getGalleryImages($entity = NULL, int $limit = 0): array
    {
        $image_repository = new ImageEntityRepository();
        $image_repository->setWhereItemType($entity);
        $image_repository->setWhereActive(1);
        $image_repository->addOrderByField();

        if ($limit) {
            $image_repository->setLimit($limit);
        }

        if ($entity) {
            $image_repository->setWhereItemId($entity->getId());
        }

        return $image_repository->getAsArrayOfObjects();
    }
}
