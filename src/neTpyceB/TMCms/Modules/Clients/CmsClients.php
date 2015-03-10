<?
namespace neTpyceB\TMCms\Modules\Clients;

use neTpyceB\TMCms\Admin\Menu;
use neTpyceB\TMCms\Files\FileSystem;
use neTpyceB\TMCms\HTML\BreadCrumbs;
use neTpyceB\TMCms\HTML\Cms\CmsForm;
use neTpyceB\TMCms\HTML\Cms\CmsTable;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnActive;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnData;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnDelete;
use neTpyceB\TMCms\HTML\Cms\Column\ColumnEdit;
use neTpyceB\TMCms\HTML\Cms\Columns;
use neTpyceB\TMCms\HTML\Cms\Element\CmsButton;
use neTpyceB\TMCms\HTML\Cms\Element\CmsHtml;
use neTpyceB\TMCms\HTML\Cms\Element\CmsInputPassword;
use neTpyceB\TMCms\HTML\Cms\Element\CmsInputText;
use neTpyceB\TMCms\HTML\Cms\Element\CmsRow;
use neTpyceB\TMCms\HTML\Cms\Element\CmsSelect;
use neTpyceB\TMCms\HTML\Cms\Filter\Select;
use neTpyceB\TMCms\HTML\Cms\Filter\Text;
use neTpyceB\TMCms\HTML\Cms\FilterForm;
use neTpyceB\TMCms\HTML\Cms\Widget\FileManager;
use neTpyceB\TMCms\Modules\Clients\Object\Client;
use neTpyceB\TMCms\Modules\Clients\Object\Collection;
use neTpyceB\TMCms\Modules\Clients\Object\Group;
use neTpyceB\TMCms\Modules\Clients\Object\GroupCollection;

defined('INC') or exit;

Menu::getInstance()
    ->addSubMenuItem('groups', 'Аккаунты')
;

class CmsClients
{


    /** Clients */

    public static function _default()
    {
        $client_groups = new Group;

        echo CmsTable::getInstance()
            ->addDataSql('SELECT * FROM `' . ModuleClients::$tables['clients'] . '` ORDER BY `login`')
            ->addColumn(ColumnData::getInstance('login')->enableOrderableColumn())
            ->addColumn(ColumnEdit::getInstance('edit')->href('?p=' . P . '&do=edit&id={%id%}')->width('1%')->value('edit'))
            ->addColumn(ColumnActive::getInstance('active')->href('?p=' . P . '&do=_active&id={%id%}')->enableOrderableColumn())
            ->addColumn(ColumnDelete::getInstance()->href('?p=' . P . '&do=_delete&id={%id%}'))
            ->attachFilterForm(
                FilterForm::getInstance()
                    ->setCaption('<a href="?p=' . P . '&do=add">Add Client</a>')
                    ->addFilter('Group', Select::getInstance('group_id')->setOptions(array(-1 => 'All') + $client_groups->getPairs())->ignoreValue(-1))
                    ->addFilter('Login', Text::getInstance('login')->actAs('like'))
            );
    }

    private static function __clients_add_edit_form()
    {
        $client_groups = new Group;

        return CmsForm::getInstance()
            ->addField('Group', CmsSelect::getInstance('group_id')->setOptions($client_groups->getPairs()))
            ->addField('Login', CmsInputText::getInstance('login'))
            ->addField('Password', CmsInputPassword::getInstance('password')->reveal(true)->help('Leave empty to keep current'));
    }

    public static function add()
    {
        echo self::__clients_add_edit_form()
            ->setAction('?p=' . P . '&do=_add')
            ->setSubmitButton(new CmsButton('Add'));
    }

    public static function edit()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $data = q_assoc_row('SELECT * FROM `' . ModuleClients::$tables['clients'] . '` WHERE `id` = "' . $id . '"');
        unset ($data['password']);

        echo self::__clients_add_edit_form()
            ->addData($data)
            ->setAction('?p=' . P . '&do=_edit&id=' . $id)
            ->setSubmitButton(new CmsButton('Update'));
    }

    public static function _add()
    {
        if (!$_POST) return;

        $_POST['password'] = ModuleClients::generateHash($_POST['password']);

        $client = new Client();
        $client->loadDataFromArray($_POST);
        $client->save();

        go('?p=' . P . '&highlight=' . $client->getId());
    }

    public static function _edit()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        if ($_POST['password']) {
            $_POST['password'] = ModuleClients::generateHash($_POST['password']);
        } else {
            unset($_POST['password']);
        }

        $client = new Client($id);
        $client->loadDataFromArray($_POST);
        $client->save();

        go('?p=' . P . '&highlight=' . $id);
    }

    public static function _active()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $group = new Client($id);
        $group->flipBoolValue('active');
        $group->save();

        go(REF);
    }

    public static function _delete()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $group = new Client($id);
        $group->deleteObject();

        go(REF);
    }



    /** Groups */

    public static function groups()
    {
        echo CmsTable::getInstance()
            ->addDataSql('
SELECT
	`g`.`id`,
	`d`.`' . LNG . '` AS `title`,
	`g`.`active`,
	`g`.`default`,
(SELECT COUNT(*) FROM `'. ModuleClients::$tables['collections'] .'` AS `l` WHERE `l`.`group_id` = `g`.`id`) AS `collection_count`
FROM `' . ModuleClients::$tables['groups'] . '` AS `g`
JOIN `cms_dstrings` AS `d` ON `d`.`id` = `g`.`title`
ORDER BY `g`.`title`
		')
            ->addColumn(ColumnData::getInstance('title')->enableOrderableColumn())
            ->addColumn(ColumnData::getInstance('collection_count')->enableOrderableColumn()->width('1%')->title('Collections')->align('right')->href('?p='. P .'&do=collections&group_id={%id%}'))
            ->addColumn(ColumnActive::getInstance('default')->href('?p='. P .'&do=_groups_default&id={%id%}'))
            ->addColumn(ColumnEdit::getInstance('edit')->href('?p=' . P . '&do=groups_edit&id={%id%}')->width('1%')->value('edit'))
            ->addColumn(ColumnActive::getInstance('active')->href('?p=' . P . '&do=_groups_active&id={%id%}')->enableOrderableColumn())
            ->addColumn(ColumnDelete::getInstance()->href('?p=' . P . '&do=_groups_delete&id={%id%}'))
            ->attachFilterForm(
                FilterForm::getInstance()->setCaption('<a href="?p=' . P . '&do=groups_add">Add Group</a>')
                    ->addFilter('Title', Text::getInstance('title')->actAs('like'))
            );
    }

    private static function __groups_add_edit_form()
    {
        return CmsForm::getInstance()
            ->addField('Title', CmsInputText::getInstance('title')->multilng(1));
    }

    public static function groups_add()
    {
        echo self::__groups_add_edit_form()
            ->setAction('?p=' . P . '&do=_groups_add')
            ->setSubmitButton(new CmsButton('Add'));
    }

    public static function groups_edit()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        echo self::__groups_add_edit_form()
            ->addData(q_assoc_row('SELECT `title` FROM `' . ModuleClients::$tables['groups'] . '` WHERE `id` = "' . $id . '"'))
            ->setAction('?p=' . P . '&do=_groups_edit&id=' . $id)
            ->setSubmitButton(new CmsButton('Update'));
    }

    public static function _groups_add()
    {
        $group = new Group();
        $group->loadDataFromArray($_POST);
        $group->save();

        go('?p=' . P . '&do=groups&highlight=' . $group->getId());
    }

    public static function _groups_edit()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $group = new Group();
        $group->loadDataFromDB($id);
        $group->loadDataFromArray($_POST);
        $group->save();

        go('?p=' . P . '&do=groups&highlight=' . $id);
    }

    public static function _groups_default()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        // Set all groups to default = 0
        $groups_collection = new GroupCollection();
        $groups_collection->setIsNotDefault();
        $groups_collection->save();

        $group = new Group($id);
        $group->setIsDefault();
        $group->save();

        go(REF);
    }

    public static function _groups_delete()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $group = new Group();
        $group->setId($id);
        $group->deleteObject();

        go(REF);
    }

    public static function _groups_active()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $group = new Group();
        $group->setId($id);
        $group->flipBoolValue('active');
        $group->save();

        go(REF);
    }



    /** Collections */

    public static function collections() {
        if (!isset($_GET['group_id']) || !ctype_digit((string)$_GET['group_id'])) return;
        $group_id = &$_GET['group_id'];

        $group = new Group($group_id);
        $group->loadDataFromDB();

        echo Columns::getInstance()
                ->add(BreadCrumbs::getInstance()->addCrumb($group->getTitle(), '?p='. P .'&do=groups'))
                ->add('<a href="?p=' . P . '&do=collection_add&group_id='. $group_id .'">Add Collection</a>', ['align' => 'right'])
            . '<br>';

        $sql = 'SELECT
    `l`.`id`,
    `l`.`active`,
    `l`.`templates_count`,
	`d1`.`' . LNG . '` AS `title`
FROM `' . ModuleClients::$tables['collections'] . '` AS `l`
LEFT JOIN `cms_dstrings` AS `d1` ON `d1`.`id` = `l`.`title`
WHERE `l`.`group_id` = "'. $group_id .'"
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
        if (!isset($_GET['group_id']) || !ctype_digit((string)$_GET['group_id'])) return;
        $group_id = &$_GET['group_id'];

        $group = new Group($group_id);
        $group->loadDataFromDB();

        echo Columns::getInstance()
            ->add(BreadCrumbs::getInstance()->addCrumb($group->getTitle(), '?p='. P .'&do=groups'))
        ;

        echo self::__collections_add_edit_form()
            ->setSubmitButton('Add')
            ->setAction('?p='. P .'&do=_collection_add&group_id='. $group_id)
        ;
    }

    public static function collection_edit() {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $collection = new Collection($id);
        $collection->loadDataFromDB();

        $group = new Group($collection->getGroupId());
        $group->loadDataFromDB();

        echo Columns::getInstance()
            ->add(BreadCrumbs::getInstance()->addCrumb($group->getTitle(), '?p='. P .'&do=groups'))
        ;

        echo self::__collections_add_edit_form()
            ->setSubmitButton('Edit')
            ->setAction('?p='. P .'&do=_collection_edit&id='. $id)
            ->addData($collection->getAsArray())
        ;
    }

    public static function _collection_add()
    {
        if (!isset($_GET['group_id']) || !ctype_digit((string)$_GET['group_id'])) return;
        $group_id = &$_GET['group_id'];

        $group = new Group($group_id);
        $group->loadDataFromDB();


        $collection = new Collection();
        $collection->loadDataFromArray($_POST);
        $collection->setGroupId($group_id);
        $collection->save();

        go('?p=' . P . '&do=collections&group_id='. $group_id .'&highlight=' . $collection->getId());
    }

    public static function _collection_edit()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $collection = new Collection($id);
        $collection->loadDataFromArray($_POST);
        $collection->save();

        go('?p=' . P . '&do=collections&group_id='. $collection->getGroupId() .'&highlight=' . $id);
    }

    public static function _collection_active()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $group = new Collection($id);
        $group->flipBoolValue('active');
        $group->save();

        go(REF);
    }

    public static function _collection_delete()
    {
        if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) return;
        $id = &$_GET['id'];

        $group = new Collection($id);
        $group->deleteObject();

        go(REF);
    }


    /** Templates */

    public static function templates() {
        if (!isset($_GET['collection_id']) || !ctype_digit((string)$_GET['collection_id'])) return;
        $collection_id = &$_GET['collection_id'];

        $collection = new Collection($collection_id);

        $group = new Group($collection->getGroupId());

        $path = ModuleClients::getCollectionTemplatesPath($group->getId(), $collection->getId());


        echo Columns::getInstance()
            ->add(BreadCrumbs::getInstance()->addCrumb($group->getTitle(), '?p='. P .'&do=groups')->addCrumb($collection->getTitle(), '?p='. P .'&do=collections&group_id='. $group->getId()))
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