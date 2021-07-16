<?php

namespace Cdinopol\DataGuard;

use Cdinopol\DataGuard\Exception\InvalidConditionException;

class DataGuard
{
    public const SEPARATOR       = ':';
    public const RESOURCE_SPLIT  = '|';
    public const WILDCARD        = '*';
    public const ARRAY_INDICATOR = '[]';

    /**
     * @param array $data
     * @param string $resource
     * @param string|array $conditions
     * @param mixed $mask
     *
     * @return array
     *
     * @throws InvalidConditionException
     */
    public static function protect(array $data, string $resource, $conditions, $mask = null): array
    {
        $nodes = explode(self::SEPARATOR, $resource);

        // Final resource node match against condition
        if (count($nodes) === 1) {
            $splits = explode(self::RESOURCE_SPLIT, $resource);
            foreach ($splits as $split) {
                if (static::isNodeArray($split, $data)) {
                    foreach ($data[$split] as $j => $single) {
                        if (static::conditions($data[$split][$j], $conditions)) {
                            if ($mask) {
                                $data[$split][$j] = $mask;
                            } else {
                                unset($data[$split][$j]);
                            }
                        }
                    }

                    // Reindex
                    $data[$split] = array_values($data[$split]);
                } elseif (isset($data[$split])) {
                    if (static::conditions($data[$split], $conditions)) {
                        if ($mask) {
                            $data[$split] = $mask;
                        } else {
                            unset($data[$split]);
                        }
                    }
                }
            }

            return $data;
        }

        // Each of parent resource nodes
        foreach ($nodes as $k => $node) {
            $levelResource = implode(self::SEPARATOR, array_slice($nodes, $k + 1));

            $splits = explode(self::RESOURCE_SPLIT, $node);
            foreach ($splits as $split) {
                if (static::isNodeArray($split, $data)) {
                    foreach ($data[$split] as $j => $single) {
                        $data[$split][$j] = static::protect($data[$split][$j], $levelResource, $conditions, $mask);
                    }
                } elseif (isset($data[$split])) {
                    $data[$split] = static::protect($data[$split], $levelResource, $conditions, $mask);
                }
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
        if (!is_array($conditions) && $conditions !== self::WILDCARD) {
            throw new InvalidConditionException(sprintf('Conditions must be an array or "%s"', self::WILDCARD));
        }

        if ($conditions === self::WILDCARD) {
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
                case 'regex':
                    $matched = (bool) preg_match($conditionValue, $data[$conditionResource]);
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
