<?php

namespace App\Models;

use App\Models\Reaction;
use App\Models\Membresia;
use App\Notifications\MyResetPassword;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use App\Permissions\HasPermissionsTrait;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable
{
    use Notifiable;
    use HasPermissionsTrait; //Import The Trait
    use Billable;
    use CrudTrait;
    use HasApiTokens; // For API authentication
    
    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'name',
            ]
        ];
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new MyResetPassword($token, $this));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'last_name', 'username', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function preference()
    {
        return $this->hasOne('App\Models\NotificationPreference', 'user_id');
    }

    public function hasReacted($recipe_id, $reaction)
    {
        return Reaction::whereRecipeId($recipe_id)->whereUserId($this->id)->whereIsLike($reaction)->first() ? true : false;
    }

    public function getImageAttribute()
    {
        return Storage::url($this->attributes['image']);
    }
    public function getBimageAttribute()
    {
        return Storage::url($this->attributes['bimage']);
    }
    /*public function setImageAttribute($image)
    {
        $this->attributes['image'] =  Storage::put('users/' . $this->id, $image);
    }*/

    public function getCompletedProfileAttribute()
    {
        if ($this->name && $this->last_name && $this->email && $this->username) {
            return true;
        } else {
            return false;
        }
    }

    public function setImagenPrincipalAttribute($value)
    {
        $attribute_name = "imagen_principal";
        $disk = 'gcs'; // or use your own disk, defined in config/filesystems.php
        $destination_path = "users/profile_pictures"; // path relative to the disk above

        // if the image was erased
        if ($value == null) {
            // delete the image from disk
            \Storage::delete($this->{$attribute_name});

            // set null in the database column
            $this->attributes[$attribute_name] = null;
        }

        // if a base64 was sent, store it in the db
        if (starts_with($value, 'data:image')) {
            // 0. Make the image
            $image = \Image::make($value)->encode('jpg', 90);
            // 1. Generate a filename.
            $filename = md5($value . time()) . '.jpg';
            // 2. Store the image on disk.
            \Storage::put($destination_path . '/' . $filename, $image->stream());
            // 3. Save the public path to the database
            // but first, remove "public/" from the path, since we're pointing to it from the root folder
            // that way, what gets saved in the database is the user-accesible URL
            $public_destination_path = \Str::replaceFirst('public/', '', $destination_path);
            $this->attributes[$attribute_name] = $public_destination_path . '/' . $filename;
        }
    }

    public function getFullNameAttribute()
    {
        return $this->name . ' ' . $this->last_name;
    }

    public function membresia()
    {
        if ($this->subscription()) {
        $stripe_plan = $this->subscription()->stripe_plan;
         $membresia = Membresia::where('product',$stripe_plan)->first();
        return $membresia;
        }
        return '';
    }
     public function userRole() {
        return $this->belongsTo('App\Role', 'role_id');
    }
    // public function role()
    // {
    //     return $this->belongsTo(Role::class);
    // }
}
