<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const PLAN_FREE = 'free';

    public const PLAN_PRO = 'pro';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_notifications' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function isFree(): bool
    {
        return $this->plan === self::PLAN_FREE;
    }

    public function isPro(): bool
    {
        return $this->plan === self::PLAN_PRO;
    }

    public function planLimit(string $key): int|string|null
    {
        return config("plans.{$this->plan}.{$key}") ?? config("plans.".self::PLAN_FREE.".{$key}");
    }

    public function canUseFeature(string $feature): bool
    {
        return (bool) (config("plans.{$this->plan}.features.{$feature}") ?? false);
    }

    public function monitoringIntervalDays(string $frequencyKey): int
    {
        return $this->planLimit($frequencyKey) === 'weekly' ? 7 : 1;
    }
}
