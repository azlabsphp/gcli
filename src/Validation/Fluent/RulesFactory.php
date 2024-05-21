<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\GCli\Validation\Fluent;

use Drewlabs\GCli\Contracts\ORMColumnDefinition as AbstractColumn;
use Drewlabs\GCli\Contracts\ORMModelDefinition as AbstractTable;
use Drewlabs\GCli\Validation\RulesFactory as ValidationRulesFactory;

class RulesFactory implements ValidationRulesFactory
{
    public function createRules(AbstractTable $table, bool $updates = false): array
    {
        return iterator_to_array((function (AbstractTable $t) use ($updates) {
            foreach ($t->columns() as $value) {
                yield $value->name() => $this->makeRule($value, $t->table(), $t->primaryKey(), $updates);
            }
        })($table));
    }

    /**
     * makes a list of rules for the column.
     *
     * @param bool $updates
     *
     * @throws \Exception
     *
     * @return array
     */
    private function makeRule(AbstractColumn $column, string $table, string $primaryKey = null, $updates = false)
    {
        $rules[] = !$column->required() ? Rules::NULLABLE : ($column->required() && $column->hasDefault() ?
            Rules::NULLABLE : ($updates ? Rules::SOMETIMES : $this->makeRequireRule($column, $primaryKey)));

        if ($column->name() === $primaryKey && $updates) {
            $rules[] = Rules::exists($table, $primaryKey);
        }

        $columnRules = Rules::get($column->type());
        $rules = [...$rules, ...$columnRules];
        if ($constraints = $column->foreignConstraint()) {
            $rules = [...$rules, ...Rules::get($constraints)];
        }
        if (($constraints = $column->unique()) && ($column->name() !== $primaryKey)) {
            $rules = [...$rules, Rules::unique($constraints, $primaryKey, $updates)];
        }

        return array_merge($rules);
    }

    private function makeRequireRule(AbstractColumn $column, string $key = null)
    {
        if ($column->name() === $key) {
            return Rules::SOMETIMES;
        }

        return null !== $key ? sprintf('%s:%s', Rules::REQUIRED_WITHOUT, $key) : Rules::REQUIRED;
    }
}
