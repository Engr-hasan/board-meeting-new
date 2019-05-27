<?phpnamespace App\Modules\Exam\Models;use App\Libraries\CommonFunction;use Illuminate\Database\Eloquent\Model;use Illuminate\Support\Facades\Auth;class Schedule extends Model {    protected $table = 'ex_schedule';    protected $fillable = [        'id',        'question_type',        'question_title',        'exam_available_from',        'exam_disabled_at',        'duration',        'mark_per_question',        'max_included_questions',        'no_of_question_for_examinee',        'exam_type',        'type_of_users',        'schedule_status',        'is_archive',        'created_by',        'updated_by',    ];    public static function boot() {        if (Auth::user()) {            parent::boot();            static::creating(function ($post) {                $post->created_by = CommonFunction::getUserId();                $post->updated_by = CommonFunction::getUserId();            });            static::updating(function ($post) {                $post->updated_by = CommonFunction::getUserId();            });        }    }    //**********************End of Model Class***************** */}