<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'Id_User';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'User_name',
        'email',
        'password',
        'role',
    ];

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'Create_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'Update_at';

    // Accessor to get full photo URL or default avatar
    public function getPhotoUrlAttribute()
    {
        // Return default avatar URL with user initials
        $name = urlencode($this->User_name ?? 'User');
        return "https://ui-avatars.com/api/?name={$name}&background=0D8ABC&color=fff";
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'Create_at' => 'datetime',
            'Update_at' => 'datetime',
        ];
    }

    /**
     * Get all laporan keuangan created by this user
     */
    public function laporanKeuanganCreated()
    {
        return $this->hasMany(LaporanKeuangan::class, 'created_by', $this->primaryKey);
    }

    /**
     * Get all laporan keuangan updated by this user
     */
    public function laporanKeuanganUpdated()
    {
        return $this->hasMany(LaporanKeuangan::class, 'updated_by', $this->primaryKey);
    }

    /**
     * Get all activity logs for this user
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityUser::class, 'user_id', 'id');
    }


    /**
     * Get all transaksi bank deleted by this user
     */
    public function transaksiBankDeleted()
    {
        return $this->hasMany(TransaksiBank::class, 'deleted_by', $this->primaryKey);
    }

    /**
     * Get all kode akun deleted by this user
     */
    public function kodeAkunDeleted()
    {
        return $this->hasMany(KodeAkun::class, 'deleted_by', $this->primaryKey);
    }

}
