<?php

return [
    'poem' => [
        'title' => 'Poem',

        'actions' => [
            'index' => 'Poem',
            'create' => 'New Poem',
            'edit' => 'Edit :name',
            'operation' => 'Operation'
        ],

        'columns' => [
            'id' => 'ID',
            'title' => 'Title',
            'language' => 'Language',
            'is_original' => 'Is original',
            'poet' => 'Poet',
            'poet_cn' => 'Poet cn',
            'bedtime_post_id' => 'Bedtime post',
            'bedtime_post_title' => 'Bedtime post title',
            'poem' => 'Poem',
            'length' => 'Length',
            'translator' => 'Translator',
            'from' => 'From',
            'year' => 'Year',
            'month' => 'Month',
            'date' => 'Date',
            'dynasty' => 'Dynasty',
            'nation' => 'Nation',
            'need_confirm' => 'Need confirm',
            'is_lock' => 'Is lock',
            'content_id' => 'Content',

        ],
    ],

    'score' => [
        'title' => 'Score',

        'actions' => [
            'index' => 'Score',
            'create' => 'New Score',
            'edit' => 'Edit :name',
        ],

        'columns' => [
            'id' => 'ID',
            'content_id' => 'Content',
            'factor' => 'Factor',
            'poem_id' => 'Poem',
            'score' => 'Score',
            'user_id' => 'User',
            
        ],
    ],

    // Do not delete me :) I'm used for auto-generation
];
