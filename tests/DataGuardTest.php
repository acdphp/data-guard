<?php

use Cdinopol\DataGuard\DataGuard;
use PHPUnit\Framework\TestCase;

class DataGuardTest extends TestCase
{
    public function testBasic(): void
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
        $resource = 'people[]';
        $conditions = [['deceased','=',true]];
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
            'people' => [
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
        $resource = 'people[]';
        $conditions = [['address:city','=','Asgard'],['deceased', '=', false]];
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
            'people' => [
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
        $resource = 'people[]';
        $conditions = [['address:city','in',['Asgard','New York']]];
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
            'people' => [
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
        $resource = 'people[]:assets[]';
        $conditions = [['cost','>',20]];
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
            'people' => [
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
    public function testConditionAll(array $data): void
    {
        $resource = 'people[]';
        $conditions = '*';
        $protectedData = DataGuard::protect($data, $resource, $conditions);

        $this->assertEquals([
            'people' => [],
        ], $protectedData);
    }

    public function provider(): array
    {
        return [[
            [
                'people' => [
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