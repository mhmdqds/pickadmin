<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\DeliveryMan;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DmPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $otp;
    protected $name;
    protected $deliveryMan;

    public function __construct($otp, $deliveryMan)
    {
        $this->otp = $otp;
        $this->deliveryMan = $deliveryMan instanceof DeliveryMan ? $deliveryMan : null;
        $this->name = $deliveryMan instanceof DeliveryMan ? $deliveryMan->full_name : $deliveryMan;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $company_name = BusinessSetting::where('key', 'business_name')->first()->value;
        $data=EmailTemplate::where('type','dm')->where('email_type', 'forget_password')->first();
        $template=$data?$data->email_template:4;
        $code = $this->otp;
        $user_name = $this->name;
        $title = Helpers::text_variable_data_format( value:$data['title']??'',user_name:$user_name??'');
        $body = Helpers::text_variable_data_format( value:$data['body']??'',user_name:$user_name??'');
        $footer_text = Helpers::text_variable_data_format( value:$data['footer_text']??'',user_name:$user_name??'');
        $copyright_text = Helpers::text_variable_data_format( value:$data['copyright_text']??'',user_name:$user_name??'');
        return $this->subject(translate('Password_Reset'))->view('email-templates.new-email-format-'.$template, [
            'company_name'=>$company_name,
            'data'=>$data,
            'title'=>Helpers::formatDeliverymanText($title, $this->deliveryMan),
            'body'=>Helpers::formatDeliverymanText($body, $this->deliveryMan),
            'footer_text'=>Helpers::formatDeliverymanText($footer_text, $this->deliveryMan),
            'copyright_text'=>Helpers::formatDeliverymanText($copyright_text, $this->deliveryMan),
            'code'=>$code
        ]);
    }
}
