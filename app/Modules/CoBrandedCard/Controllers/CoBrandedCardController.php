<?php

namespace App\Modules\CoBrandedCard\Controllers;

use App\Http\Controllers\Controller;
use App\Libraries\ACL;
use App\Libraries\CommonFunction;
use App\Libraries\Encryption;
use App\Libraries\UtilFunction;
use App\Modules\Apps\Models\AppDocuments;
use App\Modules\Apps\Models\DocInfo;
use App\Modules\Apps\Models\EmailQueue;
use App\Modules\Apps\Models\pdfQueue;
use App\Modules\Apps\Models\pdfSignatureQrcode;
use App\Modules\Apps\Models\ProcessListHist;
use App\Modules\CoBrandedCard\Models\CoBrandedBusinessPurpose;
use App\Modules\CoBrandedCard\Models\CoBrandedBusinessPurposeSave;
use App\Modules\CoBrandedCard\Models\CoBrandedCardApplicationRecommendation;
use App\Modules\LimitRenewal\Models\LimitRenewal;
use App\Modules\LoanLocator\Models\LoanType;
use App\Modules\ProcessPath\Models\ProcessList;
use App\Modules\ProcessPath\Models\ProcessStatus;
use App\Modules\ProcessPath\Models\ProcessType;
use App\Modules\Settings\Models\BankBranch;
use App\Modules\Settings\Models\PdfPrintRequest;
use App\Modules\Settings\Models\PdfServerInfo;
use App\Modules\Settings\Models\PdfServiceInfo;
use App\Modules\Signup\Models\MemberShipNumber;
use App\Modules\SpaceAllocation\Models\Sponsors;
use App\Modules\SpaceAllocation\Models\TradeBody;
use App\Modules\Settings\Models\Bank;
use App\Modules\Settings\Models\Configuration;
use App\Modules\Users\Models\AreaInfo;
use App\Modules\Users\Models\CompanyInfo;
use App\Modules\Users\Models\Users;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Mockery\CountValidator\Exception;
use mPDF;
use yajra\Datatables\Datatables;
use \ParagonIE\EasyRSA\KeyPair;
use \ParagonIE\EasyRSA\EasyRSA;

class CoBrandedCardController extends Controller
{

    protected $process_type_id;

    public function __construct()
    {
        if (Session::has('lang'))
            App::setLocale(Session::get('lang'));
        $this->process_type_id = 3; // 3 is CoBrandedCard process type
    }

    /*
     * Show application form
     */
    public function applicationForm()
    {
        try{


            $data['CoBrandedBusinessPurpose'] = CoBrandedBusinessPurpose::where('is_active',1)->orderby('id','DESC')->get();
            $data['companyInfo'] = CompanyInfo::where('is_approved',1)->where('id', Auth::user()->user_sub_type)->first();
            $data['memberShipNo'] = MemberShipNumber::where('is_active',1)->where('user_id', Auth::user()->id)->first();
            $data['bank'] = ['' => 'Select Bank '] + Bank::orderby('name')->where('is_active', 1)->lists('name', 'id')->all();
            return view("CoBrandedCard::application-form",compact('data'));

        } catch (Exception $e) {
            Session::flash('error', CommonFunction::showErrorPublic($e->getMessage()));
            return Redirect::back()->withInput();
        }
    }


    /*
     * Application view and edit
     */

    public function applicationViewEdit($applicationId)
    {
        $applicationId = Encryption::decodeId($applicationId);
        $process_type_id = $this->process_type_id;
        $user_type = Auth::user()->user_type;
        if (in_array($user_type,['5x505'])) {
            $company_id = CommonFunction::getUserSubTypeWithZero();
            $data = ProcessList::where([
                'ref_id' => $applicationId,
                'process_type_id' => $process_type_id,
            ])->first(['status_id', 'created_by','company_id','tracking_no']);

            if ($data->company_id == $company_id && in_array($data->status_id, [-1, 5, 6])) {
                $openMode='edit';
            }else{
                $openMode='view';
            }
        } else{
            $openMode='view';
        }
        try {

            $process_type_id = $this->process_type_id;


            $application = ProcessList::leftJoin('space_allocation as apps', 'apps.id', '=', 'process_list.ref_id')
                ->leftJoin('user_desk', 'user_desk.id', '=', 'process_list.desk_id')
                ->leftJoin('process_status as ps', function ($join) use ($process_type_id) {
                    $join->on('ps.id', '=', 'process_list.status_id');
                    $join->on('ps.process_type_id', '=', DB::raw($process_type_id));
                })
                //->leftJoin('park_info as pi', 'pi.id', '=', 'process_list.park_id')
                ->where('process_list.ref_id', $applicationId)
                ->where('process_list.process_type_id', $process_type_id)
                ->first([
                    'process_list.id as process_list_id',
                    'process_list.desk_id',
                    'process_list.status_id',
                    'process_list.locked_by',
                    'process_list.locked_at',
                    'process_list.ref_id',
                    'process_list.tracking_no',
                    'user_desk.desk_name',
                    'ps.status_name',
                    'ps.color',
                    // 'pi.id as parkId',
                    'apps.*',
                ]);


            // Lock application by current user

            $userDeskIds = CommonFunction::getUserDeskIds();
            if (in_array($application->status_id, [1,2,3,4,5,6]) && in_array($application->desk_id, $userDeskIds)) {
                ProcessList::where('ref_id', $applicationId)->update([
                    'locked_by' => Auth::user()->id,
                    'locked_at' => date('Y-m-d H:i:s')
                ]);
            }


            $document = DocInfo::where('process_type_id', $this->process_type_id)->orderBy('order')->get();
            $banks = Bank::where('is_archive', 0)->where('is_active', 1)->orderBy('name')->lists('name', 'id');
            $challanReg = Configuration::where('caption', 'CHALLAN_REG')->first(['value', 'value2']);
            $process_history = DB::select(DB::raw("select  `process_list_hist`.`desk_id`,`as`.`status_name`,
                                `process_list_hist`.`process_id`,                           
                                if(`process_list_hist`.`desk_id`=0,\"-\",`ud`.`desk_name`) `deskname`,
                                `users`.`user_full_name`, 
                                `process_list_hist`.`updated_by`, 
                                `process_list_hist`.`status_id`, 
                                `process_list_hist`.`process_desc`, 
                                `process_list_hist`.`process_id`, 
                                `process_list_hist`.`updated_at`,
                                 group_concat(`pd`.`file`) as files
                                
                    
                                from `process_list_hist`
                                left join `process_documents` as `pd` on `process_list_hist`.`id` = `pd`.`process_hist_id`
                                left join `user_desk` as `ud` on `process_list_hist`.`desk_id` = `ud`.`id`
                                left join `users` on `process_list_hist`.`updated_by` = `users`.`id`     
                                
                                left join `process_status` as `as` on `process_list_hist`.`status_id` = `as`.`id`
                                and `process_list_hist`.`process_type` = `as`.`process_type_id`
                                where `process_list_hist`.`process_id`  = '$application->process_list_id'
                                and `process_list_hist`.`process_type` = '$this->process_type_id' 
                               
                                and `process_list_hist`.`status_id` != -1
                    group by `process_list_hist`.`process_id`,`process_list_hist`.`desk_id`, `process_list_hist`.`status_id`, process_list_hist.updated_at
                    order by process_list_hist.updated_at desc

                    "));
            $appInfo = ProcessList::leftJoin('co_branded_card_application_recommendation as apps', 'apps.id', '=', 'process_list.ref_id')
                ->leftJoin('user_desk', 'user_desk.id', '=', 'process_list.desk_id')
                ->leftJoin('process_status as ps', function ($join) use ($process_type_id) {
                    $join->on('ps.id', '=', 'process_list.status_id');
                    $join->on('ps.process_type_id', '=', DB::raw($process_type_id));
                })
                //->leftJoin('park_info as pi', 'pi.id', '=', 'process_list.park_id')
                ->where('process_list.ref_id', $applicationId)
                ->where('process_list.process_type_id', $process_type_id)
                ->first([
                    'process_list.id as process_list_id',
                    'process_list.desk_id',
                    'process_list.park_id',
                    'process_list.process_type_id',
                    'process_list.status_id',
                    'process_list.locked_by',
                    'process_list.locked_at',
                    'process_list.ref_id',
                    'process_list.tracking_no',
                    'process_list.company_id',
                    'process_list.process_desc',
                    'process_list.priority',
                    'user_desk.desk_name',
                    'ps.status_name',
                    'ps.color',
                    'apps.*'
                ]);
            $data['CoBrandedBusinessPurpose'] = CoBrandedBusinessPurpose::where('is_active',1)->orderby('id','DESC')->get();
            $userInfo = Users::where('id',$appInfo->created_by)->first();
            $data['companyInfo'] = CompanyInfo::where('is_approved',1)->where('id', $userInfo->user_sub_type)->first();
            $data['memberShipNo'] = MemberShipNumber::where('is_active',1)->where('user_id', $userInfo->id)->first();
            $data['bank'] = ['' => 'Select Bank '] + Bank::orderby('name')->where('is_active', 1)->lists('name', 'id')->all();

            $hasDeskParkWisePermission=CommonFunction::hasDeskParkWisePermission($appInfo->desk_id,$appInfo->park_id);

            $foreignPartner = Sponsors::where('reg_id', $applicationId)->get();
            $tradeBody = TradeBody::where('reg_id', $applicationId)->get();

            $clrDocuments = array();
            $clr_document = AppDocuments::where('process_type_id', $this->process_type_id)->where('ref_id', $applicationId)->get();


            $data['SpecificBusinessProposeData']  = CoBrandedBusinessPurposeSave::where('ref_id',$appInfo->id)->get();
            $verificationData = ProcessList::where('process_list.ref_id', $applicationId)
                ->where('process_list.process_type_id', $this->process_type_id)
                ->first([
                    'process_type_id',
                    'id as process_list_id',
                    'status_id',
                    'ref_id',
                    'id',
                    'json_object',
                    'desk_id',
                    'updated_at'
                ]);

            if ($openMode == 'view') {
                $viewMode = 'on';
                $mode = '-V-';
            } else if ($openMode == 'edit') {
                $mode = '-E-';
                $viewMode = 'off';
            } else {
                $mode = 'SecurityBreak';
                $viewMode = 'SecurityBreak';
            }
            $getStatus= ProcessType::where('id',$this->process_type_id)->first()->way_to_success;
            $statusName = ProcessStatus::whereIn('id',explode(',', $getStatus))->where('process_type_id', $this->process_type_id)->get(['status_name','id']);
            $statusArray = ProcessStatus::where('process_type_id', $this->process_type_id)->lists('status_name', 'id');
            return view('CoBrandedCard::application-form-edit',
                compact('viewMode', 'mode','banks','data', 'SpecificBusinessPropose','statusArray', 'statusName','appInfo', 'verificationData', 'clrDocuments','process_history', 'hasDeskParkWisePermission'));
        } catch (Exception $e) {
            Session::flash('error', CommonFunction::showErrorPublic($e->getMessage()) . ' [RC-1040]');
            return Redirect::back()->withInput();
        }
    }
    public function getBranch(Request $request){
        $branch_name = BankBranch::where('bank_id',$request->get('bank_id'))->where('is_active',1)->orderBy('branch_name')->lists('branch_name', 'id');
        $data = ['responseCode' => 1, 'data' => $branch_name];
        return response()->json($data);
    }

    /*
     * Application Store function
     */
    public function appStore(Request $request)
    {

        $rules = [
            'company_name' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email',
//            'father_name' => 'required',
//            'mother_name' => 'required',
//            'gender' => 'required',
//            'national_id' => 'required',
//            'pre_division' => 'required',
//            'pre_district' => 'required',
//            'pre_thana' => 'required',
//            'per_division' => 'required',
//            'per_district' => 'required',
//            'per_thana' => 'required',
//            'branch_name' => 'required',
//            'amount_of_money' => 'required',


            'acceptTerms' => 'required',
        ];

        // Validate company logo

        try {

            DB::beginTransaction();
            $companyId = CommonFunction::getUserSubTypeWithZero();
            // Check existing application
            $statusArr = array(5, 8, 22, '-1'); //5 is shortfall, 8 is Discard, 22 is Rejected Application and -1 is draft

            $data = $request->all();

            if ($request->get('app_id')) {
                $decodedId = Encryption::decodeId($data['app_id']);
                $appData = CoBrandedCardApplicationRecommendation::find($decodedId);
                $appDataLimitRenewal = LimitRenewal::find($decodedId);
                $processData = ProcessList::firstOrNew(['process_type_id' => $this->process_type_id, 'ref_id' => $appData->id]);
            } else {
                $appData = new CoBrandedCardApplicationRecommendation();
                $appDataLimitRenewal = new LimitRenewal();
                $processData = new ProcessList();
                $processData->company_id = (!empty($companyId) ? $companyId : 0);;
                $processData->created_by = $appData->created_by;
            }

            $appData->company_name = $data['company_name'];
            $appData->address = $data['address'];
            $appData->membership_no = $data['membership_no'];
            $appData->phone_number = $data['phone_number'];
            $appData->name_and_designation = $data['designation'];
            $appData->name = $data['name'];
            $appData->mobile_number = $data['mobile_number'];
            $appData->business_nature = $data['business_nature'];
            $appData->email = $data['email'];
            $appData->estimated_online_transaction = $data['estimated_online_transaction'];
            $appData->bank_id = $data['bank_name'];
            $appData->acceptTerms = (!empty($data['acceptTerms']) ? 1 : 0);

            //limit renewal
            $appDataLimitRenewal->company_name = $data['company_name'];
            $appDataLimitRenewal->address = $data['address'];
            $appDataLimitRenewal->membership_no = $data['membership_no'];
            $appDataLimitRenewal->phone_number = $data['phone_number'];
            $appDataLimitRenewal->name_and_designation = $data['designation'];
            $appDataLimitRenewal->name = $data['name'];
            $appDataLimitRenewal->mobile_number = $data['mobile_number'];
            $appDataLimitRenewal->business_nature = $data['business_nature'];
            $appDataLimitRenewal->email = $data['email'];
            $appDataLimitRenewal->estimated_online_transaction = $data['estimated_online_transaction'];
            $appDataLimitRenewal->bank_id = $data['bank_name'];
            $appDataLimitRenewal->acceptTerms = (!empty($data['acceptTerms']) ? 1 : 0);




            if ($request->get('actionBtn') == "draft" && $appData->status_id != 2) {
                $processData->status_id = -1;
                $processData->desk_id = 0;
            } else {

                if ($processData->status_id == 5) { // For shortfall
                    $processData->status_id = 2;
                } else {
                    $processData->status_id = 1;
                }
                $appData->date_of_submission = Carbon::now(); // application Date
                $appDataLimitRenewal->date_of_submission = Carbon::now(); // application Date
                $processData->desk_id = 1; // 2 is desk RD1
            }
            $appData->save();
            $appDataLimitRenewal->save();
            $processData->ref_id = $appData->id;
            $processData->process_type_id = $this->process_type_id;
            $processData->park_id = 2;
            //$processData->park_id = $data['park_id'];

            $jsonData['Company Name']= $request->get('company_name');
            $jsonData['Membership No.']= $request->get('membership_no');
            $jsonData['Phone Number']= $request->get('phone_number');
            //$jsonData['Park name']=CommonFunction::getParkNameById($data['park_id']);
            $jsonData['Email']= $request->get('email');
            $processData['json_object'] = json_encode($jsonData);
            $processData->save();

            // Generate Tracking No for Submitted application
            $processlistExist = Processlist::where('ref_id', $appData->id)->where('process_type_id', $this->process_type_id)->first();

            if ($request->get('actionBtn') != "draft" && $processData->status_id != 2) { // when application submitted but not as re-submitted
                $trackingPrefix = "CBC" . date("dmY");
                $processTypeId = $this->process_type_id;
                $updateTrackingNo = DB::statement("update  process_list, process_list as table2  SET process_list.tracking_no=(
                                                            select concat('$trackingPrefix',
                                                                    LPAD( IFNULL(MAX(SUBSTR(table2.tracking_no,-4,4) )+1,0),4,'0')
                                                                          ) as tracking_no
                                                             from (select * from process_list ) as table2
                                                             where table2.process_type_id ='$processTypeId' and table2.id!='$processData->id' and table2.tracking_no like '$trackingPrefix%'
                                                        )
                                                      where process_list.id='$processData->id' and table2.id='$processData->id'");



                // proposed business section
                $proposeBusinessIds = [];
                foreach ($data['CoBrandedBusinessPurpose'] as $key => $description) {
                    if (empty($data['proposeBusinessIds'][$key])) {
                        $ProBusiData = new CoBrandedBusinessPurposeSave();
                    } else {
                        $proposeBusinessId = $data['proposeBusinessIds'][$key];
                        $ProBusiData = CoBrandedBusinessPurposeSave::where('id', $proposeBusinessId)->first();
                    }
                    $ProBusiData->ref_id = $appData->id;
                    $ProBusiData->is_active = 1;
                    $ProBusiData->business_purpose_id = $data['CoBrandedBusinessPurpose'][$key];

                    if ($request->get('actionBtn') == 'draft') {
                        $ProBusiData->status = -1; //Draft User
                    } else {
                        $ProBusiData->status = 1; //submitted application
                    }
                    $ProBusiData->save();
                    $proposeBusinessIds[] = $ProBusiData->id;

                }

                if (!empty($proposeBusinessIds)) {
                    CoBrandedBusinessPurposeSave::where('ref_id', $appData->id)->whereNotIn('id', $proposeBusinessIds)->delete();
                }



                $processData =  ProcessList::where('id', $processData->id)->first();


                $id = $processData->id;
                $ref_id = $processData->ref_id;
                $trackingNo = $processData->tracking_no;
                $desk_id = $processData->desk_id;
                $processTypeId = $processData->process_type_id;
                $status_id = $processData->status_id;
                $on_behalf_of_user = $processData->on_behalf_of_user;
                $process_desc = $processData->process_desc;
                $closed_by = $processData->closed_by;
                $locked_at = $processData->locked_at;
                $locked_by = $processData->locked_by;
                $updated_by = $processData->updated_by;


                $result = $id . ', ' .$ref_id . ', ' .$trackingNo. ', ' . $desk_id. ', ' .$processTypeId .','. $status_id. ', '
                    . $on_behalf_of_user. ', ' . $process_desc. ', ' . $closed_by. ', ' . $locked_at. ', ' . $locked_by.','.$updated_by;
//                $hashData = RSA::encrypt($result);


                $keyPair = KeyPair::generateKeyPair(2048);
                $secretKey = $keyPair->getPrivateKey();

                $publicKey = $keyPair->getPublicKey();
                $hashData = EasyRSA::encrypt($result, $publicKey);

                $previousHash = ProcessList::orderby('id','DESC')->skip(1)->first(['hash_value']);
                if($previousHash == null){
                    $previousHash= '';
                }else{
                    $previousHash= $previousHash->hash_value;
                }

                ProcessList::where('id', $id)->update(['hash_value' => $hashData, 'previous_hash'=> $previousHash]);
            }

            //  Required Documents for attachment

//            $doc_row = DocInfo::where('process_type_id', $this->process_type_id)// 1 is service id for Space Allocation
//            ->get(['doc_id', 'doc_name']);
//            if (isset($doc_row)) {
//
//                foreach ($doc_row as $docs) {
//                    $documentName = (!empty($request->get('other_doc_name_' . $docs->doc_id)) ? $request->get('other_doc_name_' . $docs->doc_id) : $request->get('doc_name_' . $docs->doc_id));
//                    $document_id = $docs->doc_id;
//
//
//                    // if this input file is new data then create
//                    if ($request->get('document_id_' . $docs->doc_id) == '') {
//
//                        $insertArray = [
//                            'process_type_id' => $this->process_type_id, // 1 for Space Allocation
//                            'ref_id' => $appData->id,
//                            'doc_info_id' => $document_id,
//                            'doc_name' => $documentName,
//                            'doc_file_path' => $request->get('validate_field_' . $docs->doc_id)
//                        ];
//                        AppDocuments::insert($insertArray);
//                    } // if this input file is old data then update
//
//                    else {
//                        $oldDocumentId = $request->get('document_id_' . $docs->doc_id);
//                        $insertArray = [
//                            'process_type_id' => $this->process_type_id, // 2 for General Form
//                            'ref_id' => $appData->id,
//                            'doc_info_id' => $document_id,
//                            'doc_name' => $documentName,
//                            'doc_file_path' => $request->get('validate_field_' . $docs->doc_id)
//                        ];
//                        AppDocuments::where('id', $oldDocumentId)->update($insertArray);
//                    }
//                }
//            } /* End file uploading */



            //Saving data to process_list table
            if ($request->get('submitInsert') == 'save') {
                if($processlistExist->status_id == 5 ){

                    ProcessList::where('ref_id', $processlistExist->id)
                        ->where('process_type_id', $this->process_type_id)->update([
                            'desk_id'=> 2,
                            'status_id'=> 2,
                            'tracking_no'=> $processlistExist->tracking_no
                        ]);
                }
            }

            DB::commit();

            if ($processData->status_id == -1) {
                \Session::flash('success', 'Successfully updated the Application!');
            } elseif ($processData->status_id == 1) {
                Session::flash('success', 'Successfully Application Submitted !');
            } elseif ($processData->status_id == 2) {
                Session::flash('success', 'Successfully Application Re-Submitted !');
            } else {
                Session::flash('error', 'Failed due to Application Status Conflict. Please try again later!');
            }
            return redirect('co-branded-card/list/'.Encryption::encodeId($this->process_type_id));
        } catch (\Exception $e) {
            dd($e->getLine(), $e->getMessage(), $e->getFile());
            DB::rollback();
            Session::flash('error', CommonFunction::showErrorPublic($e->getMessage()) . ' [RC-1060]');
            return redirect()->back()->withInput();
        }
    }
    public function uploadDocument() {
        return View::make('LoanLocator::ajaxUploadFile');
    }
    public function updateADInfo($ref_id, $process_type_id){


        $ref_id = Encryption::decodeId($ref_id);
        $process_type_id = Encryption::decodeId($process_type_id);


        $appInfo = ProcessList::leftJoin('ga_master as apps', 'apps.id', '=', 'process_list.ref_id')
            ->where('process_list.ref_id', $ref_id)
            ->where('process_list.process_type_id', $process_type_id)
            ->first([
                'process_list.ref_id',
                'process_list.status_id',
                'process_list.tracking_no',
                'process_list.created_by as ccreated_by'

            ]);

        $requestData=session('requestData');

        // 5= Shortfall, 8 = Discarded, 21 = Approved & sent to customs, 22 = Rejected,
        if (in_array($requestData['status_id'], array(5, 25))) {
            if($requestData['status_id'] == 25){  //completed cirf
                $this->certificateGenForLoanLocator($ref_id, 0, $this->process_type_id);  // 8 is the service id of Project Registration

            }
            $company_emails = Users::where('id', $appInfo->ccreated_by)
                ->where('is_approved', 1)
                ->where('user_status', 'active')
                ->first(['user_email', 'user_phone']);

            $body_msg = '<span style="color:black;text-align:justify;"><b>';
            if($requestData['status_id'] == 5) {
                $body_msg .= 'Your application for Loan Locator with Tracking Number: ' . $appInfo->tracking_no . ' is now in status: <b>' .
                    CommonFunction::getFieldName($requestData['status_id'], 'id', 'status_name', 'process_status') . '</b>';
            }elseif ($requestData['status_id'] == 25){
                $body_msg .= 'Your Loan-locator application has been approved successfully with tracking number: ' . $appInfo->tracking_no . '<b>';
            }
            $body_msg .= '</span>';
            $body_msg .= '<br/><br/><br/>Thanks<br/>';
            $body_msg .= '<b>Loan Locator</b>';

            $header = "Application Update Information for Loan Locator";
            $param = $body_msg;
            $email_content = view("Users::message", compact('header', 'param'))->render();

//            foreach ($company_emails as $companyuser) {
            $emailQueue = new EmailQueue();
            $emailQueue->service_id = $this->process_type_id; // service_id of LPP
            $emailQueue->app_id = $appInfo->ref_id;
            $emailQueue->email_content = $email_content;
            $emailQueue->email_to = $company_emails->user_email;
            $emailQueue->sms_to =  $company_emails->user_phone;
            $emailQueue->email_subject = $header;
            $emailQueue->attachment = '';
            $emailQueue->save();
            // }

        }




        Session::flash('success', "Application status updated successfully");
        return redirect()->back();
    }


    public function certificateAndOther($ref_id, $process_type_id){
        $ref_id = Encryption::decodeId($ref_id);
        $process_type_id = Encryption::decodeId($process_type_id);

        $appInfo = ProcessList::leftJoin('space_allocation as apps', 'apps.id', '=', 'process_list.ref_id')
            ->where('process_list.ref_id', $ref_id)
            ->where('process_list.process_type_id', $process_type_id)
            ->first([
                'process_list.company_id',
                'process_list.desk_id',
                'process_list.park_id',
                'process_list.ref_id',
                'process_list.tracking_no',
                'process_list.status_id',
                'process_list.locked_by',
                'process_list.locked_at',
                'apps.*',
            ]);

        $status_from = $appInfo->status_id;
        $applyStausId = 25;
        $company_emails = Users::where('user_sub_type', $appInfo->company_id)
            ->where('is_approved', 1)
            ->where('user_status', 'active')
            ->get(['user_email', 'user_phone']);

        $billMonth = date('Y-m');
        $body_msg = '<span style="color:#000;text-align:justify;"><b>';

        $body_msg .= 'Your application for CO on OCPL OSS Framework with Tracking Number: ' . $appInfo->tracking_no . ' is now in status: <b>' . CommonFunction::getFieldName($appInfo->status_id, 'id', 'status_name', 'process_status') . '</b>';
        $body_msg .= '</span>';
        $body_msg .= '<br/><br/><br/>Thanks<br/>';
        $body_msg .= '<b>OCPL OSS Framework</b>';

        $pdf_info1 = PdfServiceInfo::where('certificate_type', 'rtacert.en')->first();
        $pdf_type1 = trim($pdf_info1->certificate_type).".".trim($pdf_info1->server_type);
        $reg_key1 = trim($pdf_info1->key);

        $this->certificateGenForUpdateBatch($process_type_id, $ref_id, $pdf_type1, $reg_key1,trim($pdf_info1->certificate_type));
        $certificate = "/uploads/basiscardreccert.pdf";
//        $certificate = "certificate";   //Certificate Generation after payment accepted
        CoBrandedCardApplicationRecommendation::where('id', $appInfo->ref_id)->update(['certificate' => $certificate]);

        $header = "Application Update Information for OCPL OSS Framework Co";
        $param = $body_msg;
        $email_content = view("Users::message", compact('header', 'param'))->render();
//                    dd($company_emails);
//                    foreach ($company_emails as $companyuser) {
//                        $emailQueue = new EmailQueue();
//                        dd('hihi');
//
//                        $emailQueue->service_id = 1; // process_type_id of project Space Allocation
//                        $emailQueue->app_id = $application_id;
//
//                        //email info
//                        $emailQueue->email_content = $email_content;
//                        $emailQueue->email_to = $companyuser->user_email;
//                        $emailQueue->email_subject = $header;
//                        $emailQueue->attachment = $certificate;
//
//                        // sms info
//                        if ($applyDeskId == 20) {
//                            // TODO::will add later
////                            $emailQueue->sms_to = $companyuser->user_phone;
////                            $emailQueue->sms_content = 'Your application has been submitted with tracking id: ' . $process_data->tracking_no .
////                            ' received. Please fill up your Pay order information!';
//                        }
//                        $emailQueue->save();
//                    }


        // English PDF
        $pdfQueue = new pdfQueue();
        $pdfQueue->service_id = $process_type_id;
        $pdfQueue->app_id = $ref_id;
        $pdfQueue->secret_key = $reg_key1;
        $pdfQueue->pdf_type = $pdf_type1;
        $pdfQueue->save();


        DB::commit();

        // for previous and present status
        $appStatus = ProcessStatus::where('status', 3)->get();
        $statusList = array();
        foreach ($appStatus as $k => $v) {
            $statusList[$v->id] = $v->status_name;
        }

        Session::flash('success', "Application status updated");
        return redirect()->back();
    }


    public function certificateGenForUpdateBatch($service_id = 0, $app_id = 0, $pdf_type = "", $reg_key = "", $certificate_type="") {


        // Its should be needed for officer signature
        $user_data = Users::where('id', '=', CommonFunction::getUserId())->first();
        // File path URL comes from env url variable

        // File path URL comes from env url variable
        $signature_url = env('sign_url') . $user_data->signature;
        $signature = '';
        //************************************Signature  will not be available for local server *********************************/
        if (!empty($signature_url) && (env('server_type') != 'local')) {
            $arrContextOptions=array(
                "ssl"=>array(
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ),
            );
            $signature = file_get_contents($signature_url, false, stream_context_create($arrContextOptions));
            // $signature = file_get_contents($signature_url);
        } else {
            $signature = 'No signature found';
        }



        $get_pdf_signature = DB::table('pdf_signature_qrcode')->where('app_id', $app_id)->first();

        if ($get_pdf_signature) {
            // It will use in pdf server for retrieve signature and user info
            DB::table('pdf_signature_qrcode')->where('app_id', $app_id)->where('service_id', $service_id)->update([
                'signature' =>  base64_encode($signature),
                'user_id' => $user_data->id,
                'desk_id' => $user_data->desk_id
            ]);
        } else {
            // It will use in pdf server for retrieve signature and user info
            $pdfSinaQr = new pdfSignatureQrcode();
            $pdfSinaQr->signature = base64_encode($signature);
            $pdfSinaQr->app_id = $app_id;
            $pdfSinaQr->service_id = $service_id;
            $pdfSinaQr->qr_code = "";
            $pdfSinaQr->user_id = $user_data->id;
            $pdfSinaQr->desk_id = $user_data->desk_id;
            $pdfSinaQr->save();
        }
// End of the signature function

//        $sql = "SELECT
//pl.service_id,
//agencys.license_no,
//ara.updated_at `date`,
//ara.license_effective_date,
//ara.license_expired_date,
//ara.agency_name_en,
//ara.agency_name_bn,
//ara.applicant_name_en,
//ara.applicant_name_bn,
//ara.present_address_en,
//ara.present_address present_address_bn,
//usr.user_full_name a_name,
//usr.designation a_desig,
//usr.signature a_sign_url,
//psq.signature a_sign,
//psq.qr_code a_qr
//FROM process_list AS pl
//LEFT JOIN agency_reg_apps AS ara ON pl.record_id =  ara.id AND pl.service_id = 2
//LEFT JOIN agencys ON agencys.id = ara.agency_id AND pl.service_id = 2
//LEFT JOIN pdf_signature_qrcode psq ON psq.app_id=pl.record_id AND psq.service_id=2
//LEFT JOIN users usr ON usr.id=psq.user_id
//WHERE pl.service_id = 2 AND (pl.status_id = 25 or pl.status_id = 23) AND pl.record_id= $app_id";
//
//        $resutl_request = \DB::select(DB::raw($sql));

        $data = array();
        $data['data'] = array(
            'reg_key' => $reg_key, // Secret authentication key
            'pdf_type' => $pdf_type, // letter type
            'ref_id' => $app_id, // app_id
            'json' => array(
                'service_id' => $service_id,
                'app_id'=> $app_id,
                'a_sign' => base64_encode($signature)

            ),
            'param' => array(
                'app_id' => $app_id // app_id
            )
        );

        $encode_data = urlencode(json_encode($data));


                // Request send to the pdf server
                $pdf_info = PdfServiceInfo::where('certificate_type', $certificate_type)->first();
                //$url_request = $pdf_info->pdf_server_url . "api/new-job?requestData=$encode_data";
                $url_store = new PdfPrintRequest();
                $url_store->app_id = $app_id;
                $url_store->service_id = $service_id;
                $url_store->url_request = $encode_data;
                $url_store->save();

        return true; // return true for success
    }



    public function verifyProcessHistory($type_id, $process_list_id){
        try {
            $type_id = Encryption::decodeId($type_id);
            $process_list_id = Encryption::decodeId($process_list_id);

            $process_history = DB::select(DB::raw("select  `process_list_hist`.`status_id`,`as`.`status_name`,          
                                if(`process_list_hist`.`desk_id`=0,\"-\",`ud`.`desk_name`) `deskname`,
                                `users`.`user_full_name`, 
                                `process_list_hist`.`process_id`,
                                `process_list_hist`.`ref_id`,
                                `process_list_hist`.`process_type`,
                                `process_list_hist`.`tracking_no`,
                                `process_list_hist`.`closed_by`,
                                `process_list_hist`.`locked_by`,
                                `process_list_hist`.`locked_at`,
                                `process_list_hist`.`desk_id`,
                                `process_list_hist`.`status_id`,
                                `process_list_hist`.`process_desc`,
                                `process_list_hist`.`created_by`,
                                `process_list_hist`.`on_behalf_of_user`,
                                `process_list_hist`.`updated_by`,
                                `process_list_hist`.`status_id`,
                                `process_list_hist`.`process_desc`,
                                `process_list_hist`.`process_id`,
                                `process_list_hist`.`updated_at`,
                                `process_list_hist`.`hash_value`,                                                                
                                 group_concat(`pd`.`file`) as files
                                from `process_list_hist`
                                left join `process_documents` as `pd` on `process_list_hist`.`id` = `pd`.`process_hist_id`
                                left join `user_desk` as `ud` on `process_list_hist`.`desk_id` = `ud`.`id`
                                left join `users` on `process_list_hist`.`updated_by` = `users`.`id`     
                                
                                left join `process_status` as `as` on `process_list_hist`.`status_id` = `as`.`id`
                                and `process_list_hist`.`process_type` = `as`.`process_type_id`
                                where `process_list_hist`.`process_id`  = '$process_list_id'
                                and `process_list_hist`.`process_type` = '$type_id' 
                                and `process_list_hist`.`hash_value` !='' 
                                and `process_list_hist`.`status_id` != -1
                    group by `process_list_hist`.`process_id`,`process_list_hist`.`desk_id`, `process_list_hist`.`status_id`, process_list_hist.updated_at
                    order by process_list_hist.updated_at desc
                    limit 20
                    "));

            $html = "";
            if (count($process_history) > 1) {

                $keyPair = KeyPair::generateKeyPair(2048);
                $secretKey = $keyPair->getPrivateKey();
                $html .= "<div class=\"table-responsive\"><table border='1px solid' class='table table-striped table-bordered dt-responsive nowrap dataTable no-footer dtr-inline' style='text-align: center; width: 100%;'>";
                $html .= "<th id='1' style='text-align: center'>Process ID</th><th style='text-align: center'>On Desk</th><th style='text-align: center'>Updated By</th><th style='text-align: center'>Status</th><th style='text-align: center'>Process Time</th><th style='text-align: center'>Verification Status</th>";

                foreach ($process_history as $data) {

                    $resultData = $data->process_id . ', ' . $data->ref_id . ', ' . $data->tracking_no . ', ' . $data->desk_id . ', ' . $data->process_type . ',' . $data->status_id . ', '
                        . $data->on_behalf_of_user . ', ' . $data->process_desc . ', ' . $data->closed_by . ', ' . $data->locked_at . ', ' . $data->locked_by . ',' . $data->updated_by;
                    $plaintext = EasyRSA::decrypt($data->hash_value, $secretKey);
                    $time = date('d-m-Y h:i A', strtotime($data->updated_at));
                    $html .= "<tr><td style='text-align: center; padding: 10px'> $data->process_id </td><td  style='padding: 10px'> $data->deskname </td><td  style='padding: 10px'> $data->user_full_name </td><td  style='padding: 10px'> $data->status_name </td><td  style='padding: 10px'>$time</td>";
                    if ($resultData == $plaintext) {
                        $verification = "<font color='green'><h4><i class=\"fa fa-check-square\"></i> </h4></font>";
                    } else {
                        $verification = "<font color='red'><h4><i class=\"fa fa-ban\"></i> </h4></font>";
                    }
                    $html .= "<td style='text-align: center'> $verification </td> </tr>";
                }
                $html .= "</table></div>";

            } else {
                $html .= "<div style='text-align: center;'><h3>No result found!</h3></div>";
            }
            return view("LoanLocator::history-verification")->with("html", $html);
        }catch (Exception $e) {
            Session::flash('error', CommonFunction::showErrorPublic($e->getMessage()));
            return Redirect::back()->withInput();
        }

    }

    public function certificateGenForLoanLocator($app_id = 0, $other_significant_id = 0, $service_id = 8) {
        try {
            // Starting pdf server code
            $pdf_info = PdfServerInfo::where('certificate_type', 'apr')->first(); // proreg is for project registration
            $pdf_type = $pdf_info->project_code . "." . $pdf_info->certificate_type . "." . $pdf_info->server_type;
            $reg_key = $pdf_info->key;

            $data = array();
            $data['data'] = array(
                'reg_key' => trim($reg_key), // Secret authentication key
                'pdf_type' => $pdf_type, // letter type
                'ref_id' => $app_id, // app_id
                'param' => array(
                    'app_id' => $app_id, // app_id
                    'service_id' => $service_id, // 9 for PR
                    'other_significant_id' => $other_significant_id, // will be 0 as it is not applicable here
                )
            );

            // It will be needed for officer signature. As for now the user is being fetched from configuration
            $approver_email = "rd4@batworld.com"; //Configuration::where('caption', 'CERTIFICATE_AUTHORITY')->pluck('value'); // return an email address
            $user_data = Users::where('id', '=', 510)->first(); // id 356 = Mohammed Ayub, Secretary of BEZA
            // File path URL comes from env url variable
            $signature_url = env('sign_url') . $user_data->signature;
            $signature = '';
            //************************************Signature  will not be available for local server *********************************/
            if (!empty($signature_url) && (env('server_type') != 'local')) {
                $arrContextOptions=array(
                    "ssl"=>array(
                        "verify_peer"=>false,
                        "verify_peer_name"=>false,
                    ),
                );
                $signature = file_get_contents($signature_url, false, stream_context_create($arrContextOptions));
                // $signature = file_get_contents($signature_url);
            } else {
                $signature = 'No signature found';
            }



            // It will be used in pdf server for retrieve signature and authorized user info
            $pdfSignQr = pdfSignatureQrcode::firstOrNew([
                'app_id' => $app_id,
                'other_significant_id' => $other_significant_id,
                'service_id' => $service_id  // 9 for PR
            ]);
            $pdfSignQr->signature = $signature; // empty in dev
            $pdfSignQr->qr_code = '';
            $pdfSignQr->user_id = $user_data->id;
            $pdfSignQr->desk_id = $user_data->desk_id;
            $pdfSignQr->save();
            // End of the signature function

            $encode_data = json_encode($data);
            $url_request = $pdf_info->pdf_server_url . "api/new-job?requestData=$encode_data";

            switch ($pdf_type) {
                case 'loan.apr.d':
                case 'loan.apr.l':
                case 'loan.apr.uat':
                case 'loan.apr.t':

                    //Saving data into pdf_print_requests table
                    $url_store = PdfPrintRequest::firstOrNew([
                        'app_id' => $app_id,
                        'other_significant_id' => $other_significant_id,
                        'service_id' => $service_id  // 9 for PR
                    ]);
                    $url_store->url_request = $url_request;
                    $url_store->status = 0; // for new as well as old
                    $url_store->save();
                    break;

                default:
            }

            //Saving data into pdf_queue table
            $pdfQueue = pdfQueue::firstOrNew([
                'app_id' => $app_id,
                'other_significant_id' => $other_significant_id,
                'service_id' => $service_id  // 9 for PR
            ]);
            $pdfQueue->pdf_type = $pdf_type;
            $pdfQueue->secret_key = $reg_key;
            $pdfQueue->status = 0; // for new as well as old
            $pdfQueue->save();

            return true; // return true for success
        } catch (Exception $e) {
            Session::flash('error', CommonFunction::showErrorPublic($e->getMessage()));
            return Redirect::back()->withInput();
        }
    }


    /*     * ********************************************End of Controller Class************************************************* */
}
