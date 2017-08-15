<?php

namespace TMCms\Modules\Gallery;

use TMCms\Admin\Messages;
use TMCms\DB\SQL;
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
use TMCms\HTML\Cms\Element\CmsInputText;
use TMCms\HTML\Cms\Element\CmsSelect;
use TMCms\Modules\Gallery\Entity\GalleryCategoryEntity;
use TMCms\Modules\Gallery\Entity\GalleryCategoryEntityRepository;
use TMCms\Modules\Gallery\Entity\GalleryEntity;
use TMCms\Modules\Gallery\Entity\GalleryEntityRepository;
use TMCms\Modules\Images\Entity\ImageEntity;

defined('INC') or exit;

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
            ->setButtonSubmit(new CmsButton(__('Next ->')));
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
            ->setButtonSubmit(new CmsButton(__('Update')));


        echo ModuleGallery::getViewForCmsModules($gallery);
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
        $categories->addSimpleSelectFields(['id', 'title', 'active', 'slug']);
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
            ->addColumn(ColumnData::getInstance('galleries')
                ->enableRightAlign()
                ->disableNewlines()
                ->enableNarrowWidth()
            )
            ->addColumn(ColumnData::getInstance('slug'))
            ->addColumn(ColumnOrder::getInstance('order')
                ->setHref('?p=' . P . '&do=_categories_order&id={%id%}')
                ->enableNarrowWidth()
            )
            ->addColumn(ColumnEdit::getInstance('edit')
                ->setHref('?p=' . P . '&do=categories_edit&id={%id%}')
                ->enableNarrowWidth()
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
            ->setButtonSubmit(new CmsButton('Add'));
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
            ->setButtonSubmit(new CmsButton('Update'))
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

    public function _images_active()
    {
        $id = $_GET['id'];

        // Delete file
        $image = new ImageEntity($id);
        $image->flipBoolValue('active');
        $image->save();

        Messages::sendGreenAlert('Image updated');

        if (IS_AJAX_REQUEST) {
            die('1');
        }

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