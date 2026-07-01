<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table         = 'messages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'conversation_id',
        'direction',
        'sender_user_id',
        'external_message_id',
        'body',
        'attachments_json',
        'status',
        'created_at',
    ];

    // created_at only in the draft schema; no updated_at column.
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'conversation_id'     => 'required|is_natural_no_zero',
        'direction'           => 'required|in_list[inbound,outbound]',
        'sender_user_id'      => 'permit_empty|is_natural_no_zero',
        // UNIQUE KEY uniq_external_message (external_message_id) enforces webhook
        // idempotency at the DB level. Nullable, so it may be empty for outbound
        // rows awaiting a platform id; is_unique only checks non-null values.
        'external_message_id' => 'permit_empty|string|max_length[191]|is_unique[messages.external_message_id,id,{id}]',
        'body'                => 'permit_empty|string',
        // attachments_json is a JSON column; accept a string (caller json-encodes)
        // or null. Deeper JSON-shape validation belongs to the service layer.
        'attachments_json'    => 'permit_empty',
        'status'              => 'required|in_list[sent,delivered,read,failed]',
        'created_at'          => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;
}
