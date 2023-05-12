<?php

namespace App\Models;

//use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Micropost;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function microposts()
    {
        return $this->hasMany(Micropost::class);
    }
    public function loadRelationshipCounts()
    {
        $this->loadCount(['microposts', 'followings', 'followers','favorites']);
    }

    
    /**
     * このユーザがフォロー中のユーザ。（Userモデルとの関係を定義）
     */
    public function followings()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'user_id', 'follow_id')->withTimestamps();
    }
    
    /**
     * このユーザをフォロー中のユーザ。（Userモデルとの関係を定義）
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follow', 'follow_id', 'user_id')->withTimestamps();
    }
    
    public function follow($userId)
    {
        $exist = $this->is_following($userId);
        $its_me = $this->id == $userId;
        
        if ($exist || $its_me) {
            return false;
        } else {
            $this->followings()->attach($userId);
            return true;
        }
    }
    
    /**
     * $userIdで指定されたユーザをアンフォローする。
     * 
     * @param  int $usereId
     * @return bool
     */
    public function unfollow($userId)
    {
        $exist = $this->is_following($userId);
        $its_me = $this->id == $userId;
        
        if ($exist && !$its_me) {
            $this->followings()->detach($userId);
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 指定された$userIdのユーザをこのユーザがフォロー中であるか調べる。フォロー中ならtrueを返す。
     * 
     * @param  int $userId
     * @return bool
     */
    public function is_following($userId)
    {
        return $this->followings()->where('follow_id', $userId)->exists();
    }
    
    public function feed_microposts()
    {
        // このユーザがフォロー中のユーザのidを取得して配列にする
        $userIds = $this->followings()->pluck('users.id')->toArray();
        // このユーザのidもその配列に追加
        $userIds[] = $this->id;
        // それらのユーザが所有する投稿に絞り込む
        return Micropost::whereIn('user_id', $userIds);
    }
    
    
    
    public function favorites(){
         return $this->belongsToMany(Micropost::class, 'favorites', 'user_id', 'micropost_id');
    }
    
    
    public function favorite($micropost_id)
    {
        $exist = $this->is_favorite($micropost_id);
        //$its_this = $this->feed_favorite($micropost_id) ;
        
        if ($exist) {
            return false;
        } else {
            $this->favorites()->attach($micropost_id);
            return true;
        }
    }
    
    /**
     * $userIdで指定されたユーザをアンフォローする。
     * 
     * @param  int $usereId
     * @return bool
     */
    public function unfavorite($micropost_id)
    {
        $exist = $this->is_favorite($micropost_id);
        //$its_this = $this->id == $micropost_id;
        
        if ($exist) {
            $this->favorites()->detach($micropost_id);
            return true;
        } else {
            return false;
        }
    }
    public function is_favorite($micropost_id)
    {
        return $this->favorites()->where('micropost_id', $micropost_id)->exists();
    }
    
    public function feed_favorite($micropost_id)
    {
        // このユーザがお気に入りしているidを取得して配列にする
        $microIds = $this->favorites()->pluck('favorites.micropost_id')->toArray();
        // それらのユーザが所有する投稿に絞り込む
        return $this->favorites()->where($this->id, $microIds)->exists();
    }
}
