@extends('layouts.front')

<meta name="viewport" content="width=device-width, initial-scale=1.0">

@section("content")


    <header style="width: 100%; height: auto; opacity:0.7;">
        <div class="col-md-12 text-center">
            <div class="col-md-3"></div>
            <div class="col-md-6"  style="margin-top:5px;">
                <img width="70" alt="Logo" src="/assets/images/govt_logo.png"/><br/><br/>
                <h3 class="less-padding">{!!trans('messages.logo_title')!!}</h3><br/>
            </div>
            <div class="col-md-3"></div>
        </div>
        <div class="clearfix"> <br></div>
    </header>

    <div class="col-md-12">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <hr class="top-border"/>
        </div>
        <div class="col-md-1"></div>
    </div>

    <div class="container" style="margin-top:30px;">
        <div class="row">
            <div class="col-md-10 col-md-offset-1 " style="background: #ABD6AC; opacity:0.9; border-radius:8px;">
                <h3 class="text-center">Registration process</h3>
                @if(Session::has('success'))
                    <div class="alert alert-info">
                        {!!Session::get('success') !!}
                        {!! link_to('users/resendMail?tmp='.Input::get('tmp'), 'Resend Verification Email', array('class' => 'btn btn-primary btn-sm')) !!}
                    </div>
                @endif
                @if(Session::has('verifyNo'))
                    <div class="alert alert-warning text-center">
                        {{ Session::get('verifyNo') }}
                        {!! link_to('users/resendMail?tmp='.Input::get('tmp'), 'Resend Email', array('class' => 'btn btn-primary btn-sm')) !!}
                    </div>
                @endif
                @if(Session::has('verifyYes'))
                    <div class="alert alert-danger text-center">
                        {{ Session::get('verifyYes') }}
                        {!! link_to('forget-password', 'Recover Password', array('class' => 'btn btn-primary btn-sm')) !!}
                    </div>
                @endif
                @if(Session::has('error'))
                    <div class="alert alert-warning">
                        {{ Session::get('error') }}
                    </div>
                @endif
                <hr/>
                <div class="col-md-7 col-sm-7">

                    {!! Form::open(array('url' => '/users/store','method' => 'patch', 'class' => 'form-horizontal', 'id' => 'reg_form',
                    'enctype' =>'multipart/form-data', 'files' => 'true')) !!}

                    <fieldset>
                        <div class="form-group has-feedback {{ $errors->has('user_full_name') ? 'has-error' : ''}}">
                            <label  class="col-md-5 text-left required-star">Name</label>
                            <div class="col-md-7">
                                {!! Form::text('user_full_name', null, $attributes = array('class'=>'form-control textOnly required', 'data-rule-maxlength'=>'40',
                                'placeholder'=>'Enter your Name', 'id'=>"user_full_name")) !!}
                                <span class="fa fa-user form-control-feedback"></span>
                                {!! $errors->first('user_full_name','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('user_type') ? 'has-error' : ''}}">
                            <label  class="col-md-5 text-left required-star"> User Type</label>
                            <div class="col-md-7">
                                {!! Form::select('user_type', $user_types, '', $attributes = array('class'=>'form-control required',  'data-rule-maxlength'=>'40',
                                'id'=>"user_type")) !!}
                                {!! $errors->first('user_type','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>


                        <div class="form-group has-feedback {{ $errors->has('nationality') ? 'has-error' : ''}}">
                            <label  class="col-md-5 text-left required-star"> Nationality</label>
                            <div class="col-md-7">
                                {!! Form::select('nationality', $nationalities, '', $attributes = array('class'=>'form-control required',  'data-rule-maxlength'=>'40',
                                'placeholder' => 'Select One', 'id'=>"nationality")) !!}
                                {!! $errors->first('nationality','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback {{$errors->has('identity_type') ? 'has-error': ''}}">
                            {!! Form::label('identity_type','Identification Type :',['class'=>'text-left col-md-5', 'id' => 'identity_type_label']) !!}
                            <div class="col-md-7">
                                <label>{!! Form::radio('identity_type', '1', 'false', ['class'=>'identity_type']) !!} Passport</label>
                                &nbsp;&nbsp;
                                <label>{!! Form::radio('identity_type', '2', 'false', ['class'=>'identity_type']) !!} National ID</label>
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('passport_no') ? 'has-error' : ''}} hidden" id="passport_div">
                            <label  class="col-md-5 text-left required-star">Passport No.</label>
                            <div class="col-md-7">
                                {!! Form::text('passport_no', null, $attributes = array('class'=>'form-control',  'data-rule-maxlength'=>'40',
                                'placeholder'=>'Enter your Passport No.', 'id'=>"passport_no")) !!}
                                <span class="fa fa-book form-control-feedback"></span>
                                {!! $errors->first('passport_no','<span class="help-block">:message</span>') !!}
                                <p class="text-danger pss-error"></p>
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('user_nid') ? 'has-error' : ''}} hidden" id="nid_div">
                            <label  class="col-md-5 text- required-star">National ID No.</label>
                            <div class="col-md-7">
                                {!! Form::text('user_nid', null, $attributes = array('class'=>'form-control',  'data-rule-maxlength'=>'40',
                                'placeholder'=>'Enter your NID No.', 'id'=>"user_nid")) !!}
                                <span class="fa fa-credit-card form-control-feedback"></span>
                                {!! $errors->first('user_nid','<span class="help-block">:message</span>') !!}
                                <p class="text-danger pss-error"></p>
                            </div>
                        </div>

                        <div class="form-group has-feedback {{$errors->has('user_DOB') ? 'has-error' : ''}}">
                            {!! Form::label('user_DOB','Date of Birth',['class'=>'col-md-5 text-left required-star']) !!}
                            <div class="col-md-7">
                                <div class="datepicker input-group date" data-date="12-03-2015" data-date-format="dd-mm-yyyy">
                                    {!! Form::text('user_DOB', '', ['class'=>'form-control required', 'placeholder' => 'Pick from Calendar', 'data-rule-maxlength'=>'40']) !!}
                                    <span class="input-group-addon">
                                    <span class="fa fa-calendar"></span>
                                </span>
                                    {!! $errors->first('user_DOB','<span class="help-block">:message</span>') !!}
                                </div>
                            </div>
                        </div>


                        <div class="form-group">
                            <label  class="col-md-10 text-left"><h4>Correspondent Address & Contact Details:</h4></label>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('country') ? 'has-error' : ''}}">
                            {!! Form::hidden('location_lat', '', $attributes = array('id'=>"location_lat")) !!}
                            {!! Form::hidden('location_lon', '', $attributes = array('id'=>"location_lon")) !!}
                            <label  class="col-md-5 text-left required-star">Country </label>
                            <div class="col-md-7">
                                {!! Form::select('country', $countries, null, $attributes = array('class'=>'form-control required', 'data-rule-maxlength'=>'40',
                                'placeholder' => 'Select One', 'id'=>"country")) !!}
                                {!! $errors->first('country','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback hidden {{ $errors->has('division') ? 'has-error' : ''}}" id="division_div">
                            <label  class="col-md-5 text-left required-star">Division </label>
                            <div class="col-md-7">
                                {!! Form::select('division', $divisions, '', $attributes = array('class'=>'form-control',
                                'placeholder' => 'Select One', 'id'=>"division")) !!}
                                {!! $errors->first('division','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('state') ? 'has-error' : ''}}" id="state_div">
                            <label  class="col-md-5 text-left required-star"> City </label>
                            <div class="col-md-7">
                                {!! Form::text('state', '', $attributes = array('class'=>'form-control', 'placeholder' => 'Name of your state / division',
                                'data-rule-maxlength'=>'40', 'id'=>"state")) !!}
                                <span class="fa fa-map-marker form-control-feedback"></span>
                                {!! $errors->first('state','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback hidden {{ $errors->has('district') ? 'has-error' : ''}}" id="district_div">
                            <label  class="col-md-5 text-left required-star"> District </label>
                            <div class="col-md-7">
                                {!! Form::select('district', $districts, '', $attributes = array('class'=>'form-control', 'placeholder' => 'Select Division First',
                                'data-rule-maxlength'=>'40','id'=>"district")) !!}
                                {!! $errors->first('district','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('province') ? 'has-error' : ''}}" id="province_div">
                            <label  class="col-md-5 text-left required-star"> State / Province </label>
                            <div class="col-md-7">
                                {!! Form::text('province', '', $attributes = array('class'=>'form-control', 'data-rule-maxlength'=>'40',
                                'placeholder' => 'Enter your Province', 'id'=>"province")) !!}
                                <span class="fa fa-map-marker form-control-feedback"></span>
                                {!! $errors->first('province','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('road_no') ? 'has-error' : ''}}">
                            <label  class="col-md-5 text-left required-star"> Address Line 1</label>
                            <div class="col-md-7">
                                {!! Form::text('road_no', '', $attributes = array('class'=>'form-control bnEng required', 'data-rule-maxlength'=>'100',
                                'placeholder' => 'Enter Road / Street Name /  No.', 'id'=>"road_no")) !!}
                                <span class="fa fa-road  form-control-feedback"></span>
                                {!! $errors->first('road_no','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('house_no') ? 'has-error' : ''}}">
                            <label  class="col-md-5 text-left">  Address Line 2 </label>
                            <div class="col-md-7">
                                {!! Form::text('house_no', '', $attributes = array('class'=>'form-control bnEng', 'data-rule-maxlength'=>'100',
                                'placeholder' => 'Enter House / Flat / Holding No.', 'id'=>"house_no")) !!}
                                <span class="fa fa-home  form-control-feedback"></span>
                                {!! $errors->first('house_no','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('post_code') ? 'has-error' : ''}}">
                            <label  class="col-md-5 text-left"> ZIP / Post Code </label>
                            <div class="col-md-7">
                                {!! Form::text('post_code', '', $attributes = array('class'=>'form-control', 'data-rule-maxlength'=>'40',
                                'placeholder' => 'Enter your Post Code ', 'id'=>"post_code")) !!}
                                <span class="fa fa-inbox form-control-feedback"></span>
                                {!! $errors->first('post_code','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('user_phone') ? 'has-error' : ''}}">
                            <label  class="col-md-5 text-left required-star">Mobile Number</label>
                            <div class="col-md-7">
                                {!! Form::text('user_phone', null, $attributes = array('class'=>'form-control phone required',
                                'maxlength'=>"20", 'data-rule-maxlength'=>'40', 'placeholder'=>'Enter your Mobile Number','id'=>"user_phone")) !!}
                                <span class="text-danger mobile_number_error"></span>
                                <span class="fa fa-phone form-control-feedback"></span>
                                {!! $errors->first('user_phone','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('user_fax') ? 'has-error' : ''}}">
                            <label  class="col-md-5 text-left">Fax</label>
                            <div class="col-md-7">
                                {!! Form::text('user_fax', null, $attributes = array('class'=>'form-control', 'placeholder'=>'Enter your Fax (If Any)',
                                'data-rule-maxlength'=>'40','id'=>"user_fax")) !!}
                                <span class="fa fa-fax form-control-feedback"></span>
                                {!! $errors->first('user_fax','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        <div class="form-group has-feedback {{ $errors->has('user_email') ? 'has-error' : ''}}">
                            <label  class="col-md-5 text-left  required-star">Email Address</label>
                            <div class="col-md-7">
                                {!! Form::text('user_email', null, $attributes = array('class'=>'form-control email required', 'data-rule-maxlength'=>'40',
                                'placeholder'=>'Enter your Email Address','id'=>"user_email")) !!}
                                <span class="fa fa-envelope form-control-feedback"></span>
                                {!! $errors->first('user_email','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>

                        {{--<div class="form-group has-feedback {{ $errors->has('authorization_file') ? 'has-error' : ''}}">--}}
                            {{--<label  class="col-md-5 text-left required-star" id="auth_letter_label">Authorization Letter</label>--}}
                            {{--<div class="col-md-7">--}}
                                {{--<div id="auth_file_tmp_err" style="color: red;"></div>--}}
                                {{--<img src="" class="profile-user-img img-responsive hidden"  alt="Profile Picture" id="uploaded_pic"  style='height: 150px;width: 100%'/>--}}
                                {{--{!! Form::file('authorization_file',['onchange'=>'readURL(this)','required','data-type'=>'auth_file_tmp','style'=>'width:100%','id'=>'auth_letter','class'=>'required']) !!}--}}
                                {{--<button class="btn btn-xs btn-primary hidden change_btn" type="button">Change</button>--}}
                            {{--<span class="text-danger" style="font-size: 9px; font-weight: bold">--}}
                                {{--[File Format: *.pdf | Maximum file size 3 MB]--}}
                            {{--</span>--}}
                                {{--{!! $errors->first('authorization_file','<span class="help-block">:message</span>') !!}--}}
                                {{--<br/>--}}
                                {{--<a href="/assets/images/sample_auth_letter.png" target="_blank"> Download a sample copy </a>--}}
                                {{--<br/>--}}
                                {{--<div class="progress hidden" id="upload_progress">--}}
                                    {{--<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"--}}
                                         {{--style="width: 100%">--}}
                                    {{--</div>--}}
                                {{--</div>--}}
                            {{--</div>--}}
                        {{--</div>--}}

                        {{--<div class="form-group has-feekback {{ $errors->has('passport_nid_file') ? 'has-error' : '' }}">--}}
                            {{--<label class="col-md-5 text-left required-star" id="passport_nid_file_label">Scan Copy of Passport/NID</label>--}}
                            {{--<div class="col-md-7">--}}
                                {{--<div id="passport_nid_file_err" style="color: red;"></div>--}}
                                {{--{!! Form::file('passport_nid_file',['onchange'=>'readURL(this)','required','data-type'=>'passport_nid_file','style'=>'width:100%','id'=>'passport_nid_file','class'=>'required']) !!}--}}
                                {{--<button class="btn btn-xs btn-primary hidden change_btn" type="button">Change</button>--}}
                            {{--<span class="text-danger" style="font-size: 9px; font-weight: bold">--}}
                                {{--[File Format: *.pdf | Maximum file size 3 MB]--}}
                            {{--</span>--}}
                                {{--{!!  $errors->first('passport_nid_file','<span class="help-block">:message</span>') !!}--}}
                            {{--</div>--}}

                        {{--</div>--}}



                        {{--<div class="form-group pull-right  {{$errors->has('g-recaptcha-response') ? 'has-error' : ''}}">--}}
                            {{--<div class="col-md-12">--}}
                                {{--{!! Recaptcha::render() !!}--}}
                                {{--{!! $errors->first('g-recaptcha-response','<span class="help-block">:message</span>') !!}--}}
                            {{--</div>--}}
                            {{--<div class="g-recaptcha" data-sitekey="6LeZySUTAAAAAJPrGc0dRe2_pTnqt2ukM90OVBXA"></div>--}}
                        {{--</div>--}}

                        <div class='clearfix'></div>
                        <div class="form-group">
                            <div class="col-md-4 col-md-offset-8">
                                <button type="submit" class="btn btn-block btn-primary"><b>Submit</b></button>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <div class="col-md-12 col-md-offset-2">
                                Already have an account? <b>{!! link_to('login', 'Login', array('class' => '')) !!}</b>
                            </div>
                        </div>
                    </fieldset>

                    {!! Form::close() !!}
                    <div class="clearfix"></div>
                </div>

                <div class="col-md-5 col-sm-5" style="border-left:1px grey dotted;">
                    <div id="tips">
                        <h4>Registration related advice</h4>
                        <hr>
                        <h5>You can fill out the form by following the following steps:</h5>
                        <ol>
                            <li>Fill out each field with appropriate information.</li>
                            <li>Select "Date of Birth" by clicking the calender icon.</li>
                            <li>Upload scanned copy of the Authorization letter following the given instructions. </li>
                        </ol>
                        <br/>
                        <h5> <b>What is an Authorization Letter ?</b></h5>
                        <p>
                            If anyone wants to work on behalf of an organization, the company's managing director / chief of the company
                            will sanction a consent letter printed on a Letter Head pad of the respective company.
                        </p>
                        <br/>
                        <img src="/assets/images/need-help.png" width="300" class="pull-right" style="opacity: 0.3">
                    </div>

                    <div class="clearfix"></div>
                    <div class="row">
                        <div class="col-md-12">
                            <label class="text-left required-star"> Drag the marker to pick location from map</label>
                            <div id="locationpicker" style="height: 350px;"></div>
                        </div>
                    </div>

                </div>

                <div class="clearfix">

                </div>
            </div>
        </div>
    </div>

    @endsection

            <!--<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>-->

    @section('footer-script')
            <!--<script src="http://code.jquery.com/jquery-1.10.2.min.js"></script>-->
    <!-- Location picker -->

    <script type="text/javascript" src='https://maps.google.com/maps/api/js?key={{env('GOOGLE_MAP')}}&libraries=places'></script>
    <script src="{{ asset("assets/locationpicker/locationpicker.jquery.js") }}"  type="text/javascript"></script>

    <script>

        function updateControls(addressComponents) {
            $('#house_no').val(addressComponents.addressLine1);
            $('#state').val(addressComponents.city);
            $('#province').val(addressComponents.stateOrProvince);
            $('#post_code').val(addressComponents.postalCode);
            $("#country option[value=" + addressComponents.country +"]").attr("selected","selected");
        }

        $('#locationpicker').locationpicker({
            location: {latitude: 23.80925758974614, longitude: 90.41546648789063},
            radius: 0,
            inputBinding: {
                latitudeInput: $('#location_lat'),
                longitudeInput: $('#location_lon'),
                locationNameInput: $('#road_no')
            },
            enableAutocomplete: true,
            onchanged: function (currentLocation, radius, isMarkerDropped) {
                var addressComponents = $(this).locationpicker('map').location.addressComponents;
                updateControls(addressComponents);
                //var mapContext = $(this).locationpicker('map');
                //mapContext.map.setZoom(14);
            },
            oninitialized: function (component) {
                var addressComponents = $(component).locationpicker('map').location.addressComponents;
                updateControls(addressComponents);
                var mapContext = $(component).locationpicker('map');
                mapContext.map.setZoom(16);
            }
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function () {
            $(function () {
                var _token = $('input[name="_token"]').val();
                $("#reg_form").validate({
                    errorPlacement: function () {
                        return false;
                    }
                });
            });
        });

        $(document).ready(function () {
            var today = new Date();
            var yyyy = today.getFullYear();
            $('.datepicker').datetimepicker({
                viewMode: 'years',
                format: 'DD-MMM-YYYY',
                maxDate: (new Date()),
                minDate: '01/01/' + (yyyy - 60)
            });

            $('#country').change(function (e) {
                if (this.value == 'BD') { // 001 is Bangladesh
                    $('#division_div').removeClass('hidden');
                    $('#division').addClass('required');
                    $('#district_div').removeClass('hidden');
                    $('#district').addClass('required');
                    $('#state_div').addClass('hidden');
                    $('#state').removeClass('required');
                    $('#province_div').addClass('hidden');
                    $('#province').removeClass('required');
                }
                else {
                    $('#state_div').removeClass('hidden');
                    $('#state').addClass('required');
                    $('#province_div').removeClass('hidden');
                    $('#province').addClass('required');
                    $('#division_div').addClass('hidden');
                    $('#division').removeClass('required');
                    $('#district_div').addClass('hidden');
                    $('#district').removeClass('required');
                }
            });
            $('#country').trigger('change');


            $('.identity_type').click(function (e) {
                if (this.value == '1') { // 1 is for passport
                    $('#passport_div').removeClass('hidden');
                    $('#passport_no').addClass('required');
                    $('#nid_div').addClass('hidden');
                    $('#user_nid').removeClass('required');
                }
                else { // 2 is for NID
                    $('#passport_div').addClass('hidden');
                    $('#passport_no').removeClass('required');
                    $('#nid_div').removeClass('hidden');
                    $('#user_nid').addClass('required');
                }
            });
            $('#identity_type').trigger('click');


            $("#division").change(function () {
                var divisionId = $('#division').val();
                $(this).after('<span class="loading_data">Loading...</span>');
                var self = $(this);
                $.ajax({
                    type: "GET",
                    url: "<?php echo url(); ?>/users/get-district-by-division",
                    data: {
                        divisionId: divisionId
                    },
                    success: function (response) {
                        var option = '<option value="">Select One</option>';
                        if (response.responseCode == 1) {
                            $.each(response.data, function (id, value) {
                                option += '<option value="' + id + '">' + value + '</option>';
                            });
                        }
                        $("#district").html(option);
                        $(self).next().hide();
                    }
                });
            });

//        $("#passport_no").blur(function () {
//            var passport_no = $(this).val().trim();
//            if (passport_no != '') {
//                $(this).after('<span class="loading_data">Loading...</span>');
//                var self = $(this);
//                if (passport_no.length == 9) {
//                    $.ajax({
//                        type: "GET",
//                        url: "<?php // echo url(); ?>/users/check-passport_no",
//                        data: {passport_no: passport_no,
//                        },
//                        success: function (res) {
//                            if (res > 0) {
//                                $('.btn-primary').attr("disabled", true);
//                                $('.pss-error').html('The passport number has already been used by some other user! Please contact system admin for details.');
//                            } else {
//                                $('.btn-primary').attr("disabled", false);
//                                $('.pss-error').html('');
//                            }
//                            self.next().hide();
//                        }
//                    });
//                } else {
//                    self.next().hide();
//                    $('.pss-error').html('Valid passport number required!');
//                    $('.btn-primary').attr("disabled", true);
//                }
//            }
//        });

        });
    </script>
    @endsection
