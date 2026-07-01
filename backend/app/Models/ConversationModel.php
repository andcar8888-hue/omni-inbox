<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversationModel extends Model
{
    protected $table         = 'conversations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'channel_id',
        'contact_id',
        'assigned_user_id',
        'status',
        'last_message_at',
        'unread_count',
    ];

    // The draft schema defines NO created_at/updated_at on conversations, so
    // automatic timestamps are disabled and neither field is declared. Ordering
    // is done by last_message_at, which the app maintains explicitly.
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'channel_id'       => 'required|is_natural_no_zero',
        'contact_id'       => 'required|is_natural_no_zero',
        'assigned_user_id' => 'permit_empty|is_natural_no_zero',
        'status'           => 'required|in_list[open,pending,closed]',
        'last_message_at'  => 'required|valid_date[Y-m-d H:i:s]',
        'unread_count'     => 'permit_empty|is_natural',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;
}
