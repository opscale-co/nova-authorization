<?php

namespace Opscale\NovaAuthorization\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Opscale\Validations\Validatable;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUlids, Validatable;

    /**
     * @var array<string, array<int, string>>
     */
    public array $validationRules = [
        'name' => ['required', 'string', 'max:255', 'unique:permissions'],
        'guard_name' => ['required'],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];
}
