<?php

namespace App\Helpers\QueryBuilder;

class NamedWhereLikeOr
{
    private $field;
    private $paramBase;
    private $values;

    public function __construct($field, $paramBase, array $values) {
        $this->field = $field;
        $this->paramBase = $paramBase;
        $this->values = $values;
    }

    public function __toString() {
        if (empty($this->values)) {
            return '1=1'; // Fallback if no values
        }
        $conditions = [];
        foreach ($this->values as $index => $value) {
            $conditions[] = "{$this->field} LIKE :{$this->paramBase}{$index}";
        }
        return '(' . implode(' OR ', $conditions) . ')';
    }

    public function getNamedDictionary() {
        $dictionary = [];
        foreach ($this->values as $index => $value) {
            $dictionary["{$this->paramBase}{$index}"] = '%' . $value . '%';
        }
        return $dictionary;
    }
}
