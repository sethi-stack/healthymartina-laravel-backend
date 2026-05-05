<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarExportJob extends Model
{
    protected $table = 'calendar_export_jobs';

    protected $guarded = ['id'];

    protected $casts = [
        'request_payload' => 'array',
        'status_payload' => 'array',
        'completed_at' => 'datetime',
        'started_at' => 'datetime',
    ];
}
