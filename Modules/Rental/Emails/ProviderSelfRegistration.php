<?php

namespace Modules\Rental\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use Illuminate\Queue\SerializesModels;
use Modules\Rental\Entities\RentalEmailTemplate;

class ProviderSelfRegistration extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $status;
    protected $name;

    public function __construct($status, $name)
    {
        $this->status = $status;
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $company_name = BusinessSetting::where('key', 'business_name')->first()->value;

        $status = $this->status;
        if($status == 'approved'){
            $data=RentalEmailTemplate::where('type','provider')->where('email_type', 'approve')->first();
        }elseif($status == 'denied'){
            $data=RentalEmailTemplate::where('type','provider')->where('email_type', 'deny')->first();
        }else{
            $data=RentalEmailTemplate::where('type','provider')->where('email_type', 'registration')->first();
        }
        $template=$data?$data->email_template:5;
        $url = '';
        $store_name = $this->name;
        $title = Helpers::text_variable_data_format( value:$data['title']??'',store_name:$store_name??'');
        $body = Helpers::text_variable_data_format( value:$data['body']??'',store_name:$store_name??'');
        $footer_text = Helpers::text_variable_data_format( value:$data['footer_text']??'',store_name:$store_name??'');
        $copyright_text = Helpers::text_variable_data_format( value:$data['copyright_text']??'',store_name:$store_name??'');
        return $this->subject(translate('New_Provider_Registration'))->view('email-templates.new-email-format-'.$template, ['company_name'=>$company_name,'data'=>$data,'title'=>$title,'body'=>$body,'footer_text'=>$footer_text,'copyright_text'=>$copyright_text,'url'=>$url]);
    }
}
