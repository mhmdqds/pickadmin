<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFile extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $appends = ['image_full_url'];

    public function getImageFullUrlAttribute()
    {
        return Helpers::get_full_url('order/saved_files', $this->file_name, $this->storage ?? 'public');
    }
}
