<?php

namespace Cdinopol\DataGuard;

use Cdinopol\DataGuard\Exception\InvalidConditionException;

class DataGuard
{
    public const SEPARATOR       = ':';
    public const CONDITION_ALL   = '*';
    public const ARRAY_INDICATOR = '[]';

    /**
     * @param array $data
     * @param string $resource
     * @param string|array $conditions
     *
     * @return array
     *
     * @throws InvalidConditionException
     */
    public static function protect(array $data, string $resource, $conditions): array
    {
        $nodes = explode(self::SEPARATOR, $resource);

        // Final resource node match against condition
        if (count($nodes) === 1) {
            if (static::isNodeArray($resource, $data)) {
                foreach ($data[$resource] as $j => $single) {
                    if (static::conditions($data[$resource][$j], $conditions)) {
                        unset($data[$resource][$j]);
                    }
                }
            } elseif (isset($data[$resource])) {
                if (static::conditions($data[$resource], $conditions)) {
                    unset($data[$resource]);
                }
            }

            return $data;
        }

        // Each of parent resource nodes
        foreach ($nodes as $k => $node) {
            $levelResource = implode(self::SEPARATOR, array_slice($nodes, $k + 1));

            if (static::isNodeArray($node, $data)) {
                foreach ($data[$node] as $j => $single) {
                    $data[$node][$j] = static::protect($data[$node][$j], $levelResource, $conditions);
                }
            } elseif (isset($data[$node])) {
                $data[$node] = static::protect($data[$node], $levelResource, $conditions);
            }
        }

        return $data;
    }

    /**
     * @param mixed        $data
     * @param string|array $conditions
     *
     * @return bool
     *
     * @throws InvalidConditionException
     */
    private static function conditions($data, $conditions): bool
    {
        if (!is_array($conditions) && $conditions !== self::CONDITION_ALL) {
            throw new InvalidConditionException(sprintf('Conditions must be an array or "%s"', self::CONDITION_ALL));
        }

        if ($conditions === self::CONDITION_ALL) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                throw new InvalidConditionException(
                    'Conditions must be an array of array condition');
            }

            $conditionCount = count($condition);
            if ($conditionCount < 2 || $conditionCount > 3) {
                throw new InvalidConditionException(
                'Condition must consist of 2 or 3 segments: [1] resource key condition (optional), [2] operator, [3] value');
            }

            // Direct search from resource
            if ($conditionCount === 2) {
                $data = ['search' => $data];
                array_unshift($condition, 'search');
            }

            if (!static::condition($data, ...$condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed  $data
     * @param string $conditionResource
     * @param string $conditionOperator
     * @param mixed  $conditionValue
     *
     * @return bool
     *
     * @throws InvalidConditionException
     */
    private static function condition($data, string $conditionResource, string $conditionOperator, $conditionValue): bool
    {
        $matched = false;
        $nodes   = explode(self::SEPARATOR, $conditionResource);

        // Final condition node
        if (count($nodes) === 1) {
            // Condition key not found in the resource data
            if (!isset($data[$conditionResource])) {
                throw new InvalidConditionException(
                    sprintf('%s: condition key not found in the resource data', $conditionResource));
            }

            switch ($conditionOperator) {
                case '=':
                    $matched = $data[$conditionResource] == $conditionValue;
                    break;
                case '!=':
                    $matched = $data[$conditionResource] != $conditionValue;
                    break;
                case 'in':
                    if (!is_array($conditionValue)) {
                        throw new InvalidConditionException(
                            sprintf('%s: condition value must be an array', $conditionValue));
                    }

                    $matched = in_array($data[$conditionResource], $conditionValue, false);
                    break;
                case '!in':
                    if (!is_array($conditionValue)) {
                        throw new InvalidConditionException(
                            sprintf('%s: condition value must be an array', $conditionValue));
                    }

                    $matched = !in_array($data[$conditionResource], $conditionValue, false);
                    break;
                case '>':
                    $matched = $data[$conditionResource] > $conditionValue;
                    break;
                case '<':
                    $matched = $data[$conditionResource] < $conditionValue;
                    break;
                default:
                    throw new InvalidConditionException(sprintf('Unsupported operator: %s', $conditionOperator));
            }

            return $matched;
        }

        // Each of parent resource nodes
        foreach ($nodes as $k => $node) {
            $levelResource = implode(self::SEPARATOR, array_slice($nodes, $k + 1));

            if (static::isNodeArray($node, $data)) {
                foreach ($data[$node] as $j => $single) {
                    if (static::condition($data[$node][$j], $levelResource, $conditionOperator, $conditionValue)) {
                        return true;
                    }
                }
            } elseif (isset($data[$node])) {
                return static::condition($data[$node], $levelResource, $conditionOperator, $conditionValue);
            }
        }

        return $matched;
    }

    /**
     * @param string $node
     * @param array  $data
     *
     * @return bool
     */
    private static function isNodeArray(string &$node, array $data): bool
    {
        if (substr($node, -strlen(self::ARRAY_INDICATOR)) !== self::ARRAY_INDICATOR) {
            return false;
        }

        $node = trim($node, self::ARRAY_INDICATOR);
        return isset($data[$node]) && is_array($data[$node]);
    }
}
