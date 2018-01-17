<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Joshwhatk\Cent\UserModel;

class User extends UserModel
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
     protected $fillable = [
         'email',
         'password',
         'name',
         'permissions',
     ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];


    /**
     * 指定のパーミッションにアクセスできるかを確認する
     * @param array or string チェックするパーミッション名。
     */
    public function hasAccessRoles($args)
    {
        // 管理者メールアドレスチェック
        if ($this->email === config('app.admin_email')) return true;
        // 全てのロールでチェック
        foreach ($this->roles as $role) {
            if ($role->hasAccess($args)) {
                return true;
            }
        }
        return false;
    }
}
