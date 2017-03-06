<?php

namespace TMCms\Modules\Gallery\Entity;

use TMCms\Orm\EntityRepository;

class GalleryEntityRepository extends EntityRepository {
    protected $translation_fields = ['title'];
    protected $table_structure = [
        'fields' => [
            'category_id' => [
                'type' => 'index',
            ],
            'title' => [
                'type' => 'translation',
            ],
            'image' => [
                'type' => 'varchar',
            ],
            'active' => [
                'type' => 'bool',
            ],
            'ts_created' => [
                'type' => 'ts',
            ],
            'order' => [
                'type' => 'int',
                'unsigned' => true,
            ],
        ]
    ];
}