<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomingDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'created_by',
        'document_reference_number',
        'date_received',
        'document_source_id',
        'document_from_type',
        'transaction_type',
        'drn',
        'document_type_id',
        'subject',
        'description',
        'current_status',
        'signed_by',
        'date_signed',
        'forwarded_to_user_id',
        'forwarded_to_source_id',
        'forwarded_to_group_id',
        'date_forwarded',
        'received_by',
        'forward_remarks',
        'received_remarks',
        'attachment_path',
        'priority_level',
        'deadline_date',
        'is_archived',
    ];

    protected $casts = [
        'date_received' => 'date',
        'date_signed' => 'date',
        'date_forwarded' => 'datetime',
        'deadline_date' => 'date',
        'is_archived' => 'boolean',
    ];

    public function source()
    {
        return $this->belongsTo(DocumentSource::class, 'document_source_id');
    }

    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function logs()
    {
        return $this->hasMany(DocumentLog::class, 'incoming_document_id')->orderBy('action_timestamp');
    }

    public function forwardedToUser()
    {
        return $this->belongsTo(User::class, 'forwarded_to_user_id');
    }

    public function forwardedToSource()
    {
        return $this->belongsTo(DocumentSource::class, 'forwarded_to_source_id');
    }

    public function forwardedToGroup()
    {
        return $this->belongsTo(Group::class, 'forwarded_to_group_id');
    }

    public function receivedByUser()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function forwardedRecipients()
    {
        return $this->hasMany(IncomingDocumentForwardRecipient::class, 'incoming_document_id');
    }
}
