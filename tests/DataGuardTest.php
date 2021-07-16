<?php

namespace Acdphp\DataGuard\Tests;

use Acdphp\DataGuard\DataGuard;
use Orchestra\Testbench\TestCase;

class DataGuardTest extends TestCase
{
    public function testBasicArray(): void
    {
        $data = ['key1' => 'val1', 'key2' => 'val2'];
        $protectedData = app(DataGuard::class)
            ->hide($data, 'key2', 'val2');

        $this->assertEquals(['key1' => 'val1'], $protectedData);
    }

    public function testFullExample(): void
    {
        $data = collect([
            'hero' => [
                'name'    => 'Thor',
                'profile' => [
                    'address' => [
                        'city'    => 'Asgard',
                        'country' => 'Asgard',
                    ],
                ],
            ],
            'villain' => [
                'name'    => 'Loki',
                'profile' => [
                    'address' => [
                        'city'    => 'Asgard',
                        'country' => 'Asgard',
                    ],
                ],
            ],
            'others' => [
                [
                    'name'    => 'John',
                    'profile' => [
                        'address' => [
                            'city'    => 'Asgard',
                            'country' => 'Asgard',
                        ],
                    ],
                ],
                [
                    'name'    => 'Doe',
                    'profile' => [
                        'address' => [
                            'city'    => 'New York',
                            'country' => 'USA',
                        ],
                    ],
                ],
                [
                    'name'    => 'Carl',
                    'profile' => [
                        'address' => [
                            [
                                'city'    => 'Chicago',
                                'country' => 'USA',
                            ],
                            [
                                'city'    => 'Stockholm',
                                'country' => 'Asgard',
                            ],
                        ],
                    ],
                ],
                [
                    'name'    => 'Guy',
                    'profile' => [
                        'addresses' => [
                            [
                                'city'    => 'Chicago',
                                'country' => 'USA',
                            ],
                            [
                                'city'    => 'Uppsala',
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
                    'name'    => 'Doe',
                    'profile' => [
                        'address' => [
                            'city'    => 'New York',
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

    public function testMask(): void
    {
        $data = collect(['a' => 'ABC', 'b' => 'DEF']);

        $maskedData = $data->mask('a');

        $this->assertEquals(['a' => config('dataguard.mask_with'), 'b' => 'DEF'], $maskedData->toArray());
    }

    /**
     * @dataProvider \Acdphp\DataGuard\Tests\DataProvider::provide()
     */
    public function testMoreExamplesAsArray(array $data, string $resource, array $expectedResult, array $conditions = null): void
    {
        $result = null;

        if (func_num_args() === 4) {
            $result = app(DataGuard::class)->hide($data, $resource, function (DataGuard $dg) use ($conditions) {
                foreach ($conditions as $condition) {
                    $dg->whereResource(...$condition);
                }

                return $dg;
            });
        } else {
            $result = app(DataGuard::class)->hide($data, $resource);
        }

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider \Acdphp\DataGuard\Tests\DataProvider::provide()
     */
    public function testMoreExamplesAsCollection(array $data, string $resource, array $expectedResult, array $conditions = null): void
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

    protected function getPackageProviders($app)
    {
        return ['Acdphp\DataGuard\DataGuardServiceProvider'];
    }
}
