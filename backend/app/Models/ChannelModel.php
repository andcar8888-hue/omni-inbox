<?php

namespace App\Models;

use CodeIgniter\Model;

class ChannelModel extends Model
{
    protected $table         = 'channels';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'business_id',
        'platform',
        'external_account_id',
        // Encrypted platform tokens. Treated as a plain string here; encryption
        // is applied at the service layer (out of scope for this model).
        'credentials_encrypted',
        'created_at',
    ];

    // created_at only in the draft schema; no updated_at column.
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'business_id'         => 'required|is_natural_no_zero',
        'platform'            => 'required|in_list[whatsapp,messenger,instagram,telegram,tiktok]',
        // UNIQUE KEY uniq_channel (platform, external_account_id) is COMPOSITE.
        // CI4's is_unique cannot express a two-column uniqueness constraint, and a
        // single-column is_unique[channels.external_account_id] would be WRONG here
        // (it would reject the same account id used on a different platform, which
        // the DB permits). The composite uniqueness is enforced by the DB unique
        // key; the service layer should catch the DB error and map it to a 409.
        'external_account_id' => 'required|string|max_length[191]',
        'credentials_encrypted' => 'required|string',
        'created_at'          => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;
}
