<?php

namespace LyonStahl\SoqlBuilder;

use LyonStahl\SoqlBuilder\Exceptions\InvalidQueryException;

/**
 * A fluent API for building Salesforce SOQL queries.
 */
class SoqlBuilder
{
    /**
     * Applied fields.
     *
     * @var string[]
     */
    private $fields = [];

    /**
     * Target object.
     *
     * @var string
     */
    private $object;

    /**
     * Where conditions.
     *
     * @var string[]
     */
    private $where = [];

    /**
     * Limit of records.
     *
     * @var int
     */
    private $limit;

    /**
     * Offset of records.
     *
     * @var int
     */
    private $offset;

    /**
     * Order by fields.
     *
     * @var stroing[]
     */
    private $orders = [];

    /**
     * Grouped expressions start.
     *
     * @var int[]
     */
    private $groupedConditionalStart = [];

    /**
     * Grouped expressions end.
     *
     * @var int[]
     */
    private $groupedConditionalEnd = [];

    /**
     * Select fields from object.
     * Call addSelect() instead if the builder was already instantiated.
     *
     * @param string[] $fields Fields to select
     *
     * @return $this
     */
    public static function select(array $fields): self
    {
        return (new self())->addSelect($fields);
    }

    /**
     * Add a field(s) to select.
     *
     * @param string|string[] $field Field to select
     *
     * @return $this
     */
    public function addSelect($field): self
    {
        if (is_array($field)) {
            $this->fields = array_merge($this->fields, $field);
        } else {
            $this->fields[] = $field;
        }

        return $this;
    }

    /**
     * Set the target object.
     * Call setFrom() method instead if the builder was already instantiated.
     *
     * @param string $object Target object
     *
     * @return $this
     */
    public static function from(string $object): self
    {
        return (new self())->setFrom($object);
    }

    /**
     * Set the target object.
     *
     * @param string $object Target object
     *
     * @return $this
     */
    public function setFrom(string $object): self
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Begin a grouped expression based on current where conditions.
     *
     * @return $this
     */
    public function startWhere(): self
    {
        if (empty($this->where)) {
            $this->groupedConditionalStart[] = 0;
        } else {
            $this->groupedConditionalStart[] = array_key_last($this->where) + 1;
        }

        return $this;
    }

    /**
     * End a grouped expression.
     *
     * @return $this
     */
    public function endWhere(): self
    {
        $this->groupedConditionalEnd[] = array_key_last($this->where);

        return $this;
    }

    /**
     * Add a where condition.
     *
     * @param string $column   Column name
     * @param string $operator Operator
     * @param mixed  $value    Value
     * @param string $boolean  Boolean operator
     *
     * @return $this
     */
    public function where(string $column, string $operator, $value, string $boolean = 'AND'): self
    {
        $this->where[] = [$column, $operator, $this->prepareWhereValue($value), $boolean];

        return $this;
    }

    /**
     * Add a where condition using OR.
     *
     * @param string $column   Column name
     * @param string $operator Operator
     * @param mixed  $value    Value
     *
     * @return $this
     */
    public function orWhere(string $column, string $operator, $value): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add a where condition with a datetime value.
     *
     * @param string $column   Column name
     * @param string $operator Operator
     * @param mixed  $value    Value
     * @param string $boolean  Boolean operator
     *
     * @return $this
     */
    public function whereDate(string $column, string $operator, $value, string $boolean = 'AND'): self
    {
        $this->where[] = [$column, $operator, $this->prepareWhereValue($value, 'date'), $boolean];

        return $this;
    }

    /**
     * Add a where condition with a datetime value using OR.
     *
     * @param string $column   Column name
     * @param string $operator Operator
     * @param mixed  $value    Value
     *
     * @return $this
     */
    public function orWhereDate(string $column, string $operator, $value): self
    {
        return $this->whereDate($column, $operator, $value, 'OR');
    }

    /**
     * Add multiple where conditions.
     *
     * @param array  $conditions Array of conditions
     * @param string $boolean    Boolean operator
     *
     * @return $this
     */
    public function whereMultiple(array $conditions, string $boolean = 'AND'): self
    {
        foreach ($conditions as $condition) {
            $this->where($condition[0], $condition[1], $condition[2], $boolean);
        }

        return $this;
    }

    /**
     * Add where condition with IN operator.
     *
     * @param string $column       Column name
     * @param array  $restrictions Restrictions
     * @param string $boolean      Boolean operator
     * @param bool   $not          If true, use 'NOT IN' operator
     *
     * @return $this
     */
    public function whereIn(string $column, array $restrictions, string $boolean = 'AND', bool $not = false): self
    {
        foreach ($restrictions as &$restriction) {
            $restriction = $this->prepareWhereValue($restriction);
        }

        $operator = !$not ? 'IN' : 'NOT IN';

        $this->where[] = [$column, $operator, '('.implode(', ', $restrictions).')', $boolean];

        return $this;
    }

    /**
     * Add where condition with NOT IN operator.
     *
     * @param string $column       Column name
     * @param array  $restrictions Restrictions
     *
     * @return $this
     */
    public function whereNotIn(string $column, array $restrictions): self
    {
        $this->whereIn($column, $restrictions, 'AND', true);

        return $this;
    }

    /**
     * Add where condition with IN operator using OR.
     *
     * @param string $column       Column name
     * @param array  $restrictions Restrictions
     * @param bool   $not          If true, use 'NOT IN' operator
     *
     * @return $this
     */
    public function orWhereIn(string $column, array $restrictions, bool $not = false): self
    {
        $this->whereIn($column, $restrictions, 'OR', $not);

        return $this;
    }

    /**
     * Add where condition with NOT IN operator using OR.
     *
     * @param string $column       Column name
     * @param array  $restrictions Restrictions
     *
     * @return $this
     */
    public function orWhereNotIn(string $column, array $restrictions): self
    {
        $this->whereIn($column, $restrictions, 'OR', true);

        return $this;
    }

    /**
     * Add where condition with function.
     *
     * @param string        $column   Column name
     * @param string        $function Function name
     * @param mixed|mixed[] $value    Value or array of values
     * @param string        $boolean  Boolean operator
     *
     * @return $this
     */
    public function whereFunction(string $column, string $function, $value, string $boolean = 'AND'): self
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->prepareWhereValue($item);
            }
            $value = implode(', ', $value);
        } else {
            $value = $this->prepareWhereValue($value);
        }

        $this->where[] = [$column, null, $function.'('.$value.')', $boolean];

        return $this;
    }

    /**
     * Retrieve the number of expressions at a given index.
     */
    private function getGroupExpressionsAtIndex(array $expressionLocations, int $index): int
    {
        if (empty($expressionLocations)) {
            return 0;
        }

        return count(array_filter($expressionLocations, function ($expressionLocation) use ($index) {
            return $expressionLocation === $index;
        }));
    }

    /**
     * Prepare where value.
     */
    private function prepareWhereValue($value, $forceType = null): string
    {
        if ($forceType === 'date') {
            return $value;
        }

        if (is_string($value)) {
            $value = "'".$value."'";
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === null) {
            $value = 'null';
        }

        return $value;
    }

    /**
     * Set the limit for the query.
     *
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set the offset for the query.
     *
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Add the order by for the query.
     *
     * @param string $column    Column name
     * @param string $direction Direction (ASC or DESC)
     *
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = $column.' '.$direction;

        return $this;
    }

    /**
     * Add the descending order by for the query.
     *
     * @param string $column Column name
     *
     * @return $this
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Compose the query based on the current data.
     */
    public function toSoql(): string
    {
        if (!$this->object) {
            throw new InvalidQueryException('Query must contains sObject name. Use from() or setFrom() method to set it.');
        }
        if (!$this->fields) {
            throw new InvalidQueryException('Query must contains fields for select. Use select() or addSelect() method to set them.');
        }
        if (count($this->groupedConditionalStart) !== count($this->groupedConditionalEnd)) {
            throw new InvalidQueryException('Unmatched parenthesis for grouped expressions. Make sure to call startWhere() and endWhere().');
        }

        $soql = 'SELECT ';
        $soql .= implode(', ', array_unique($this->fields));
        $soql .= ' FROM '.$this->object;

        if (count($this->where) > 0) {
            $soql .= ' WHERE ';
        }

        foreach ($this->where as $i => $iValue) {
            $iValue[0] = str_repeat('(', $this->getGroupExpressionsAtIndex($this->groupedConditionalStart, $i)).$iValue[0];
            $iValue[2] .= str_repeat(')', $this->getGroupExpressionsAtIndex($this->groupedConditionalEnd, $i));
            if ($i !== 0) {
                $soql .= ' '.$iValue[3].' ';
            }
            $soql .= implode(' ', array_filter([$iValue[0], $iValue[1], $iValue[2]], function ($item) {
                return $item !== null;
            })
            );
        }

        if (count($this->orders) > 0) {
            $soql .= ' ORDER BY ';
            $soql .= implode(', ', $this->orders);
        }

        if ($this->limit) {
            $soql .= ' LIMIT '.$this->limit;
        }

        if ($this->offset) {
            $soql .= ' OFFSET '.$this->offset;
        }

        return $soql;
    }

    public function __toString(): string
    {
        return $this->toSoql();
    }
}
