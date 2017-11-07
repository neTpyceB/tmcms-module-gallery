<?php

namespace TMCms\Modules\Gallery\Entity;

use TMCms\Orm\EntityRepository;
use TMCms\Orm\TableStructure;

/**
 * Class GalleryEntityRepository
 * @package TMCms\Modules\Gallery\Entity
 *
 * @method $this setWhereActive(int $flag)
 * @method $this setWhereCategoryId(int $category_id)
 */
class GalleryEntityRepository extends EntityRepository {
    protected $translation_fields = ['title'];
    protected $table_structure = [
        'fields' => [
            'category_id' => [
                'type' => 'index',
            ],
            'title' => [
                'type' => TableStructure::FIELD_TYPE_TRANSLATION,
            ],
            'image' => [
                'type' => 'varchar',
            ],
            'active' => [
                'type' => 'bool',
            ],
            'ts_created' => [
                'type' => TableStructure::FIELD_TYPE_UNSIGNED_INTEGER,
            ],
            'order' => [
                'type' => 'int',
                'unsigned' => true,
            ],
        ]
    ];
}
