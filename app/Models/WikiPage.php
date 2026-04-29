<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WikiPage extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'body',
        'parent_id',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }

    /** Роли, которым разрешён просмотр страницы. Пусто — доступ у всех с правом «Wiki: просмотр». */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Roles::class, 'wiki_page_role', 'wiki_page_id', 'role_id')
            ->withTimestamps();
    }

    public function isReadableByWikiReader(Employee $user): bool
    {
        if ($user->canAccessPage('knowledge_wiki_edit')) {
            return true;
        }
        if (! $user->canAccessPage('knowledge_wiki')) {
            return false;
        }
        if ($this->relationLoaded('roles')) {
            if ($this->roles->isEmpty()) {
                return true;
            }
            $roleId = (int) $user->role_id;

            return $roleId > 0 && $this->roles->contains('id', $roleId);
        }
        if (! $this->roles()->exists()) {
            return true;
        }
        $roleId = (int) $user->role_id;

        return $roleId > 0 && $this->roles()->where('roles.id', $roleId)->exists();
    }

    /**
     * Страницы, доступные читателю wiki (не редактору): без ограничений по ролям или с его ролью.
     */
    public function scopeVisibleToWikiReader(Builder $query, Employee $user): void
    {
        if ($user->canAccessPage('knowledge_wiki_edit')) {
            return;
        }
        if (! $user->canAccessPage('knowledge_wiki')) {
            $query->whereRaw('1 = 0');

            return;
        }
        $roleId = (int) $user->role_id;
        $query->where(function (Builder $q) use ($roleId) {
            $q->whereDoesntHave('roles')
                ->orWhereHas('roles', fn (Builder $r) => $r->where('roles.id', $roleId));
        });
    }
}
