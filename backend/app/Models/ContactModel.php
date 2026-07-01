<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactModel extends Model
{
    protected $table         = 'contacts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'channel_id',
        'external_contact_id',
        'display_name',
        'created_at',
    ];

    // created_at only in the draft schema; no updated_at column.
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'channel_id'          => 'required|is_natural_no_zero',
        // UNIQUE KEY uniq_contact (channel_id, external_contact_id) is COMPOSITE.
        // Same limitation as ChannelModel: is_unique cannot express two columns,
        // and a single-column rule would be wrong. The DB unique key enforces it.
        'external_contact_id' => 'required|string|max_length[191]',
        'display_name'        => 'permit_empty|string|max_length[191]',
        'created_at'          => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;
}
