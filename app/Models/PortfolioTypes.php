<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioTypes extends Model
{
    public function type(){
        return $this->hasmany(Portfolio::class, 'type_id');
    }
}
