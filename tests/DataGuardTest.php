<?php

use Cdinopol\DataGuard\DataGuard;
use PHPUnit\Framework\TestCase;

class DataGuardTest extends TestCase
{
    public function testFullExample(): void
    {
        $data = [
            'hero' => [
                'name' => 'Thor',
                'profile' => [
                    'address' => [
                        'city' => 'Asgard',
                        'country' => 'Asgard',
                    ],
                ],

            ],
            'villain' => [
                'name' => 'Loki',
                'profile' => [
                    'address' => [
                        'city' => 'Asgard',
                        'country' => 'Asgard',
                    ],
                ],
            ],
            'others' => [
                [
                    'name' => 'John',
                    'profile' => [
                        'address' => [
                            'city' => 'Asgard',
                            'country' => 'Asgard',
                        ],
                    ],
                ],
                [
                    'name' => 'Doe',
                    'profile' => [
                        'address' => [
                            'city' => 'New York',
                            'country' => 'USA',
                        ],
                    ],
                ],
                [
                    'name' => 'Carl',
                    'profile' => [
                        'addresses' => [
                            [
                                'city' => 'Chicago',
                                'country' => 'USA',
                            ],
                            [
                                'city' => 'Asgard',
                                'country' => 'Asgard',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Hides profile if city = Asgard
        $resource = 'heroes[]|hero|villain|others[]:profile';
        $conditions = [['address|addresses[]:city', '=', 'Asgard']];
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
            'hero' => [
                'name' => 'Thor',
            ],
            'villain' => [
                'name' => 'Loki',
            ],
            'others' => [
                [
                    'name' => 'John',
                ],
                [
                    'name' => 'Doe',
                    'profile' => [
                        'address' => [
                            'city' => 'New York',
                            'country' => 'USA',
                        ],
                    ],
                ],
                [
                    'name' => 'Carl',
                ],
            ],
        ], $protectedData);
    }

    public function testBasicArray(): void
    {
        $data = ['key1' => 'val1', 'key2' => 'val2'];
        $resource = 'key2';
        $conditions = [['=','val2']];
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals(['key1' => 'val1'], $protectedData);
    }

    /**
     * @dataProvider provider
     */
    public function testDirectKey(array $data): void
    {
        $resource = 'heroes[]';
        $conditions = [['deceased','=',true]];
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
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
        ], $protectedData);
    }

    /**
     * @dataProvider provider
     */
    public function testMultipleConditions(array $data): void
    {
        $resource = 'heroes[]';
        $conditions = [['address:city','=','Asgard'],['deceased', '=', false]];
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
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
        ], $protectedData);
    }

    /**
     * @dataProvider provider
     */
    public function testMultiLevelCondition(array $data): void
    {
        $resource = 'heroes[]';
        $conditions = [['address:city','in',['Asgard','New York']]];
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
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
        ], $protectedData);
    }

    /**
     * @dataProvider provider
     */
    public function testMultiLevelResource(array $data): void
    {
        $resource = 'heroes[]:assets[]';
        $conditions = [['cost','>',20]];
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
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
        ], $protectedData);
    }

    /**
     * @dataProvider provider
     */
    public function testMask(array $data): void
    {
        $resource = 'heroes[]:assets[]:cost';
        $conditions = '*';
        $mask = 'unknown';
        $protectedData = DataGuard::protect($data, $resource, $conditions, $mask);

        $this->assertEquals([
            'heroes' => [
                [
                    'name' => 'Tony',
                    'deceased' => true,
                    'address' => [
                        'city' => 'New York',
                        'country' => 'United States',
                    ],
                    'assets' => [
                        ['type' => 'house', 'cost' => 'unknown'],
                        ['type' => 'car', 'cost' => 'unknown'],
                        ['type' => 'others', 'cost' => 'unknown'],
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
                        ['type' => 'bike', 'cost' => 'unknown'],
                        ['type' => 'accessories', 'cost' => 'unknown'],
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
                        ['type' => 'house', 'cost' => 'unknown'],
                        ['type' => 'others', 'cost' => 'unknown'],
                    ]
                ],
            ],
        ], $protectedData);
    }

    /**
     * @dataProvider provider
     */
    public function testConditionAll(array $data): void
    {
        $resource = 'heroes[]';
        $conditions = '*';
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
            'heroes' => [],
        ], $protectedData);
    }

    public function provider(): array
    {
        return [[
            [
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
            ],
        ]];
    }
}
