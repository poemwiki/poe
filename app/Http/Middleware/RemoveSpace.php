<?php


namespace App\Http\Middleware;


use RenatoMarinho\LaravelPageSpeed\Middleware\PageSpeed;

class RemoveSpace  extends PageSpeed{

    public function apply($buffer) {
        $replace = [
//            "/\n([\S])/" => '$1',
//            "/\r/" => '',
//            "/\n/" => '',
//            "/\t/" => '',
//            "/ +/" => ' ',
            "@>\s+<@" => '><',
            "@\s*(<(a|li|span|dt|dd|td|tr|th)[^>]*>)\s*@" => '$1',
            "@\s*(</(a|li|span|dt|dd|td|tr|th)[^>]*>)\s*@" => '$1',
        ];

        return $this->replace($replace, $buffer);
    }
}
