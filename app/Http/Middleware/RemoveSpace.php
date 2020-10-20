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
            '@(<\w+)\s+(\w+="[^"]*")@' => '$1 $2',
            '@(\w+=".*")\s+(\w+=".*")@u' => '$1 $2',
            "@>\s*([^\s]*)\s*<@" => '>$1<',
            "@\s*(<(a|li|span|dt|dd|td|tr|th)[^>]*>)\s*@" => '$1',
            "@\s*(</(a|li|span|dt|dd|td|tr|th)[^>]*>)\s*@" => '$1'
        ];

        return $this->replace($replace, $buffer);
    }
}
