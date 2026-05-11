<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingDocumentForwardRecipient extends Model
{
    protected $table = 'incoming_document_forward_recipients';

    protected $fillable = [
        'incoming_document_id',
        'user_id',
        'date_received',
        'received_by',
        'received_in_behalf',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
