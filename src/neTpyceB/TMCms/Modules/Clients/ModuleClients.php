<?php
namespace neTpyceB\TMCms\Modules\Clients;

use neTpyceB\TMCms\Admin\Users;
use neTpyceB\TMCms\Files\FileSystem;
use neTpyceB\TMCms\Modules\Clients\Object\Client;
use neTpyceB\TMCms\Modules\Clients\Object\Collection;
use neTpyceB\TMCms\Modules\Clients\Object\Offer;
use neTpyceB\TMCms\Modules\IModule;
use neTpyceB\TMCms\Modules\Templater\ModuleTemplater;
use \neTpyceB\TMCms\Strings\UID;

defined('INC') or exit;

class ModuleClients implements IModule {
	/** @var $this */
	private static $instance;

	public static function getInstance() {
		if (!self::$instance) self::$instance = new self;
		return self::$instance;
	}

	public static $tables = array(
		'clients' => 'm_clients',
		'groups' => 'm_clients_groups',
		'collections' => 'm_clients_collections',
		'offers' => 'm_clients_offers',
		'offers_slides' => 'm_clients_offers_slides',
	);

	/**
	 * NEVER change in working sites. Only for new
	 * @var string
	 */
	private static $_password_salt = 'JG%^_&S45A';

	public static function getCollectionTemplatesPath($group_id, $collection_id)
	{
		$path = [];
		$path[] = rtrim(DIR_PUBLIC_URL, '/');
		$path[] = 'collections';
		$path[] = 'group_'. $group_id;
		$path[] = 'templates';
		$path[] = 'collection_'. $collection_id;

		return implode('/', $path) . '/';
	}

	public static function getCollectionTemplatesPreviewsPath($group_id, $collection_id)
	{
		$path = [];
		$path[] = rtrim(DIR_PUBLIC_URL, '/');
		$path[] = 'collections';
		$path[] = 'group_'. $group_id;
		$path[] = 'previews';
		$path[] = 'collection_'. $collection_id;

		return implode('/', $path) . '/';
	}

	public static function getOfferFilesPath($group_id, $offer_id)
	{
		$path = [];
		$path[] = rtrim(DIR_PUBLIC_URL, '/');
		$path[] = 'collections';
		$path[] = 'group_'. $group_id;
		$path[] = 'offers';
		$path[] = 'offer_'. $offer_id;

		return implode('/', $path) . '/';
	}

	public static function authorize($login, $password, $table = NULL) {
		if (!$table) $table = self::$tables['clients'];
		return q_assoc_row('SELECT * FROM `'. $table .'` WHERE `login` = "'. sql_prepare($login) .'" AND `password` = "'. self::generateHash($password) .'" AND `active` = "1" LIMIT 1');
	}

	public static function generateUniqueLogin($table = NULL) {
		if (!$table) $table = self::$tables['clients'];
		while (($login = UID::uid10()) && q_check($table, '`login` = "'. $login .'"'));
		return $login;
	}

	public static function generateUniquePasswordString() {
		return UID::uid10();
	}

	public static function generateHash($password) {
		return Users::generateHash($password, self::$_password_salt . CMS_UNIQUE_KEY);
	}

    public static function getCollectionPreviewImagePath(Collection $collection)
    {
        $template_path = ModuleClients::getCollectionTemplatesPath($collection->getGroupId(), $collection->getId());
        $dir_base_prefix = substr(DIR_BASE, 0, -1);

        $preview_path = ModuleClients::getCollectionTemplatesPreviewsPath($collection->getGroupId(), $collection->getId());
        FileSystem::mkDir($dir_base_prefix . $preview_path);

        $files = FileSystem::scanDirs(DIR_BASE . $template_path);
        if (!$files || !isset($files[0]['name']))  return '';

        // HTML file
        $template = $template_path . $files[0]['name'];

        // Image for this HTML
        $basename = pathinfo($files[0]['name'], PATHINFO_FILENAME);
        $img_url = $preview_path . $basename . '.jpg';

        ModuleTemplater::generateImagesFromHtmlFile($dir_base_prefix . $template, $dir_base_prefix . $img_url);

        return $img_url;
    }

    /**
     * @param Offer $offer
     * @param string $type
     * @param bool $image_num
     * @return array|string
     */
    public static function getOfferFiles(Offer $offer, $type = '', $image_num = false)
    {
        $client = new Client($offer->getClientId());

        $offer_path = self::getOfferFilesPath($client->getGroupId(), $offer->getId());

        $files = [];

        foreach (FileSystem::scanDirs(DIR_BASE . $offer_path) as $file) {
            if ($type && pathinfo($file['name'], PATHINFO_EXTENSION) != $type) continue; // Skip all other if type is provided

            if (!$image_num) {
                $files[] = $offer_path . $file['name'];
            } elseif ($image_num == str_replace('.'.$type, '', $file['name'])) {
                $files = $offer_path . $file['name'];
            }
        }

        // if we have array of files then we sort it
        if (is_array($files)) {
            natsort($files);
        }

        return $files;
    }

    public static function getCollectionFiles(Collection $collection, $type = '')
    {
        $templates_path = self::getCollectionTemplatesPath($collection->getGroupId(), $collection->getId());

        $files = [];
        foreach (FileSystem::scanDirs(DIR_BASE . $templates_path) as $file) {
            if ($type && pathinfo($file['name'], PATHINFO_EXTENSION) != $type) continue; // Skip all other if type is provided

            $files[] = $templates_path . $file['name'];
        }

        natsort($files);
        return $files;
    }
}