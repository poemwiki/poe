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
//             "@(/\w+>\s*[^\s]*)\s*<@" => '$1<',
            // remove  spaces around block elements tag
            "@\s*(</?(head|meta|title|link|address|article|h1|h2|h3|h4|h5|h6|aside|blockquote|details|dialog|dd|div|dl|dt|fieldset|figcaption|figure|footer|form|h1|header|hgroup|hr|li|main|nav|ol|p|section|table|ul)(\s+[^>]+)*>)\s*@" => '$1',
            // remove spaces around inline elements tag
            "@\s*(</?(a|abbr|acronym|audio|b|bdi|bdo|big|br|button|canvas|cite|data|datalist|del|dfn|em|embed|i|iframe|img|input|ins|kbd|label|map|mark|meter|noscript|object|output|picture|progress|q|ruby|s|samp|script|select|slot|small|span|strong|sub|sup|svg|template|textarea|time|u|tt|var|video|wbr)(\s+[^>]+)*>)\s*@" => '$1',
            // remove spaces around pre/code tag
            "@\s*(<(pre|code)(\s+[^>]+)*>)@" => '$1',
        ];

        $str = $this->replace($replace, $buffer);
        return preg_replace_callback('@<\w+[^>]*>@', function ($matches){
            // replace spaces between attrs
            return preg_replace('@([\w\-:]+(?:="[^"]*")?)\s+@', '$1 ', $matches[0]);
        }, $str);
    }
}
