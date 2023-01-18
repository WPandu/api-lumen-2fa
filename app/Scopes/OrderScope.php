<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrderScope implements Scope
{
    private $field;

    private $direction;

    public function __construct($field = 'created_at', $direction = 'DESC')
    {
        $this->field = $field;
        $this->direction = $direction;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    //phpcs:ignore
    public function apply(Builder $builder, Model $model)
    {
        $builder->orderBy($this->field, $this->direction);
    }
}
