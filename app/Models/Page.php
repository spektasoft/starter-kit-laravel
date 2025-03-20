<?php

namespace App\Models;

use App\Concerns\HasCreatorAttribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * @property string $id
 * @property string $creator_id
 * @property User $creator
 * @property string $title
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Page extends Model
{
    use HasCreatorAttribute;

    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory;

    use HasTranslations;
    use HasUlids;

    /**
     * @var string[]
     */
    public $translatable = ['title', 'content'];

    /*
     * @var string[]
     */
    protected $guarded = [];
}
