<?php

namespace Drewlabs\GCli\DBAL\T;


class ForeignKeyConstraint
{

    private $foreignKeys = [];

    /**
     * Class constructor
     * 
     * @param array $foreignKeys 
     */
    public function __construct(array $foreignKeys)
    {
        $this->foreignKeys = $foreignKeys;
    }


    /**
     * Creates new class instance
     * 
     * @param array $values 
     * @return static 
     */
    public static function new(array $values)
    {
        return new static($values);
    }


    /**
     * binds foreign key constraint to colum instance
     * 
     * @param array|\Traversable<Column> $columns
     * 
     * @return array 
     */
    public function bind($columns)
    {
        $foreignKeys = empty($this->foreignKeys) ? [] : $this->foreignKeys;
        if (!empty($foreignKeys)) {
            $foreignKeys = iterator_to_array((static function ($keys) {
                foreach ($keys as $key) {
                    yield $key->getLocalColumns()[0] => new ForeignKey(
                        $key->getLocalTableName(),
                        $key->getLocalColumns(),
                        $key->getForeignTableName(),
                        $key->getForeignColumns(),
                        $key->getName()
                    );
                }
            })($foreignKeys));
        }
        $columns = \is_array($columns) ? $columns : iterator_to_array($columns);
        foreach ($foreignKeys as $key => $value) {
            /** @var Column $column */
            if (!is_null($column = $columns[$key] ?? null)) {
                $columns = array_merge($columns, [$key => $column->setForeignKey($value)]);
            }
        }

        return $columns;
    }
}