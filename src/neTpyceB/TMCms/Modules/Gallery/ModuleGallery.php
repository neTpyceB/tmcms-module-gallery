<?php
namespace neTpyceB\TMCms\Modules\Gallery;

use neTpyceB\TMCms\Admin\Users;
use neTpyceB\TMCms\Files\FileSystem;
use neTpyceB\TMCms\Modules\Clients\Object\Client;
use neTpyceB\TMCms\Modules\Clients\Object\Collection;
use neTpyceB\TMCms\Modules\Clients\Object\Offer;
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
		'images' => 'm_<gallery',
		'categories' => 'm_gallery_categories'
	);
}