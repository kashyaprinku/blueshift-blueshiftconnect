/**
 * @category   Blueshift
 * @package    Blueshift_Blueshiftconnect
 * @author     Blueshift
 * @copyright  Copyright (c) Blueshift
 * @license    Blueshift
 */

require(['jquery', 'jquery/ui'], function($){
    jQuery(document).ready( function() {
        var dateVal = jQuery("#blueshiftconnect_step2_start_date").val();
        if(dateVal==''){ 
            setTimeout(function(){
                jQuery("#blueshiftconnect_step2_start_date").prop('disabled', false);
            }, 1000);
        }else{
            setTimeout(function(){
                jQuery("#blueshiftconnect_step2_start_date").prop('disabled', true);
            }, 600);
        }
        jQuery("#blueshiftconnect_step2_newlist").val("");
        var validate = jQuery("#blueshiftconnect_Step1_validate_value").val();
        var catalog = jQuery("#blueshiftconnect_step2_custom_dropdown").val();
        if(validate == 1 && catalog != 0 ){
            jQuery('#blueshiftconnect_step2-head').show(); 
            jQuery('#blueshiftconnect_step2-state').val(1);
            jQuery("#blueshiftconnect_Step1_userapikey").prop('readonly', true);
            jQuery("#blueshiftconnect_Step1_eventapikey").prop('readonly', true);
            jQuery('#row_blueshiftconnect_general_realtimesync p.note span').text("Enabled");
            jQuery('#row_blueshiftconnect_general_realtimesync p.note').css('background', 'green');
            jQuery('#row_blueshiftconnect_general_eventwebhooks p.note span').text("Enabled");
            jQuery('#row_blueshiftconnect_general_eventwebhooks p.note').css('background', 'green');
            jQuery('#row_blueshiftconnect_Step1_validate').css('display', 'none').addClass('supt');
            jQuery('#create_list_btn').css('display', 'none');
            jQuery('#row_blueshiftconnect_step2_newlist').css('display', 'none');
            setTimeout(function(){
                jQuery("#blueshiftconnect_step2_allow_customer_group").prop('disabled',true);
            }, 600);
            jQuery("#blueshiftconnect_step2_custom_dropdown").prop('disabled', true);
        }else if(validate == 1 && catalog == 0){
            jQuery('#row_blueshiftconnect_Step1_validate').css('display', 'none').addClass('supt');
            jQuery('#blueshiftconnect_step2-head').show(); 
            jQuery('#blueshiftconnect_step2-state').val(1);
            jQuery("#blueshiftconnect_Step1_userapikey").prop('readonly', true);
            jQuery("#blueshiftconnect_Step1_eventapikey").prop('readonly', true);
        }
        else{
            jQuery('#row_blueshiftconnect_general_realtimesync p.note span').text("Disabled");
            jQuery('#row_blueshiftconnect_general_realtimesync p.note').css('background', 'red');
            jQuery('#blueshiftconnect_step2-state').val(0);
            jQuery('#blueshiftconnect_step2-head').hide(); 
            jQuery('#blueshiftconnect_step2').hide();
        }
        jQuery("#edit_btn_id").click(function(){
            jQuery("#blueshiftconnect_Step1_userapikey").prop('readonly', false);
            jQuery("#blueshiftconnect_Step1_eventapikey").prop('readonly', false);
            jQuery('#row_blueshiftconnect_Step1_validate').css('display', 'table-row').removeClass('supt');
            jQuery('button#key_cancel_id').css('display', 'block');
        });
        jQuery("#sync_edit_btn_id").click(function(){
            jQuery("#blueshiftconnect_step2_custom_dropdown").prop('disabled', false);
            jQuery("#blueshiftconnect_step2_allow_customer_group").prop('disabled',false);
            jQuery('button#sync_cencal_id').css('display', 'block');
            jQuery('#create_list_btn').css('display', '');
            jQuery('#row_blueshiftconnect_step2_newlist').css('display', '');
             jQuery('#sync_edit_btn_id').css('display', 'none');
        });
    });
});