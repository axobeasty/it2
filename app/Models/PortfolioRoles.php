<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioRoles extends Model
{
    public function PortfolioRoles(){
        return $this->hasmany(Portfolio::class, 'role_id');
    }
}
