<?php

namespace App\Models;

use App\Traits\RelatableNode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\MediaFile.
 *
 * @property int   $id
 * @property array $props
 * @mixin \Eloquent
 */
class MediaFile extends Model {
    use LogsActivity;
    use RelatableNode;
    protected $table     = 'file';

    public const TYPE = [
        'image'      => 0,
        'thumb'      => 1,
        'avatar'     => 2,
        'audio'      => 3,
        'video'      => 4,
        'weapp_code' => 5
    ];
    public const CONVERSION = [
        '300x300'        => 1,
        '300x300scroped' => 2,
        '1200xauto'      => 3
    ];

    protected $fillable = [
        'model_type',
        'model_id',
        'path',
        'name',
        'type',
        'mime_type',
        'disk',
        'size',
        'fid',
        'props',
    ];

    protected $casts = [
        'props' => 'json'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function hasThumb(string $conversionName): bool {
        $thumbs = $this->getThumbs();

        return $thumbs[$conversionName] ?? false;
    }

    public function getThumbs(): Collection {
        return collect($this->getProp('generated_conversions', []));
    }

    /**
     * Determine if the file item has a prop with the given name.
     * @param string $propertyName
     * @return bool
     */
    public function hasProp(string $propertyName): bool {
        return Arr::has($this->props, $propertyName);
    }

    /**
     * @param string $propName
     * @param null   $default
     * @return array|\ArrayAccess|mixed
     */
    public function getProp(string $propName, $default = null) {
        return Arr::get($this->props, $propName, $default);
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function setProp(string $name, $value): self {
        $props = $this->props;

        Arr::set($props, $name, $value);

        $this->props = $props;

        return $this;
    }

    /**
     * Remove one or many array items from prop using "dot" notation.
     * @param string $name
     * @return $this
     */
    public function forgetProp(string $name): self {
        $props = $this->props;

        Arr::forget($props, $name);

        $this->props = $props;

        return $this;
    }
}
