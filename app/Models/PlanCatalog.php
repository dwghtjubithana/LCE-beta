<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanCatalog extends Model
{
    protected $table = 'plan_catalog';

    protected $fillable = [
        'plan_key',
        'plan_label',
        'description',
        'rank',
        'is_active',
        'is_default',
        'available_for_signup',
        'available_for_upgrade',
        'requires_payment_proof',
    ];

    protected $casts = [
        'rank' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'available_for_signup' => 'boolean',
        'available_for_upgrade' => 'boolean',
        'requires_payment_proof' => 'boolean',
    ];
}
