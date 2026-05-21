<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\DeliveryMan;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CollectCashMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $wallet;
    protected $name;
    protected $deliveryMan;

    public function __construct($wallet, $deliveryMan)
    {
        $this->wallet = $wallet;
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
        $data=EmailTemplate::where('type','dm')->where('email_type', 'cash_collect')->first();
        $template=$data?$data->email_template:6;
        $wallet = $this->wallet;
        $delivery_man_name = $this->name;
        $title = Helpers::text_variable_data_format( value:$data['title']??'',delivery_man_name:$delivery_man_name??'',transaction_id:$transaction_id??'');
        $body = Helpers::text_variable_data_format( value:$data['body']??'',delivery_man_name:$delivery_man_name??'',transaction_id:$transaction_id??'');
        $footer_text = Helpers::text_variable_data_format( value:$data['footer_text']??'',delivery_man_name:$delivery_man_name??'',transaction_id:$transaction_id??'');
        $copyright_text = Helpers::text_variable_data_format( value:$data['copyright_text']??'',delivery_man_name:$delivery_man_name??'',transaction_id:$transaction_id??'');
        return $this->subject(translate('Collect_Cash'))->view('email-templates.new-email-format-'.$template, [
            'company_name'=>$company_name,
            'data'=>$data,
            'title'=>Helpers::formatDeliverymanText($title, $this->deliveryMan),
            'body'=>Helpers::formatDeliverymanText($body, $this->deliveryMan),
            'footer_text'=>Helpers::formatDeliverymanText($footer_text, $this->deliveryMan),
            'copyright_text'=>Helpers::formatDeliverymanText($copyright_text, $this->deliveryMan),
            'wallet'=>$wallet,
            'transaction_id'=>$wallet->transaction_id,
            'time'=>$wallet->created_at,
            'amount'=>$wallet->amount
        ]);
    }
}
