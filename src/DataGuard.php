<?php

namespace Cdinopol\DataGuard;

use Cdinopol\DataGuard\Exception\InvalidConditionException;

class DataGuard
{
    public const SEPARATOR       = ':';
    public const RESOURCE_SPLIT  = '|';
    public const MATCH_ALL       = '*';
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
        // Validate conditions format
        if (!is_array($conditions) && $conditions !== self::MATCH_ALL) {
            throw new InvalidConditionException(
                sprintf('Conditions must be an array or "%s"', self::MATCH_ALL)
            );
        }
        
        // Always return true on MATCH_ALL
        if ($conditions === self::MATCH_ALL) {
            return true;
        }

        // Match every single condition if array of conditions is provided
        foreach ($conditions as $condition) {
            // Validate individual condition format
            if (!is_array($condition)) {
                throw new InvalidConditionException(
                    'Conditions must be an array of array condition'
                );
            }

            // Validate condition segments, must be either 2 or 3
            $conditionCount = count($condition);
            if ($conditionCount < 2 || $conditionCount > 3) {
                throw new InvalidConditionException(
                    'Condition must consist of 2 or 3 segments: [1] resource key condition (optional), [2] operator, [3] value'
                );
            }

            // Match against resource if 2 segment condition is provided
            if ($conditionCount === 2) {
                $data = ['search' => $data];
                array_unshift($condition, 'search');
            }

            // Immediately return false if one of condition is not true
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
            $splits = explode(self::RESOURCE_SPLIT, $conditionResource);
            foreach ($splits as $split) {
                // Ignore if Condition key not is found in the resource data
                if (!isset($data[$split])) {
                    continue;
                }

                // Operator evaluation
                switch ($conditionOperator) {
                    case '=':
                        $matched = $data[$split] == $conditionValue;
                        break;
                    case '!=':
                        $matched = $data[$split] != $conditionValue;
                        break;
                    case 'in':
                        if (!is_array($conditionValue)) {
                            throw new InvalidConditionException(
                                sprintf('%s: condition value must be an array', $conditionValue)
                            );
                        }

                        $matched = in_array($data[$split], $conditionValue, false);
                        break;
                    case '!in':
                        if (!is_array($conditionValue)) {
                            throw new InvalidConditionException(
                                sprintf('%s: condition value must be an array', $conditionValue)
                            );
                        }

                        $matched = !in_array($data[$split], $conditionValue, false);
                        break;
                    case '>':
                        $matched = $data[$split] > $conditionValue;
                        break;
                    case '<':
                        $matched = $data[$split] < $conditionValue;
                        break;
                    case 'regex':
                        $matched = (bool)preg_match($conditionValue, $data[$split]);
                        break;
                    default:
                        throw new InvalidConditionException(
                            sprintf('Unsupported operator: %s', $conditionOperator)
                        );
                }
            }

            return $matched;
        }

        // Each of parent resource nodes
        foreach ($nodes as $k => $node) {
            $levelResource = implode(self::SEPARATOR, array_slice($nodes, $k + 1));

            $splits = explode(self::RESOURCE_SPLIT, $node);
            foreach ($splits as $split) {
                if (static::isNodeArray($split, $data)) {
                    foreach ($data[$split] as $j => $single) {
                        if (static::condition($data[$split][$j], $levelResource, $conditionOperator, $conditionValue)) {
                            return true;
                        }
                    }
                } elseif (isset($data[$split])) {
                    return static::condition($data[$split], $levelResource, $conditionOperator, $conditionValue);
                }
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
