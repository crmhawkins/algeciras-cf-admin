<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubStaff extends Model
{
    use HasFactory;

    protected $table = 'club_staff';

    protected $fillable = [
        'name','role','department','email','phone','photo','sort_order','visible_web',
    ];

    protected $casts = ['visible_web' => 'bool'];

    public function scopeVisible($q) { return $q->where('visible_web', true); }
    public function scopeByDept($q, string $d) { return $q->where('department', $d); }
}
