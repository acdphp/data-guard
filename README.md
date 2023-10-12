# Data Guard

[![Latest Stable Version](http://poser.pugx.org/cdinopol/data-guard/v)](https://packagist.org/packages/cdinopol/data-guard)
[![License](http://poser.pugx.org/cdinopol/data-guard/license)](https://packagist.org/packages/cdinopol/data-guard)

Hides or masks array or collection elements on specific levels from a given specifications and conditions.

## Installation
```sh
composer require acdphp/data-guard
```

## Usage
```php
# Hide
(new DataGuard())
    ->hide(array $data, string $resource, string $search, string $operator, mixed $value);

# Mask
(new DataGuard())
    ->mask(array $data, string $resource, string $search, string $operator, mixed $value);
```

## Collection support
- This can also be used directly with illuminate collection.
```php
$protectedData = collect(['a' => 1, 'b' => 2])
    ->hide('a')
    ->mask('b');

print_r($protectedData->toArray());
# Result:
['b' => '###'];
```

---

## Resource and Search Indicators
- `|`   - key split, keys to match on the same level.
- `:`   - key separator, hierarchy of keys to match from root to child.
- `[]`  - array indicator, DataGuard will look inside each of the values instead of directly looking for the next key.
- `###` - mask with, when calling `mask()`, data will be replaced with a string instead of removing it.

#### You may modify the indicators by passing it as a constructor argument.
```php
new DataGuard(':', '|', '[]', '###')
```

#### When used in a framework like laravel, you may publish the config to change the indicators.
```sh
php artisan vendor:publish --tag=dataguard-config
```

## Data (array)
- your data array (preferably an associative array)

## Resource (string)
- string (example format: `'orders[]|order:line_items[]:sku'`)
- this is the key point of data to be processed.

## Search (string, optional)
- instead of matching the given resource directly, you can pass another resource (same formatting as resource) as the first index of condition to match against the operator+value. search_resource will be searched through and matched, but the process point will still be on the given resource.
- if not provided, last node of resource will be matched.

## Operator (string, optional)
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

## Value (mixed, optional)
- matches the search or resource with the given operator.

---

## Example
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
    ->hide($data, 'heroes[]|hero|villain|others[]:profile', 'address|address[]:city', '=', 'Asgard');

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

---

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.
