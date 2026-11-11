<?php

namespace App\Helpers\QueryBuilder;

class NamedWhereIn
{
    public function __construct(private readonly string $field, private readonly string $listName, private readonly array $whereInList)
    {
    }

    public function __toString()
    {
        return $this->getQueryPart();
    }

    /**
     * Returns IN (:name1, :name2 , ..., :nameN).
     * If whereInList is empty returns empty string, so the raw query would be valid in any case.
     * @return string
     */
    public function getQueryPart() : string
    {
        $dictionary = $this->getNamedDictionary();

        if ($this->isValidQueryPart()) {
            return sprintf(' %s IN (:%s) ', $this->field, implode(', :', array_keys($dictionary)));
        }

        return '';
    }

    public function getNamedDictionary() : array
    {
        $whereInDictionary = [];
        $count = 1;
        foreach ($this->whereInList as $whereInUnit) {
            $unitName = $this->listName . $count;
            $whereInDictionary[$unitName] = $whereInUnit;
            $count++;
        }

        return $whereInDictionary;
    }

    public function isValidQueryPart() : bool
    {
        if (count($this->whereInList) > 0) {
            return true;
        }

        return false;
    }
}
