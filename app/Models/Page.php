<?php

namespace App\Models;

use App\Concerns\HandlesTranslatableAttributes;
use App\Concerns\HasCreatorAttribute;
use App\Enums\Page\Status;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $creator_id
 * @property User $creator
 * @property string $title
 * @property string $content
 * @property Status $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\PageFactory factory(...$parameters)
 */
class Page extends Model
{
    use HandlesTranslatableAttributes;

    use HasCreatorAttribute;

    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory;
    use HasUlids;

    /**
     * @var string[]
     */
    public $translatable = ['title', 'content'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => Status::class,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = ['creator_id', 'title', 'content', 'status'];

    public function isReferenced(): bool
    {
        return false;
    }
}
