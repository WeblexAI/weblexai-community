<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupArchive extends Model
{
    protected $guarded = ['id'];

    protected $hidden = [
        'archive_password',
    ];

    protected $casts = [
        'archive_password' => 'encrypted',
    ];
}
