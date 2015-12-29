<?php
namespace TMCms\Modules\Gallery\Object;

use TMCms\Orm\EntityRepository;

class GalleryCollection extends EntityRepository {
    protected $db_table = 'm_gallery';
}
