<?php


namespace App\Http\Middleware;


use RenatoMarinho\LaravelPageSpeed\Middleware\PageSpeed;

class RemoveSpace extends PageSpeed{
    // TODO replace spaces for html of livewire response
    public function apply($buffer) {
        $replace = [
//            "/\n([\S])/" => '$1',
//            "/\r/" => '',
//            "/\n/" => '',
//            "/\t/" => '',
//            "/ +/" => ' ',
            "@>\s*([^\s]*)\s*<@" => '>$1<',
            "@\s*(<(a|li|span|dt|dd|td|tr|th)[^>]*>)\s*@" => '$1',
            "@\s*(</(a|li|span|dt|dd|td|tr|th)[^>]*>)\s*@" => '$1'
        ];

        $str = $this->replace($replace, $buffer);
        return preg_replace_callback('@<\w+[^>]*>@', function ($matches){
            // replace spaces between attrs
            return preg_replace('@([\w\-:]+(?:="[^"]*")?)\s+@', '$1 ', $matches[0]);
        }, $str);
    }
}
