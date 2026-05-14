<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ComplaintHistory;

class Complaint extends Model
{
    protected $fillable = [
        'ticket_number',
        'reporter_id',
        'assigned_to',
        'category_id',
        'priority_id',
        'title',
        'description',
        'status',
        'resolved_at',
        'closed_at',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function histories()
    {
        return $this->hasMany(ComplaintHistory::class)
            ->latest();
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)
            ->latest();
    }
}
