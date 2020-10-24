<?php

return [
    'poem' => [
        'title' => '诗歌',

        'actions' => [
            'index' => '诗歌',
            'create' => '添加诗歌',
            'edit' => '编辑',
            'operation' => '操作'
        ],

        'columns' => [
            'id' => 'ID',
            'title' => '标题',
            'language' => '语言',
            'is_original' => '原作/译作',
            'poet' => '作者',
            'poet_cn' => '作者中文名',
            'bedtime_post_id' => '读睡博客ID',
            'bedtime_post_title' => '读睡博客标题',
            'poem' => '诗歌正文',
            'length' => '长度',
            'translator' => '译者',
            'from' => '来源',
            'year' => '年',
            'month' => '月',
            'date' => '日',
            'dynasty' => '朝代',
            'nation' => '国家',
            'need_confirm' => '审核状态',
            'is_lock' => '锁定',
            'content_id' => '内容ID',
            'preface' => '题记',
            'subtitle' => '副标题',

        ],
    ],

    // Do not delete me :) I'm used for auto-generation
];
