<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'business_id',
        'name',
        'email',
        'password_hash',
        'role',
        'created_at',
    ];

    // created_at only in the draft schema; no updated_at column.
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'business_id'   => 'required|is_natural_no_zero',
        'name'          => 'required|string|max_length[191]',
        // is_unique respects the current row on update via {id} placeholder.
        'email'         => 'required|valid_email|max_length[191]|is_unique[users.email,id,{id}]',
        'password_hash' => 'required|string|max_length[255]',
        'role'          => 'required|in_list[owner,agent]',
        'created_at'    => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;
}
