<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Prettus\Repository\Contracts\Transformable;
use Prettus\Repository\Traits\TransformableTrait;

class Tag extends Meta implements Transformable
{
    use TransformableTrait;

    protected $table = 'metas';

    protected static function boot()
    {
        static::addGlobalScope('tag', function (Builder $builder) {
            return $builder->where('type', '=', 'tag');
        });
    }

    public function __construct(array $attributes = [])
    {
        $attributes['type'] = 'tag';
        parent::__construct($attributes);
    }

    public function showUrl()
    {
        return route('tag.show', [$this->slug ? $this->slug : $this->id]);
    }

}
