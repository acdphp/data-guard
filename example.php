<?php
require_once('src/Exception/InvalidConditionException.php');
require_once('src/DataGuard.php');

use Cdinopol\DataGuard\DataGuard;

echo '<pre>';

# Basic example
$data = ['key1' => 'val1', 'key2' => 'val2'];
$resource = 'key2';
$conditions = [['=','val2']];

$protectedData = DataGuard::protect($data, $resource, $conditions);
print_r($protectedData);

# Array of objects (in a form of associative array) example
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

# Direct key
$resource = 'people[]';
$conditions = [['deceased','=',true]];

$protectedData = DataGuard::protect($data, $resource, $conditions);
print_r($protectedData);

# Multi-level condition
$resource = 'people[]';
$conditions = [['address:city','in',['Asgard','New York']]];

$protectedData = DataGuard::protect($data, $resource, $conditions);
print_r($protectedData);

# Multi-level resource
$resource = 'people[]:assets[]';
$conditions = [['cost','>',20]];

$protectedData = DataGuard::protect($data, $resource, $conditions);
print_r($protectedData);

# Condition all
$resource = 'people[]';
$conditions = '*';

$protectedData = DataGuard::protect($data, $resource, $conditions);
print_r($protectedData);