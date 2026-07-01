<?php

namespace App\Models;

use CodeIgniter\Model;

class BusinessModel extends Model
{
    protected $table         = 'businesses';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'name',
        'created_at',
    ];

    // Draft schema has created_at only (no updated_at), so CI4's automatic
    // timestamp handling (which manages updated_at too) is disabled. Callers
    // set created_at explicitly (UTC per CLAUDE.md). $createdField is declared
    // for clarity / future setCreatedField() use.
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'name'       => 'required|string|max_length[191]',
        'created_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;
}
