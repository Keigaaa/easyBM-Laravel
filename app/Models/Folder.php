<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;


    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'idFolder');
    }

    /**
     * Returns the folders indide the current folder
     *
     * @return HasMany
     */
    public function folders()
    {
        return $this->hasmany(Folder::class, 'idParent');
    }

    /**
     * Returns the parent folder of the current folder
     *
     * @return BelongsTo
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class, 'idParent');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'idOwnerFolder');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
