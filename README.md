# data-guard
![](https://github.com/cdinopol/data-guard/workflows/Tests/badge.svg?branch=master)
[![Latest Stable Version](http://poser.pugx.org/cdinopol/data-guard/v)](https://packagist.org/packages/cdinopol/data-guard)
[![License](http://poser.pugx.org/cdinopol/data-guard/license)](https://packagist.org/packages/cdinopol/data-guard)

Filter out data from an array of a given conditions

## Installation
```sh
composer require acdphp/data-guard
```

## Usage
```php
$data = (new DataGuard())->setData(array $data);

# Hide
$data->hide(string $resource, string $search, string $operator, mixed $value);

# Mask
$data->mask(string $resource, string $search, string $operator, mixed $value);
```

### Data
- your data array (preferably an associative array)

### Resource
- string (example format: `'orders[]|order:line_items[]:sku'`)
- this is the key point of data to be processed.
- `|` - key split, keys to match on the same level.
- `:` - key separator, hierarchy of keys to match from root to child.
- `[]` - array indicator, DataGuard will look inside each of the values instead of directly looking for the next key.

## Search (optional)
- instead of matching the given resource directly, you can pass another resource (same formatting as resource) as the first index of condition to match against the operator+value. search_resource will be searched through and matched, but the process point will still be on the given resource.
- if not provided, last node of resource will be matched.

##  Operators (optional)
```
1. =     : equals
2. !=    : not equals
3. in    : in array
4. !in   : not in array
5. >     : greater than
6. <     : less than
7. <=    : less than or equal
8. >=    : greater than or equal
9. regex : Regular Expression; condition value must be a proper expression
```
- if not provided, `=` (equals) will be used

## Value
- matches the search or resource with the given operator.

## Usage
```php
use Cdinopol\DataGuard\DataGuard;

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
                'address' => [
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
$protectedData = (new DataGuard())
    ->setData($data)
    ->hide('heroes[]|hero|villain|others[]:profile', 'address|address[]:city', '=', 'Asgard')
    ->getResult();

print_r($protectedData);
# Result:
[
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
];
```

Please check the [unit test](tests/DataGuardTest.php) for more usage examples.

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.
