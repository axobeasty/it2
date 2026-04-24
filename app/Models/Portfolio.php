<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    protected $fillable = [
        'number',
        'status',
        'type_id',
        'file_path',
        'title',
        'employee_id',
        'role_id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function portfolioType(): BelongsTo
    {
        return $this->belongsTo(PortfolioTypes::class, 'type_id');
    }

    public function portfolioRole(): BelongsTo
    {
        return $this->belongsTo(PortfolioRoles::class, 'role_id');
    }
}
