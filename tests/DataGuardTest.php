<?php

namespace Acdphp\DataGuard\Tests;

use Acdphp\DataGuard\DataGuard;
use Orchestra\Testbench\TestCase;

class DataGuardTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Acdphp\DataGuard\DataGuardServiceProvider'];
    }

    public function test_basic_array(): void
    {
        $data = ['key1' => 'val1', 'key2' => 'val2'];
        $protectedData = app(DataGuard::class)
            ->setData($data)
            ->hide('key2', 'val2')
            ->getResult();

        $this->assertEquals(['key1' => 'val1'], $protectedData);
    }

    public function test_full_example(): void
    {
        $data = collect([
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
                        'address' => [
                            [
                                'city' => 'Chicago',
                                'country' => 'USA',
                            ],
                            [
                                'city' => 'Stockholm',
                                'country' => 'Asgard',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Guy',
                    'profile' => [
                        'addresses' => [
                            [
                                'city' => 'Chicago',
                                'country' => 'USA',
                            ],
                            [
                                'city' => 'Uppsala',
                                'country' => 'Asgard',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // Hides profile if city = Asgard
        $protectedData = $data->hide(
            'hero|villain|others[]:profile',
            'addresses[]|address|address[]:city|country',
            '=',
            'Asgard'
        );

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
                [
                    'name' => 'Guy',
                ],
            ],
        ], $protectedData->toArray());
    }

    public function test_mask(): void
    {
        $data = collect(['a' => 'ABC', 'b' => 'DEF']);

        $maskedData = $data->mask('a');

        $this->assertEquals(['a' => config('dataguard.mask_with'), 'b' => 'DEF'], $maskedData->toArray());
    }

    /**
     * @dataProvider \Acdphp\DataGuard\Tests\DataProvider::provide()
     */
    public function test_more_examples_as_array(array $data, string $resource, array $expectedResult, array $conditions = null): void
    {
        $dg = app(DataGuard::class)
            ->setData($data);

        if (func_num_args() === 4) {
            $dg->hide($resource, function (DataGuard $dg) use ($conditions) {
                foreach ($conditions as $condition) {
                    $dg->whereResource(...$condition);
                }

                return $dg;
            });
        } else {
            $dg->hide($resource);
        }

        $this->assertEquals($expectedResult, $dg->getResult());
    }

    /**
     * @dataProvider \Acdphp\DataGuard\Tests\DataProvider::provide()
     */
    public function test_more_examples_as_collection(array $data, string $resource, array $expectedResult, array $conditions = null): void
    {
        $data = collect($data);

        if (func_num_args() === 4) {
            $guarded = $data->hide($resource, function (DataGuard $dg) use ($conditions) {
                foreach ($conditions as $condition) {
                    $dg->whereResource(...$condition);
                }

                return $dg;
            });
        } else {
            $guarded = $data->hide($resource);
        }

        $this->assertEquals($expectedResult, $guarded->toArray());
    }
}
