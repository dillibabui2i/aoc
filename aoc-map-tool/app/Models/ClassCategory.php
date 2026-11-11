<?php

namespace App\Models;

use App\TrachVentClass;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $color
 * @property bool $is_system
 * @property-read bool $is_custom
 */
class ClassCategory extends Model
{
    public $table = 'class_categories';
    protected $fillable = ['name', 'color', 'is_system'];
    protected $casts = [
        'is_system' => 'boolean',
    ];
    protected $appends = ['is_custom'];

    protected function isCustom(): Attribute
    {
        return Attribute::get(fn () => !$this->is_system);
    }

    public function isInUse(): bool
    {
        return TrachVentClass::where('type', $this->id)->exists();
    }

    public function deleteIfCustom(): bool
    {
        if ($this->is_system || $this->isInUse()) {
            return false;
        }

        return (bool) $this->delete();
    }
}
