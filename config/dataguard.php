<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Key separator
    |--------------------------------------------------------------------------
    |
    | Separates hierarchy of keys to match from root to child.
    | Example: grandparent.parent.child
    |
    */
    'separator' => ':',

    /*
    |--------------------------------------------------------------------------
    | Splitter
    |--------------------------------------------------------------------------
    |
    | Splits keys to match on the same level.
    | Example: grandma|grandpa.mother|father.son|daughter
    |
    */
    'splitter' => '|',

    /*
    |--------------------------------------------------------------------------
    | Array indicator
    |--------------------------------------------------------------------------
    |
    | To indicate that it should look inside each of the values instead of directly looking for the next key.
    | Example: grandparents[].parents[].child
    |
    */
    'array_indicator' => '[]',

    /*
    |--------------------------------------------------------------------------
    | Mask With
    |--------------------------------------------------------------------------
    |
    | When calling mask(), data will be replaced with a string instead of removing it.
    |
    */
    'mask_with' => '###',
];
