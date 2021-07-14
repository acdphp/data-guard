# data-guard
Filter out data from an array of a given conditions

## Installation
```sh
composer require cdinopol/data-guard
```

## Usage
```
DataGuard::protect(array $data, string $resource, array $conditions);
```

## Config
Override the config by creating `config/dataguard.php`
```php
return [
    'separator'       => ':',
    'condition_all'   => '*',
    'array_indicator' => '[]',
];
```

## Condition Operators
```
1. =   : equals
2. !=  : not equals
3. in  : in array
4. !in : not in array
5. >   : greater than
6. <   : less than
```

## Usage
#### Basic example
```php
use Cdinopol\DataGuard\DataGuard;

$data = ['key1' => 'val1', 'key2' => 'val2'];
$resource = 'key2';
$conditions = [['=','val2']];

$protectedData = DataGuard::protect($data, $resource, $conditions);

print_r($protectedData);
//Result:
//['key1' => 'val1'];
```

#### Array of objects (in a form of associative array) example
```php
use Cdinopol\DataGuard\DataGuard;

$data = [
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
];

# -------------------------------------------------------------------------
# Direct key
$resource = 'people[]';
$conditions = [['deceased','=',true]];
$protectedData = DataGuard::protect($data, $resource, $conditions);
//Result:
//[
//    'people' => [
//        [
//            'name' => 'Thor',
//            'deceased' => false,
//            'address' => [
//                'city' => 'Asgard',
//                'country' => 'Asgard',
//            ],
//            'assets' => [
//                ['type' => 'house', 'cost' => '20'],
//                ['type' => 'others', 'cost' => '500'],
//            ],
//        ],
//    ],
//];

# -------------------------------------------------------------------------
# Multi-level condition 
$resource = 'people[]';
$conditions = [['address:city','in',['Asgard','New York']]];
$protectedData = DataGuard::protect($data, $resource, $conditions);
//Result:
//[
//        [
//            'name' => 'Natalia',
//            'deceased' => true,
//            'address' => [
//                'city' => 'Moscow',
//                'country' => 'Russia',
//            ],
//            'assets' => [
//                ['type' => 'bike', 'cost' => '50'],
//                ['type' => 'accessories', 'cost' => '30'],
//            ]
//        ],
//    ],
//];

# -------------------------------------------------------------------------
# Multi-level resource
$resource = 'people[]:assets[]';
$conditions = [['cost','>',20]];
$protectedData = DataGuard::protect($data, $resource, $conditions);
//Result:
//[
//    'people' => [
//        [
//            'name' => 'Tony',
//            'deceased' => true,
//            'address' => [
//                'city' => 'New York',
//                'country' => 'United States',
//            ],
//            'assets' => [
//                ['type' => 'car', 'cost' => '10'],
//            ]
//        ],
//        [
//            'name' => 'Natalia',
//            'deceased' => true,
//            'address' => [
//                'city' => 'Moscow',
//                'country' => 'Russia',
//            ],
//            'assets' => []
//        ],
//        [
//            'name' => 'Thor',
//            'deceased' => false,
//            'address' => [
//                'city' => 'Asgard',
//                'country' => 'Asgard',
//            ],
//            'assets' => [
//                ['type' => 'house', 'cost' => '20'],
//            ]
//        ],
//    ],
//];

# -------------------------------------------------------------------------
# Condition all
$resource = 'people[]';
$conditions = '*';
$protectedData = DataGuard::protect($data, $resource, $conditions);
# Result:
//[
//    'people' => [],
//];
```
