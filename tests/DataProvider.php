<?php

namespace Acdphp\DataGuard\Tests;

class DataProvider
{
    public static function provide(): array
    {
        return [
            'all' => [
                static::nestedArray(),
                'heroes[]',
                ['heroes' => []],
            ],
            'multi-level-resource' => [
                static::nestedArray(),
                'heroes[]:assets[]',
                static::multiLevelResourceResult(),
                [['cost', '>', 20]],
            ],
            'multi-level-condition' => [
                static::nestedArray(),
                'heroes[]',
                static::multiLevelConditionResult(),
                [['address:city', 'in', ['Asgard','New York']]]
            ],
            'multiple-conditions' => [
                static::nestedArray(),
                'heroes[]',
                static::multiConditionsResult(),
                [['address:city', '=', 'Asgard'], ['deceased', '=', false]]
            ],
            'direct-key' => [
                static::nestedArray(),
                'heroes[]',
                static::directKeyResult(),
                [['deceased', '=', true]],
            ]
        ];
    }

    private static function nestedArray(): array
    {
        return [
            'heroes' => [
                [
                    'name' => 'Tony',
                    'deceased' => true,
                    'address' => [
                        'city' => 'New York',
                        'country' => 'United States',
                    ],
                    'assets' => [
                        ['type' => 'house', 'cost' => '200'],
                        ['type' => 'car', 'cost' => '10'],
                        ['type' => 'others', 'cost' => '50'],
                    ]
                ],
                [
                    'name' => 'Natalia',
                    'deceased' => true,
                    'address' => [
                        'city' => 'Moscow',
                        'country' => 'Russia',
                    ],
                    'assets' => [
                        ['type' => 'bike', 'cost' => '50'],
                        ['type' => 'accessories', 'cost' => '30'],
                    ]
                ],
                [
                    'name' => 'Thor',
                    'deceased' => false,
                    'address' => [
                        'city' => 'Asgard',
                        'country' => 'Asgard',
                    ],
                    'assets' => [
                        ['type' => 'house', 'cost' => '20'],
                        ['type' => 'others', 'cost' => '500'],
                    ]
                ],
            ],
        ];
    }

    private static function multiLevelResourceResult(): array
    {
        return [
            'heroes' => [
                [
                    'name' => 'Tony',
                    'deceased' => true,
                    'address' => [
                        'city' => 'New York',
                        'country' => 'United States',
                    ],
                    'assets' => [
                        ['type' => 'car', 'cost' => '10'],
                    ]
                ],
                [
                    'name' => 'Natalia',
                    'deceased' => true,
                    'address' => [
                        'city' => 'Moscow',
                        'country' => 'Russia',
                    ],
                    'assets' => []
                ],
                [
                    'name' => 'Thor',
                    'deceased' => false,
                    'address' => [
                        'city' => 'Asgard',
                        'country' => 'Asgard',
                    ],
                    'assets' => [
                        ['type' => 'house', 'cost' => '20'],
                    ]
                ],
            ],
        ];
    }

    private static function multiLevelConditionResult(): array
    {
        return [
            'heroes' => [
                [
                    'name' => 'Natalia',
                    'deceased' => true,
                    'address' => [
                        'city' => 'Moscow',
                        'country' => 'Russia',
                    ],
                    'assets' => [
                        ['type' => 'bike', 'cost' => '50'],
                        ['type' => 'accessories', 'cost' => '30'],
                    ]
                ],
            ],
        ];
    }

    private static function multiConditionsResult(): array
    {
        return [
            'heroes' => [
                [
                    'name' => 'Tony',
                    'deceased' => true,
                    'address' => [
                        'city' => 'New York',
                        'country' => 'United States',
                    ],
                    'assets' => [
                        ['type' => 'house', 'cost' => '200'],
                        ['type' => 'car', 'cost' => '10'],
                        ['type' => 'others', 'cost' => '50'],
                    ]
                ],
                [
                    'name' => 'Natalia',
                    'deceased' => true,
                    'address' => [
                        'city' => 'Moscow',
                        'country' => 'Russia',
                    ],
                    'assets' => [
                        ['type' => 'bike', 'cost' => '50'],
                        ['type' => 'accessories', 'cost' => '30'],
                    ]
                ],
            ],
        ];
    }

    private static function directKeyResult(): array
    {
        return [
            'heroes' => [
                [
                    'name' => 'Thor',
                    'deceased' => false,
                    'address' => [
                        'city' => 'Asgard',
                        'country' => 'Asgard',
                    ],
                    'assets' => [
                        ['type' => 'house', 'cost' => '20'],
                        ['type' => 'others', 'cost' => '500'],
                    ]
                ],
            ],
        ];
    }
}
