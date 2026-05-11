<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLog extends Model
{
    protected $fillable = [
        'incoming_document_id',
        'user_id',
        'action_type',
        'action_timestamp',
        'status_from',
        'status_to',
        'related_user_id',
        'related_source_id',
        'remarks',
    ];

    protected $casts = [
        'action_timestamp' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(IncomingDocument::class, 'incoming_document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function relatedUser()
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    public function relatedSource()
    {
        return $this->belongsTo(DocumentSource::class, 'related_source_id');
    }
}
