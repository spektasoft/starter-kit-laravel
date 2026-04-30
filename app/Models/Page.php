<?php

namespace App\Models;

use App\Concerns\HandlesTranslatableAttributes;
use App\Enums\Page\Status;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $creator_id
 * @property User $creator
 * @property string $title
 * @property string $content
 * @property Status $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static PageFactory factory(...$parameters)
 */
class Page extends Model
{
    use HandlesTranslatableAttributes;

    /** @use HasFactory<PageFactory> */
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function isReferenced(): bool
    {
        return false;
    }
}
