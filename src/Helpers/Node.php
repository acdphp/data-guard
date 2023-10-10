<?php

namespace Acdphp\DataGuard\Helpers;

class Node
{
    public static function isArray(string &$node, iterable $data, string $arrayIndicator): bool
    {
        if (substr($node, -strlen($arrayIndicator)) !== $arrayIndicator) {
            return false;
        }

        $node = trim($node, $arrayIndicator);

        return isset($data[$node]) &&
            is_array($data[$node]) &&
            array_keys($data[$node]) === range(0, count($data[$node]) - 1);
    }

    public static function split(string $node, string $splitter, string $arrayIndicator): array
    {
        $splits = explode($splitter, $node);

        // Sort splits to process array types first
        return [
            ...array_filter($splits, static fn ($v) => str_ends_with($v, $arrayIndicator)),
            ...array_filter($splits, static fn ($v) => ! str_ends_with($v, $arrayIndicator)),
        ];
    }
}
