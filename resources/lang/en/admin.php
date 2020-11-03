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

    'review' => [
        'title' => 'Review',

        'actions' => [
            'index' => 'Review',
            'create' => 'New Review',
            'edit' => 'Edit :name',
        ],

        'columns' => [
            'id' => 'ID',
            'content' => 'Content',
            'content_id' => 'Content',
            'like' => 'Like',
            'poem_id' => 'Poem',
            'title' => 'Title',
            'user_id' => 'User',
            
        ],
    ],

    'admin-user' => [
        'title' => 'Users',

        'actions' => [
            'index' => 'Users',
            'create' => 'New User',
            'edit' => 'Edit :name',
            'edit_profile' => 'Edit Profile',
            'edit_password' => 'Edit Password',
        ],

        'columns' => [
            'id' => 'ID',
            'last_login_at' => 'Last login',
            'activated' => 'Activated',
            'email' => 'Email',
            'first_name' => 'First name',
            'forbidden' => 'Forbidden',
            'language' => 'Language',
            'last_name' => 'Last name',
            'password' => 'Password',
            'password_repeat' => 'Password Confirmation',
                
            //Belongs to many relations
            'roles' => 'Roles',
                
        ],
    ],

    'genre' => [
        'title' => 'Genre',

        'actions' => [
            'index' => 'Genre',
            'create' => 'New Genre',
            'edit' => 'Edit :name',
        ],

        'columns' => [
            'id' => 'ID',
            'describe_lang' => 'Describe lang',
            'f_id' => 'F',
            'name' => 'Name',
            'name_lang' => 'Name lang',
            'wikidata_id' => 'Wikidata',
            
        ],
    ],

    'dynasty' => [
        'title' => 'Dynasty',

        'actions' => [
            'index' => 'Dynasty',
            'create' => 'New Dynasty',
            'edit' => 'Edit :name',
        ],

        'columns' => [
            'id' => 'ID',
            'describe_lang' => 'Describe lang',
            'f_id' => 'F',
            'name' => 'Name',
            'name_lang' => 'Name lang',
            'wikidata_id' => 'Wikidata',
            
        ],
    ],

    'nation' => [
        'title' => 'Nation',

        'actions' => [
            'index' => 'Nation',
            'create' => 'New Nation',
            'edit' => 'Edit :name',
        ],

        'columns' => [
            'id' => 'ID',
            'describe_lang' => 'Describe lang',
            'f_id' => 'F',
            'name' => 'Name',
            'name_lang' => 'Name lang',
            'wikidata_id' => 'Wikidata',
            
        ],
    ],

    'tag' => [
        'title' => 'Tag',

        'actions' => [
            'index' => 'Tag',
            'create' => 'New Tag',
            'edit' => 'Edit :name',
        ],

        'columns' => [
            'id' => 'ID',
            'category_id' => 'Category',
            'describe_lang' => 'Describe lang',
            'name' => 'Name',
            'name_lang' => 'Name lang',
            'wikidata_id' => 'Wikidata',
            
        ],
    ],

    'category' => [
        'title' => 'Category',

        'actions' => [
            'index' => 'Category',
            'create' => 'New Category',
            'edit' => 'Edit :name',
        ],

        'columns' => [
            'id' => 'ID',
            'describe_lang' => 'Describe lang',
            'name' => 'Name',
            'name_lang' => 'Name lang',
            'wikidata_id' => 'Wikidata',
            
        ],
    ],

    'author' => [
        'title' => 'Author',

        'actions' => [
            'index' => 'Author',
            'create' => 'New Author',
            'edit' => 'Edit :name',
        ],

        'columns' => [
            'id' => 'ID',
            'describe_lang' => 'Describe lang',
            'name_lang' => 'Name lang',
            'pic_url' => 'Pic url',
            'user_id' => 'User',
            'wikidata_id' => 'Wikidata',
            'wikipedia_url' => 'Wikipedia url',
            
        ],
    ],

    // Do not delete me :) I'm used for auto-generation
];
