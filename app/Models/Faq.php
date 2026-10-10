<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_en',
        'question_bn',
        'answer_en',
        'answer_bn',
        'category',
        'service_id',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getQuestionAttribute()
    {
        $locale = app()->getLocale();

        return $locale === 'bn' && $this->question_bn ? $this->question_bn : $this->question_en;
    }

    public function getAnswerAttribute()
    {
        $locale = app()->getLocale();

        return $locale === 'bn' && $this->answer_bn ? $this->answer_bn : $this->answer_en;
    }
}
