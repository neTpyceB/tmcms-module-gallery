<?php

namespace TMCms\Modules\Gallery;

use TMCms\Admin\Messages;
use TMCms\DB\SQL;
use TMCms\Files\FileSystem;
use TMCms\HTML\BreadCrumbs;
use TMCms\HTML\Cms\CmsForm;
use TMCms\HTML\Cms\CmsFormHelper;
use TMCms\HTML\Cms\CmsTable;
use TMCms\HTML\Cms\Column\ColumnActive;
use TMCms\HTML\Cms\Column\ColumnData;
use TMCms\HTML\Cms\Column\ColumnDelete;
use TMCms\HTML\Cms\Column\ColumnEdit;
use TMCms\HTML\Cms\Column\ColumnOrder;
use TMCms\HTML\Cms\Element\CmsButton;
use TMCms\HTML\Cms\Element\CmsHtml;
use TMCms\HTML\Cms\Element\CmsInputText;
use TMCms\HTML\Cms\Element\CmsSelect;
use TMCms\HTML\Cms\Widget\FileManager;
use TMCms\Modules\Gallery\Entity\GalleryCategoryEntity;
use TMCms\Modules\Gallery\Entity\GalleryCategoryEntityRepository;
use TMCms\Modules\Gallery\Entity\GalleryEntity;
use TMCms\Modules\Gallery\Entity\GalleryEntityRepository;
use TMCms\Modules\Images\ModuleImages;
use TMCms\Modules\Images\Entity\ImageEntity;
use TMCms\Modules\Images\Entity\ImageEntityRepository;
use TMCms\HTML\Cms\CmsGallery as AdminGallery;

defined('INC') or exit;

BreadCrumbs::getInstance()
    ->addCrumb(__('Galleries'))
;

class CmsGallery
{
    /** gallery */

    public static function _default()
    {
        BreadCrumbs::getInstance()
            ->addAction('Add Gallery', '?p=' . P . '&do=gallery_add')
        ;

        $galleries = new GalleryEntityRepository();
        $galleries->addOrderByField();

        $categories = new GalleryCategoryEntityRepository();

        echo CmsTable::getInstance()
            ->setHeadingTitle('Galleries')
            ->addData($galleries)
            ->addColumn(ColumnData::getInstance('title')
                ->enableOrderableColumn()
                ->enableTranslationColumn()
            )
            ->addColumn(ColumnData::getInstance('category_id')
                ->enableOrderableColumn()
                ->enableNarrowWidth()
                ->disableNewlines()
                ->setPairedDataOptionsForKeys($categories->getPairs('title'))
            )
            ->addColumn(ColumnEdit::getInstance('edit')
                ->setHref('?p=' . P . '&do=gallery_edit&id={%id%}')
                ->enableNarrowWidth()
                ->setValue('edit')
            )
            ->addColumn(ColumnOrder::getInstance('order')
                ->setHref('?p=' . P . '&do=_gallery_order&id={%id%}')
            )
            ->addColumn(ColumnActive::getInstance('active')
                ->setHref('?p=' . P . '&do=_gallery_active&id={%id%}')
                ->enableOrderableColumn()
            )
            ->addColumn(ColumnDelete::getInstance()
                ->setHref('?p=' . P . '&do=_gallery_delete&id={%id%}')
            )
        ;
    }

    public static function gallery_add()
    {
        echo self::__gallery_add_edit_form()
            ->setAction('?p=' . P . '&do=_gallery_add')
            ->setSubmitButton(new CmsButton(__('Next ->')));
    }

    private static function __gallery_add_edit_form()
    {
        return CmsForm::getInstance()
            ->addField('Title', CmsInputText::getInstance('title')
                ->enableTranslation()
            )
            ->addField('Category', CmsSelect::getInstance('category_id')
                ->setOptions(ModuleGallery::getCategoryPairs())
            );
    }

    public static function gallery_edit()
    {
        $id = (int)$_GET['id'];

        $gallery = new GalleryEntity($id);

        BreadCrumbs::getInstance()
            ->addCrumb($gallery->getTitle(), '?p='. P .'&highlight='. $gallery->getId())
            ->addCrumb('Images')
        ;

        echo self::__gallery_add_edit_form()
            ->addData($gallery)
            ->setAction('?p=' . P . '&do=_gallery_edit&id=' . $id)
            ->setSubmitButton(new CmsButton(__('Update')));


        // Images

        // Get existing images in DB
        $image_collection = new ImageEntityRepository;
        $image_collection->setWhereItemType('gallery');
        $image_collection->setWhereItemId($gallery->getId());
        $image_collection->addOrderByField();

        $existing_images_in_db = $image_collection->getPairs('image');

        // Get images on disk
        $path = ModuleImages::getPathForItemImages('gallery', $gallery->getId());

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
            $image->setItemType('gallery');
            $image->setItemId($gallery->getId());
            $image->setImage($file_path);
            $image->setOrder(SQL::getNextOrder($image->getDbTableName(), 'order', 'item_type', 'gallery'));
            $image->save();
        }

        // Delete entries where no more files
        foreach ($diff_non_file_db as $id => $file_path) {
            $image = new ImageEntity($id);
            $image->deleteObject();
        }

        $image_collection->clearCollectionCache(); // Clear cache, because we may have deleted as few images

        echo  CmsForm::getInstance()
                ->addField('', CmsHtml::getInstance('images')
                    ->setWidget(FileManager::getInstance()
                        ->enablePageReloadOnClose()
                        ->path($path)
                    )
                )
            . '<br>' ;

        echo AdminGallery::getInstance($image_collection->getAsArrayOfObjectData(true))
            ->linkActive('_images_active')
            ->linkMove('_images_move')
            ->linkDelete('_images_delete')
            ->enableResizeProcessor()
            ->imageWidth(270)
            ->imageHeight(200)
        ;
    }

    public static function _gallery_add()
    {
        $gallery = new GalleryEntity();
        $gallery->loadDataFromArray($_POST);
        $gallery->setOrder(SQL::getNextOrder($gallery->getDbTableName(), 'order', 'category_id', $gallery->getCategoryId()));
        $gallery->save();


        go('?p=' . P . '&do=gallery_edit&id=' . $gallery->getId());
    }

    public static function _gallery_edit()
    {
        $id = (int)$_GET['id'];

        $client = new GalleryEntity($id);
        $client->loadDataFromArray($_POST);
        $client->save();

        go('?p=' . P . '&highlight=' . $id);
    }

    public static function _gallery_active()
    {
        $id = (int)$_GET['id'];

        $Category = new GalleryEntity($id);
        $Category->flipBoolValue('active');
        $Category->save();

        go(REF);
    }

    public static function _gallery_delete()
    {
        $id = (int)$_GET['id'];

        $Category = new GalleryEntity($id);
        $Category->deleteObject();

        go(REF);
    }

    /** categories */

    public static function categories()
    {
        $categories = new GalleryCategoryEntityRepository();
        $categories->addSimpleSelectFields(['id', 'title', 'active']);
        $categories->addOrderByField();

        $galleries = new GalleryEntityRepository();
        $categories->addSimpleSelectFieldsAsString('(SELECT COUNT(*) FROM `'. $galleries->getDbTableName() .'` AS `l` WHERE `l`.`category_id` = `'. $categories->getDbTableName() .'`.`id`) AS `galleries`');

        BreadCrumbs::getInstance()
            ->addAction('Add Category', '?p=' . P . '&do=categories_add')
        ;

        echo CmsTable::getInstance()
            ->setHeadingTitle('Categories')
            ->addData($categories)
            ->addColumn(ColumnData::getInstance('title')
                ->enableTranslationColumn()
                ->enableOrderableColumn()
            )
            ->addColumn(ColumnEdit::getInstance('edit')
                ->setHref('?p=' . P . '&do=categories_edit&id={%id%}')
                ->enableNarrowWidth()
                ->setValue('edit')
            )
            ->addColumn(ColumnData::getInstance('galleries')
                ->enableRightAlign()
                ->disableNewlines()
                ->enableNarrowWidth()
            )
            ->addColumn(ColumnData::getInstance('slug'))
            ->addColumn(ColumnOrder::getInstance('order')
                ->setHref('?p=' . P . '&do=_categories_order&id={%id%}')
                ->enableNarrowWidth()
                ->setValue('edit')
            )
            ->addColumn(ColumnActive::getInstance('active')
                ->setHref('?p=' . P . '&do=_categories_active&id={%id%}')
                ->enableOrderableColumn()
            )
            ->addColumn(ColumnDelete::getInstance()
                ->setHref('?p=' . P . '&do=_categories_delete&id={%id%}')
            );
    }

    public static function categories_add()
    {
        BreadCrumbs::getInstance()
            ->addCrumb(__('Add category'));

        echo self::__categories_add_edit_form()
            ->setAction('?p=' . P . '&do=_categories_add')
            ->setSubmitButton(new CmsButton('Add'));
    }

    private static function __categories_add_edit_form($data = [])
    {
        $category = new GalleryCategoryEntity();

        return CmsFormHelper::outputForm($category->getDbTableName(), [
            'dara' => $data,
            'title' => $data ? __('Edit category') : __('Add category'),
            'fields' => [
                'slug' => [
                    'hint' => 'For code reference only',
                ],
                'title' => [
                    'translation' => true,
                ]
            ],
            'combine' => true,
            'unset' => [
                'order',
                'active'
            ]
        ]);
    }

    public static function categories_edit()
    {

        $id = (int)$_GET['id'];

        $category = new GalleryCategoryEntity($id);

        BreadCrumbs::getInstance()
            ->addCrumb($category->getTitle())
        ;

        echo self::__categories_add_edit_form()
            ->addData($category)
            ->setFormTitle('Edit category')
            ->setAction('?p=' . P . '&do=_categories_edit&id=' . $id)
            ->setSubmitButton(new CmsButton('Update'))
        ;
    }

    public static function _categories_add()
    {
        $category = new GalleryCategoryEntity();
        $category->loadDataFromArray($_POST);
        $category->setOrder(SQL::getNextOrder($category->getDbTableName()));
        $category->save();

        go('?p=' . P . '&do=categories&highlight=' . $category->getId());
    }

    public static function _categories_edit()
    {
        $id = (int)$_GET['id'];

        $Category = new GalleryCategoryEntity($id);
        $Category->loadDataFromArray($_POST);
        $Category->save();

        go('?p=' . P . '&do=categories&highlight=' . $id);
    }

    public static function _categories_order()
    {
        $id = (int)$_GET['id'];

        $category = new GalleryCategoryEntity();

        SQL::order($id, $category->getDbTableName(), $_GET['direct']);

        go(REF);
    }

    public static function _gallery_order()
    {
        $id = (int)$_GET['id'];

        $gallery = new GalleryEntity();

        SQL::order($id, $gallery->getDbTableName(), $_GET['direct']);

        go(REF);
    }

    public static function _categories_delete()
    {
        $id = (int)$_GET['id'];

        $Category = new GalleryCategoryEntity($id);
        $Category->deleteObject();

        go(REF);
    }

    public static function _categories_active()
    {
        $id = (int)$_GET['id'];

        $Category = new GalleryCategoryEntity($id);
        $Category->flipBoolValue('active');
        $Category->save();

        go(REF);
    }

    public function _images_delete()
    {
        $id = $_GET['id'];

        // Delete file
        $image = new ImageEntity($id);
        if (file_exists(DIR_BASE . $image->getImage())) {
            unlink(DIR_BASE . $image->getImage());
        }

        // Delete object from DB
        $image->deleteObject();

        // Show message to user
        Messages::sendGreenAlert('Image removed');

        back();
    }

    public function _images_move()
    {
        $id = $_GET['id'];

        $image = new ImageEntity($id);
        $product_id = $image->getItemId();

        SQL::orderCat($id, $image->getDbTableName(), $product_id, 'item_id', $_GET['direct']);

        // Show message to user
        Messages::sendGreenAlert('Images reordered');

        back();
    }
}