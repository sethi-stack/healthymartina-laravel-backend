<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Prophecy\Doubler\Generator\Node\ReturnTypeNode;

class Comment extends Model
{
    use CrudTrait;
    use SoftDeletes;
    use Notifiable;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'comments';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Return array of mail for mailing
     *
     * @return array
     */
    public function routeNotificationForMail($notification)
    {
        // return 'eric@braigo.mx';
        return $this->user->email;
    }

    public function isOwnedByCurrentUser()
    {
        if ($this->user->id == Auth::user()->id) {
            return true;
        } else {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function recipe()
    {   
        return $this->belongsTo('App\Models\Receta');
    }

    public function recipes()
    {
        return $this->belongsToMany('App\Models\Receta', 'comment_receta', 'comment_id', 'receta_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    public function getElapsedTimeAttribute()
    {
        $created =  Carbon::parse($this->created_at);
        $diff = $created->diffForHumans();
        return $diff;
    }

    public function getDayAttribute()
    {
        return Carbon::parse($this->created_at)->day;
    }

    public function getMonthAttribute()
    {
        $meses = ["ENE", "FEB", "MAR", "ABR", "MAY", "JUN", "JUL", "AGO", "SEP", "OCT", "NOV", "DIC"];
        return $meses[(Carbon::parse($this->created_at)->month)-1];
    }

    public function getRecipeNameAttribute()
    {
        return $this->recipes()->first()->titulo;
        // return Receta::find(DB::table('comment_receta')->whereCommentId($this->id)->first()->receta_id)->titulo;
    }

    public function getRecipeIdAttribute()
    {
        $receta_comment = DB::table('comment_receta')->whereCommentId($this->id)->first();
        if ($receta_comment) {
            $receta = Receta::find($receta_comment->id);
        } else {
            $receta = null;
        }
        return $receta ? $receta->id : null;
    }

    public function getRecipeNameAdminAttribute()
    {
        return Receta::find(DB::table('comment_receta')->whereCommentId($this->id)->first()->receta_id)->titulo;
    }
}
