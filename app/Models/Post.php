<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property-read string $computed_status  draft|scheduled|published
 * @property-read string $effective_meta_title
 * @property-read string $effective_meta_description
 */
class Post extends Model
{
    use HasFactory, SoftDeletes;

    /** @var array<int, string> */
    protected $fillable = [
        'title','slug','body','lead','toc_json',
        'show_ad_under_lead','show_ad_in_body','ad_in_body_max','show_ad_below',
        'meta_title','meta_description','og_image_path',
        'is_published','published_at','user_id','category_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'toc_json'            => 'array',
        'show_ad_under_lead'  => 'bool',
        'show_ad_in_body'     => 'bool',
        'show_ad_below'       => 'bool',
        'ad_in_body_max'      => 'int',
        'is_published'        => 'bool',
        'published_at'        => 'immutable_datetime', // 不変キャストの方が事故りにくい
    ];

    /* =========================
     | Relations
     |=========================*/

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /* =========================
     | Scopes
     |=========================*/

    /** 公開（現在時刻に到達済みのもののみ） */
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)
                 ->whereNotNull('published_at')
                 ->where('published_at', '<=', now());
    }

    /** 予約公開（未来時刻） */
    public function scopeScheduled(Builder $q): Builder
    {
        return $q->where('is_published', true)
                 ->whereNotNull('published_at')
                 ->where('published_at', '>', now());
    }

    /** キーワード検索（title/body の部分一致） */
    public function scopeKeyword(Builder $q, ?string $kw): Builder
    {
        if (!$kw) return $q;

        return $q->where(static function (Builder $w) use ($kw): void {
            $w->where('title', 'like', "%{$kw}%")
              ->orWhere('body',  'like', "%{$kw}%");
        });
    }

    /** ステータス絞り込み */
    public function scopeStatus(Builder $q, string $status = 'all'): Builder
    {
        return match ($status) {
            'published' => $q->published(),
            'scheduled' => $q->scheduled(),
            'draft'     => $q->where('is_published', false),
            'trashed'   => $q->onlyTrashed(),
            default     => $q,
        };
    }

    /** 公開日の範囲（公開日があるものに限定） */
    public function scopePublishedBetween(Builder $q, ?string $from, ?string $to): Builder
    {
        $q->whereNotNull('published_at');

        if ($from) {
            $q->where('published_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to) {
            $q->where('published_at', '<=', Carbon::parse($to)->endOfDay());
        }

        return $q;
    }

    /** 管理一覧用（読みやすさ重視のまとめ） */
    public function scopeForAdminIndex(Builder $q, array $f): Builder
    {
        $status = $f['status'] ?? 'all';

        $q->with('user:id,name')
          ->select(['id','user_id','title','slug','is_published','published_at','created_at'])
          ->keyword($f['q'] ?? null)
          ->status($status);

        // ゴミ箱/下書き以外だけ公開日フィルタ
        if (!in_array($status, ['draft','trashed'], true)) {
            $q->publishedBetween($f['from'] ?? null, $f['to'] ?? null);
        }

        // ソート許可リスト
        $allowedSort = ['created_at', 'published_at', 'title'];
        $sort = $f['sort'] ?? 'created_at';
        if (!in_array($sort, $allowedSort, true)) $sort = 'created_at';

        $dir = ($f['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $q->orderBy($sort, $dir);
    }

    /* =========================
     | Accessors (computed)
     |=========================*/

    /** 画面表示用：draft / scheduled / published を算出 */
    public function computedStatus(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (!$this->is_published) {
                    return 'draft';
                }
                if ($this->published_at && $this->published_at->greaterThan(now())) {
                    return 'scheduled';
                }
                return 'published';
            }
        );
    }

    /** SEO: メタタイトルのフォールバック */
    public function effectiveMetaTitle(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $site = config('seo.site_name', config('app.name'));
                $title = $this->meta_title ?: $this->title;
                return $title ? "{$title} | {$site}" : $site;
            }
        );
    }

    /** SEO: メタディスクリプションのフォールバック（160字） */
    public function effectiveMetaDescription(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $raw = $this->meta_description ?: Str::limit(strip_tags($this->body ?? ''), 160, '…');
                return trim(preg_replace('/\s+/u', ' ', $raw));
            }
        );
    }

    /* =========================
     | Model Events
     |=========================*/

    protected static function booted(): void
    {
        // 新規作成時：slug 自動採番（soft delete 含め重複回避）
        static::creating(function (Post $post): void {
            if (!blank($post->slug)) {
                return;
            }

            $base = Str::slug(Str::limit((string) $post->title, 70, ''));
            $base = $base !== '' ? $base : Str::lower(Str::random(8));

            $slug = $base;
            $i = 1;

            // withTrashed() を使い、論理削除分とも重複しないように
            while (static::withTrashed()->where('slug', $slug)->exists()) {
                $slug = "{$base}-{$i}";
                $i++;
            }

            $post->slug = $slug;
        });
    }
}
