<?php
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Factory\RendererFactory;
?>
@php
  $differOptions = [
      // show how many neighbor lines
      // Differ::CONTEXT_ALL can be used to show the whole file
      'context' => 1,
      // ignore case difference
      'ignoreCase' => false,
      // ignore whitespace difference
      'ignoreWhitespace' => false,
  ];
  $rendererOptions = [
      'detailLevel' => 'char', // (none, line, word, char)
      'language' => 'chs',
      'separateBlock' => true, // show a separator between different diff hunks in HTML renderers
      'spacesToNbsp' => true
  ];

  $differ = new Differ(explode("\n", $old), explode("\n", $new), $differOptions);
  $renderer = RendererFactory::make('SideBySide', $rendererOptions); // or your own renderer object
  //dd($renderer, $old, $new);
  $result = $renderer->render($differ);

  echo $result;
@endphp