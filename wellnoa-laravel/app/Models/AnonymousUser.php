<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnonymousUser extends Model
{
    protected $table = 'anonymous_users';
    protected $guarded = [];
    public $timestamps = true; // カラムが無ければ false
}