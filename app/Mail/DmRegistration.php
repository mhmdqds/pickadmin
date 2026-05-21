<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\DeliveryMan;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DmRegistration extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $status;
    protected $name;
    protected $deliveryMan;

    public function __construct($status, $deliveryMan)
    {
        $this->status = $status;
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
        $data=EmailTemplate::where('type','admin')->where('email_type', 'dm_registration')->first();
        $template=$data?$data->email_template:1;
        $url = '';
        $dm_name = $this->name;
        $title = Helpers::text_variable_data_format( value:$data['title']??'',delivery_man_name:$dm_name??'');
        $body = Helpers::text_variable_data_format( value:$data['body']??'',delivery_man_name:$dm_name??'');
        $footer_text = Helpers::text_variable_data_format( value:$data['footer_text']??'',delivery_man_name:$dm_name??'');
        $copyright_text = Helpers::text_variable_data_format( value:$data['copyright_text']??'',delivery_man_name:$dm_name??'');
        $subject = Helpers::formatDeliverymanText(translate('Deliveryman_Registration'), $this->deliveryMan);
        return $this->subject($subject)->view('email-templates.new-email-format-'.$template, [
            'company_name'=>$company_name,
            'data'=>$data,
            'title'=>Helpers::formatDeliverymanText($title, $this->deliveryMan),
            'body'=>Helpers::formatDeliverymanText($body, $this->deliveryMan),
            'footer_text'=>Helpers::formatDeliverymanText($footer_text, $this->deliveryMan),
            'copyright_text'=>Helpers::formatDeliverymanText($copyright_text, $this->deliveryMan),
            'url'=>$url
        ]);
    }
}
