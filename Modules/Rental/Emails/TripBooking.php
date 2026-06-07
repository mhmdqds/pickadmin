<?php

namespace Modules\Rental\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\BusinessSetting;
use Modules\Rental\Entities\Trips;
use App\CentralLogics\Helpers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Rental\Entities\RentalEmailTemplate;

class TripBooking extends Mailable
{
    use Queueable, SerializesModels;

    protected $trip_id;
    public function __construct($trip_id)
    {
        $this->trip_id = $trip_id;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $trip_id = $this->trip_id;
        $trip=Trips::where('id', $trip_id)->first();

        $url=route('admin.trip_invoice',['id' => base64_encode($trip_id) ,'type' =>'trip']);

        $company_name = BusinessSetting::where('key', 'business_name')->first()->value;
        $data=RentalEmailTemplate::where('type','user')->where('email_type', 'new_order')->first();
        $template=$data?$data->email_template:3;
        $user_name = $trip->customer->f_name.' '.$trip->customer->l_name;
        $store_name = $trip->provider->name;

        $title = Helpers::text_variable_data_format( value:$data['title']??'',user_name:$user_name??'',store_name:$store_name??'',order_id:$trip_id??'');
        $body = Helpers::text_variable_data_format( value:$data['body']??'',user_name:$user_name??'',store_name:$store_name??'',order_id:$trip_id??'');
        $footer_text = Helpers::text_variable_data_format( value:$data['footer_text']??'',user_name:$user_name??'',store_name:$store_name??'',order_id:$trip_id??'');
        $copyright_text = Helpers::text_variable_data_format( value:$data['copyright_text']??'',user_name:$user_name??'',store_name:$store_name??'',order_id:$trip_id??'');
        return $this->subject(translate('Trip_Booked'))->view('email-templates.rental.new-email-format-'.$template, ['company_name'=>$company_name,'data'=>$data,'title'=>$title,'body'=>$body,'footer_text'=>$footer_text,'copyright_text'=>$copyright_text,'trip'=>$trip ,'url' => $url]);
    }
}
