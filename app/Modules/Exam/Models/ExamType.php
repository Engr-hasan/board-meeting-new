<?phpnamespace App\Modules\Exam\Models;use App\Libraries\CommonFunction;use Illuminate\Database\Eloquent\Model;use Illuminate\Support\Facades\Auth;use Illuminate\Support\Facades\DB;class ExamType extends Model {    protected $table = 'ex_exam_type';    protected $fillable = [        'id',        'exam_name',        'is_archive',        'created_at',        'created_by',        'updated_at',        'updated_by'    ];    public static function boot() {        if (Auth::user()) {            parent::boot();            static::creating(function ($post) {                $post->created_by = CommonFunction::getUserId();                $post->updated_by = CommonFunction::getUserId();            });            static::updating(function ($post) {                $post->updated_by = CommonFunction::getUserId();            });        }    }    //**********************End of Model Class***************** */}