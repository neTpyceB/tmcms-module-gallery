<?php

namespace TMCms\Modules\Gallery\Entity;

use TMCms\Orm\EntityRepository;
use TMCms\Orm\TableStructure;

class GalleryCategoryEntityRepository extends EntityRepository {
    protected $db_table = 'm_gallery_categories';
    protected $translation_fields = ['title'];

    protected $table_structure = [
        'fields' => [
            'title' => [
                'type' => TableStructure::FIELD_TYPE_TRANSLATION,
            ],
            'active' => [
                'type' => 'bool',
            ],
            'order' => [
                'type' => 'int',
                'unsigned' => true,
            ],
            'slug' => [
                'type' => 'varchar',
            ],
        ]
    ];
}
