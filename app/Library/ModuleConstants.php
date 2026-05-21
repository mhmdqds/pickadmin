<?php
const MAP_API_BASE_URI = 'https://maps.googleapis.com/maps/api';
//driver & vehicle delete array
const DV_DELETE_TRIP_CURRENT_STATUS = ['accepted', 'ongoing'];
const PENDING = 'pending';

const SCHEDULED = 'scheduled';
const ACCEPTED = 'accepted';
const ONGOING = 'ongoing';
const CANCELLED = 'cancelled';
const COMPLETED = 'completed';
const RETURNING = 'returning';
const RETURNED = 'returned';
const APPROVED = 'approved';
const DENIED = 'denied';
const REFUNDED = 'refunded';
const SETTLED = 'settled';
const PAID = 'paid';
const UNPAID = 'unpaid';
const PARTIAL_PAID = 'partial_paid';
const DUE = 'due';

const SENDER = 'sender';
const RECEIVER = 'receiver';

const RUNNING = 'running';
const CURRENTLY_OFF = 'currently_off';
const UPCOMING = 'upcoming';
const EXPIRED = 'expired';

const BUSINESS_START_DATE = "2023-10-01";
const DATE_FORMAT = "d/m/Y";
const DASHBOARD_RECENT_TRANSACTIONS_DATE_FORMAT = "d M h:i a";
const DASHBOARD_RECENT_TRIP_ACTIVITY_DATE_FORMAT = "m-d-Y, h:i A";
const ALL = 'all';
const CUSTOM = 'custom';
const PARCEL = 'parcel';
const RIDE_REQUEST = 'ride_request';
const AMOUNT = 'amount';
const PERCENTAGE = 'percentage';

const COUPON = 'coupon';
const DISCOUNT = 'discount';

const CANCELLATION_TYPE = [
    'accepted_ride' => 'Before Pickup',
    'ongoing_ride' => 'Ongoing Ride',
];

//passport address type
const ADMIN_PANEL_ACCESS = "AccessToAdmin";
const CUSTOMER_PANEL_ACCESS = "AccessToCustomer";
const DRIVER_PANEL_ACCESS = "AccessToDriver";

//users
const ADMIN_USER_TYPES = ['super-admin', 'admin-employee'];
const CUSTOMER_USER_TYPES = ['customer'];
const DRIVER_USER_TYPES = 'driver';
const CUSTOMER = 'customer';
const DRIVER = 'driver';
const ALL_DRIVER = 'all-driver';
const DRIVER_ON_TRIP = 'driver-on-trip';
const DRIVER_IDLE = 'driver-idle';
const ALL_CUSTOMER = 'all-customer';

const SUPPORT = 'support';
const SUPPORT_CENTER = 'support_center';
const NO_REWARDS = 'no_rewards';
const LOYALTY_POINTS = 'loyalty_points';
const WALLET = 'wallet';

const SAFETY_ALERT = 'safety-alert';
const PRECAUTION = 'precaution';

// REQUEST METHOD KEYS
const WEB = 'web';
const PAGINATE = 'paginate';
const AJAX = 'ajax';
const EXCEPT_PAGINATE = 'except_paginate';
const FILTER = 'filter';
const FILTER_BY_ID = 'filter_by_id';
const EXCEPT_FILTER = 'except_filter';
const STORE = 'store';
const UPDATE = 'update';

const DESTROY = 'destroy';

// filter
const TODAY = 'today';
const PREVIOUS_DAY = 'previous_day';
const THIS_WEEK = 'this_week';
const LAST_WEEK = 'last_week';
const LAST_7_DAYS = 'last_7_days';
const THIS_MONTH = 'this_month';
const LAST_MONTH = 'last_month';
const THIS_YEAR = 'this_year';
const ALL_TIME = 'all_time';
const CUSTOM_DATE = 'custom_date';

const KG = 'kg'; // Kilograms
const LB = 'lb'; // Pounds
const G = 'g'; // Grams
const OZ = 'oz'; // Ounces
const T = 't'; // Metric Tons

const MG = 'mg'; // Milligrams
const ST = 'st'; // Stones
const SHORT_TON = 'short_ton'; // Short Tons (US)
const LONG_TON = 'long_ton'; // Long Tons (UK)

const CT = 'ct'; // Carats
const TROY_OZ = 'troy_oz'; // Troy Ounces (Precious Metals)

const WEIGHT_UNIT = [
    KG => 'Kilograms',
    LB => 'Pounds',
    G => 'Grams',
    OZ => 'Ounces',
    T => 'Metric Tons',
    MG => 'Milligrams',
    ST => 'Stones',
    SHORT_TON => 'Short Tons',
    LONG_TON => 'Long Tons',
    CT => 'Carats',
    TROY_OZ => 'Troy Ounces',
];


const FUEL_TYPES = [
    'petrol' => 'Petrol',
    'diesel' => 'Diesel',
    'cng' => 'CNG',
    'lpg' => 'LPG',
];

const UPDATE_VEHICLE = [
    'vehicle_brand',
    'vehicle_category',
    'license_plate_number',
    'license_expiry_date',
];

//system defaults
const DEFAULT_PAGINATION = 25;


const GOVT_EMERGENCY_NUMBER_TYPE = [
  'phone' => 'Phone',
  'telephone' => 'Telephone',
  'hotline' => 'Hotline',
];


////Business Settings Management

const APP_VERSION = "app_version";
const ALL_ZONE_EXTRA_FARE = "all_zone_extra_fare";
const BUSINESS_INFORMATION = "business_information";
const CACHE_BUSINESS_SETTINGS = "cache_business_settings";
const BUSINESS_SETTINGS = "business_settings";
const DRIVER_SETTINGS = "driver_settings";
const DRIVER_REVIEW = "driver_review";
const DRIVER_LEVEL = "driver_level";
const CUSTOMER_SETTINGS = "customer_settings";
const CUSTOMER_REVIEW = "customer_review";
const CUSTOMER_LEVEL = "customer_level";
const NOTIFICATION_SETTINGS = "notification_settings";
const PAGES_SETTINGS = "pages_settings";
const LANDING_PAGES_SETTINGS = "landing_pages_settings";
const SERVER_KEY = "server_key";

const INTRO_SECTION = "intro_section";
const INTRO_SECTION_IMAGE = "intro_section_image";
const OUR_SOLUTIONS_SECTION = "our_solutions";
const OUR_SOLUTIONS_DATA = "our_solutions_data";
const BUSINESS_STATISTICS = "business_statistics";
const EARN_MONEY = "earn_money";
const EARN_MONEY_IMAGE = "earn_money_image";
const TESTIMONIAL = "testimonial";
const CTA = "cta";
const CTA_IMAGE = "cta_image";

const EMAIL_CONFIG = "email_config";
const SMS_CONFIG = "sms_config";
const FIREBASE_OTP = "firebase_otp";
const PAYMENT_CONFIG = "payment_config";
const SOCIAL_LOGIN = "social_login";

const TRIP_SETTINGS = "trip_settings";
const RIDE_SHARE_BUSINESS_SETTINGS = "ride_share_business_settings";
const PARCEL_SETTINGS = "parcel_settings";
const TRIP_FARE_SETTINGS = "trip_fare_settings";

const RECAPTCHA = "recaptcha";
const GOOGLE_MAP_API = "google_map_api";

const SYSTEM_LANGUAGE = "system_language";
const LANGUAGE_SETTINGS = "language_settings";
const CUSTOMER_APP_VERSION_CONTROL_FOR_ANDROID = "customer_app_version_control_for_android";
const CUSTOMER_APP_VERSION_CONTROL_FOR_IOS = "customer_app_version_control_for_ios";
const DRIVER_APP_VERSION_CONTROL_FOR_ANDROID = "driver_app_version_control_for_android";
const DRIVER_APP_VERSION_CONTROL_FOR_IOS = "driver_app_version_control_for_ios";


const CHATTING_SETTINGS = "chatting_settings";

const SAFETY_FEATURE_SETTINGS = "safety_feature_settings";




//// demandium

// const ADMIN_PANEL_ACCESS = "AccessToAdmin";
const PROVIDER_PANEL_ACCESS = "AccessToProvider";
// const CUSTOMER_PANEL_ACCESS = "AccessToCustomer";
const SERVICEMAN_APP_ACCESS = "AccessToServicemanApp";

// const ADMIN_USER_TYPES = ['super-admin', 'admin-employee'];
const PROVIDER_USER_TYPES = ['provider-admin', 'provider-employee', 'provider-serviceman'];
// const CUSTOMER_USER_TYPES = ['customer'];
const SERVICEMAN_USER_TYPES = PROVIDER_USER_TYPES[2];
const PROVIDER = "provider";
const SERVICEMAN = "serviceman";

const IMAGEEXTENSION = [
    ['key' => 'png', 'value' => 'Png'],
    ['key' => 'jpg', 'value' => 'Jpg'],
    ['key' => 'jpeg', 'value' => 'Jpeg'],
    ['key' => 'gif', 'value' => 'gif'],
];

const IMAGEFILESIZE = [
    ['key' => '1mb', 'value' => '1024'],
    ['key' => '2mb', 'value' => '2048'],
    ['key' => '3mb', 'value' => '3072'],
    ['key' => '4mb', 'value' => '4096'],
    ['key' => '5mb', 'value' => '5120'],
    ['key' => '6mb', 'value' => '6144'],
    ['key' => '7mb', 'value' => '7168'],
    ['key' => '8mb', 'value' => '8192'],
    ['key' => '9mb', 'value' => '9216'],
    ['key' => '10mb', 'value' => '10240'],
];

// const DEFAULT_PAGINATION = 25;

const COUPON_TYPES = [
    'default' => 'Default',
    'first_booking' => 'First Booking',
    'customer_wise' => 'Customer Wise'
];

const COUPON_TYPES_REACT_FORMAT = [
    ['value' => 'default', 'name' => 'Default'],
    ['value' => 'first_order', 'name' => 'First Order']
];



const CURRENCIES = [
    ["code" => "AED", "symbol" => "د.إ", "name" => "UAE dirham"],
    ["code" => "AFN", "symbol" => "Afs", "name" => "Afghan afghani"],
    ["code" => "ALL", "symbol" => "L", "name" => "Albanian lek"],
    ["code" => "AMD", "symbol" => "AMD", "name" => "Armenian dram"],
    ["code" => "ANG", "symbol" => "NAƒ", "name" => "Netherlands Antillean gulden"],
    ["code" => "AOA", "symbol" => "Kz", "name" => "Angolan kwanza"],
    ["code" => "ARS", "symbol" => "$", "name" => "Argentine peso"],
    ["code" => "AUD", "symbol" => "$", "name" => "Australian dollar"],
    ["code" => "AWG", "symbol" => "ƒ", "name" => "Aruban florin"],
    ["code" => "AZN", "symbol" => "AZN", "name" => "Azerbaijani manat"],
    ["code" => "BAM", "symbol" => "KM", "name" => "Bosnia and Herzegovina konvertibilna marka"],
    ["code" => "BBD", "symbol" => "Bds$", "name" => "Barbadian dollar"],
    ["code" => "BDT", "symbol" => "৳", "name" => "Bangladeshi taka"],
    ["code" => "BGN", "symbol" => "BGN", "name" => "Bulgarian lev"],
    ["code" => "BHD", "symbol" => ".د.ب", "name" => "Bahraini dinar"],
    ["code" => "BIF", "symbol" => "FBu", "name" => "Burundi franc"],
    ["code" => "BMD", "symbol" => "BD$", "name" => "Bermudian dollar"],
    ["code" => "BND", "symbol" => "B$", "name" => "Brunei dollar"],
    ["code" => "BOB", "symbol" => "Bs.", "name" => "Bolivian boliviano"],
    ["code" => "BRL", "symbol" => "R$", "name" => "Brazilian real"],
    ["code" => "BSD", "symbol" => "B$", "name" => "Bahamian dollar"],
    ["code" => "BTN", "symbol" => "Nu.", "name" => "Bhutanese ngultrum"],
    ["code" => "BWP", "symbol" => "P", "name" => "Botswana pula"],
    ["code" => "BYR", "symbol" => "Br", "name" => "Belarusian ruble"],
    ["code" => "BZD", "symbol" => "BZ$", "name" => "Belize dollar"],
    ["code" => "CAD", "symbol" => "$", "name" => "Canadian dollar"],
    ["code" => "CDF", "symbol" => "F", "name" => "Congolese franc"],
    ["code" => "CHF", "symbol" => "Fr.", "name" => "Swiss franc"],
    ["code" => "CLP", "symbol" => "$", "name" => "Chilean peso"],
    ["code" => "CNY", "symbol" => "¥", "name" => "Chinese/Yuan renminbi"],
    ["code" => "COP", "symbol" => "Col$", "name" => "Colombian peso"],
    ["code" => "CRC", "symbol" => "₡", "name" => "Costa Rican colon"],
    ["code" => "CUC", "symbol" => "$", "name" => "Cuban peso"],
    ["code" => "CVE", "symbol" => "Esc", "name" => "Cape Verdean escudo"],
    ["code" => "CZK", "symbol" => "Kč", "name" => "Czech koruna"],
    ["code" => "DJF", "symbol" => "Fdj", "name" => "Djiboutian franc"],
    ["code" => "DKK", "symbol" => "Kr", "name" => "Danish krone"],
    ["code" => "DOP", "symbol" => "RD$", "name" => "Dominican peso"],
    ["code" => "DZD", "symbol" => "دج", "name" => "Algerian dinar"],
    ["code" => "EEK", "symbol" => "KR", "name" => "Estonian kroon"],
    ["code" => "EGP", "symbol" => "e£", "name" => "Egyptian pound"],
    ["code" => "ERN", "symbol" => "Nfa", "name" => "Eritrean nakfa"],
    ["code" => "ETB", "symbol" => "Br", "name" => "Ethiopian birr"],
    ["code" => "EUR", "symbol" => "€", "name" => "European Euro"],
    ["code" => "FJD", "symbol" => "FJ$", "name" => "Fijian dollar"],
    ["code" => "FKP", "symbol" => "£", "name" => "Falkland Islands pound"],
    ["code" => "GBP", "symbol" => "£", "name" => "British pound"],
    ["code" => "GEL", "symbol" => "GEL", "name" => "Georgian lari"],
    ["code" => "GHS", "symbol" => "GH¢", "name" => "Ghanaian cedi"],
    ["code" => "GIP", "symbol" => "£", "name" => "Gibraltar pound"],
    ["code" => "GMD", "symbol" => "D", "name" => "Gambian dalasi"],
    ["code" => "GNF", "symbol" => "FG", "name" => "Guinean franc"],
    ["code" => "GQE", "symbol" => "CFA", "name" => "Central African CFA franc"],
    ["code" => "GTQ", "symbol" => "Q", "name" => "Guatemalan quetzal"],
    ["code" => "GYD", "symbol" => "GY$", "name" => "Guyanese dollar"],
    ["code" => "HKD", "symbol" => "HK$", "name" => "Hong Kong dollar"],
    ["code" => "HNL", "symbol" => "L", "name" => "Honduran lempira"],
    ["code" => "HRK", "symbol" => "kn", "name" => "Croatian kuna"],
    ["code" => "HTG", "symbol" => "G", "name" => "Haitian gourde"],
    ["code" => "HUF", "symbol" => "Ft", "name" => "Hungarian forint"],
    ["code" => "IDR", "symbol" => "Rp", "name" => "Indonesian rupiah"],
    ["code" => "ILS", "symbol" => "₪", "name" => "Israeli new sheqel"],
    ["code" => "INR", "symbol" => "₹", "name" => "Indian rupee"],
    ["code" => "IQD", "symbol" => "ع.د", "name" => "Iraqi dinar"],
    ["code" => "IRR", "symbol" => "IRR", "name" => "Iranian rial"],
    ["code" => "ISK", "symbol" => "kr", "name" => "Icelandic kr\u00f3na"],
    ["code" => "JMD", "symbol" => "J$", "name" => "Jamaican dollar"],
    ["code" => "JOD", "symbol" => "JOD", "name" => "Jordanian dinar"],
    ["code" => "JPY", "symbol" => "¥", "name" => "Japanese yen"],
    ["code" => "KES", "symbol" => "KSh", "name" => "Kenyan shilling"],
    ["code" => "KGS", "symbol" => "Лв", "name" => "Kyrgyzstani som"],
    ["code" => "KHR", "symbol" => "៛", "name" => "Cambodian riel"],
    ["code" => "KMF", "symbol" => "KMF", "name" => "Comorian franc"],
    ["code" => "KPW", "symbol" => "W", "name" => "North Korean won"],
    ["code" => "KRW", "symbol" => "W", "name" => "South Korean won"],
    ["code" => "KWD", "symbol" => "KWD", "name" => "Kuwaiti dinar"],
    ["code" => "KYD", "symbol" => "KY$", "name" => "Cayman Islands dollar"],
    ["code" => "KZT", "symbol" => "T", "name" => "Kazakhstani tenge"],
    ["code" => "LAK", "symbol" => "KN", "name" => "Lao kip"],
    ["code" => "LBP", "symbol" => ".ل.ل", "name" => "Lebanese lira"],
    ["code" => "LKR", "symbol" => "Rs", "name" => "Sri Lankan rupee"],
    ["code" => "LRD", "symbol" => "L$", "name" => "Liberian dollar"],
    ["code" => "LSL", "symbol" => "M", "name" => "Lesotho loti"],
    ["code" => "LTL", "symbol" => "Lt", "name" => "Lithuanian litas"],
    ["code" => "LVL", "symbol" => "Ls", "name" => "Latvian lats"],
    ["code" => "LYD", "symbol" => "LD", "name" => "Libyan dinar"],
    ["code" => "MAD", "symbol" => "MAD", "name" => "Morocodean dirham"],
    ["code" => "MDL", "symbol" => "MDL", "name" => "Moldovan leu"],
    ["code" => "MGA", "symbol" => "FMG", "name" => "Malagasy ariary"],
    ["code" => "MKD", "symbol" => "MKD", "name" => "Macedonian denar"],
    ["code" => "MMK", "symbol" => "K", "name" => "Myanma kyat"],
    ["code" => "MNT", "symbol" => "₮", "name" => "Mongolian tugrik"],
    ["code" => "MOP", "symbol" => "P", "name" => "Macanese pataca"],
    ["code" => "MRO", "symbol" => "UM", "name" => "Mauritanian ouguiya"],
    ["code" => "MUR", "symbol" => "Rs", "name" => "Mauritian rupee"],
    ["code" => "MVR", "symbol" => "Rf", "name" => "Maldivian rufiyaa"],
    ["code" => "MWK", "symbol" => "MK", "name" => "Malawian kwacha"],
    ["code" => "MXN", "symbol" => "$", "name" => "Mexican peso"],
    ["code" => "MYR", "symbol" => "RM", "name" => "Malaysian ringgit"],
    ["code" => "MZM", "symbol" => "MTn", "name" => "Mozambican metical"],
    ["code" => "NAD", "symbol" => "N$", "name" => "Namibian dollar"],
    ["code" => "NGN", "symbol" => "₦", "name" => "Nigerian naira"],
    ["code" => "NIO", "symbol" => "C$", "name" => "Nicaraguan c\u00f3rdoba"],
    ["code" => "NOK", "symbol" => "kr", "name" => "Norwegian krone"],
    ["code" => "NPR", "symbol" => "NRs", "name" => "Nepalese rupee"],
    ["code" => "NZD", "symbol" => "NZ$", "name" => "New Zealand dollar"],
    ["code" => "OMR", "symbol" => "OMR", "name" => "Omani rial"],
    ["code" => "PAB", "symbol" => "B/.", "name" => "Panamanian balboa"],
    ["code" => "PEN", "symbol" => "S/.", "name" => "Peruvian nuevo sol"],
    ["code" => "PGK", "symbol" => "K", "name" => "Papua New Guinean kina"],
    ["code" => "PHP", "symbol" => "₱", "name" => "Philippine peso"],
    ["code" => "PKR", "symbol" => "Rs.", "name" => "Pakistani rupee"],
    ["code" => "PLN", "symbol" => "zł", "name" => "Polish zloty"],
    ["code" => "PYG", "symbol" => "₲", "name" => "Paraguayan guarani"],
    ["code" => "QAR", "symbol" => "QR", "name" => "Qatari riyal"],
    ["code" => "RON", "symbol" => "L", "name" => "Romanian leu"],
    ["code" => "RSD", "symbol" => "din.", "name" => "Serbian dinar"],
    ["code" => "RUB", "symbol" => "R", "name" => "Russian ruble"],
    ["code" => "SAR", "symbol" => "SR", "name" => "Saudi riyal"],
    ["code" => "SBD", "symbol" => "SI$", "name" => "Solomon Islands dollar"],
    ["code" => "SCR", "symbol" => "SR", "name" => "Seychellois rupee"],
    ["code" => "SDG", "symbol" => "SDG", "name" => "Sudanese pound"],
    ["code" => "SEK", "symbol" => "kr", "name" => "Swedish krona"],
    ["code" => "SGD", "symbol" => "S$", "name" => "Singapore dollar"],
    ["code" => "SHP", "symbol" => "£", "name" => "Saint Helena pound"],
    ["code" => "SLL", "symbol" => "Le", "name" => "Sierra Leonean leone"],
    ["code" => "SOS", "symbol" => "Sh.", "name" => "Somali shilling"],
    ["code" => "SRD", "symbol" => "$", "name" => "Surinamese dollar"],
    ["code" => "SYP", "symbol" => "LS", "name" => "Syrian pound"],
    ["code" => "SZL", "symbol" => "E", "name" => "Swazi lilangeni"],
    ["code" => "THB", "symbol" => "฿", "name" => "Thai baht"],
    ["code" => "TJS", "symbol" => "TJS", "name" => "Tajikistani somoni"],
    ["code" => "TMT", "symbol" => "m", "name" => "Turkmen manat"],
    ["code" => "TND", "symbol" => "DT", "name" => "Tunisian dinar"],
    ["code" => "TRY", "symbol" => "TRY", "name" => "Turkish new lira"],
    ["code" => "TTD", "symbol" => "TT$", "name" => "Trinidad and Tobago dollar"],
    ["code" => "TWD", "symbol" => "NT$", "name" => "New Taiwan dollar"],
    ["code" => "TZS", "symbol" => "TZS", "name" => "Tanzanian shilling"],
    ["code" => "UAH", "symbol" => "UAH", "name" => "Ukrainian hryvnia"],
    ["code" => "UGX", "symbol" => "USh", "name" => "Ugandan shilling"],
    ["code" => "USD", "symbol" => "$", "name" => "United States dollar"],
    ["code" => "UYU", "symbol" => '$U', "name" => "Uruguayan peso"],
    ["code" => "UZS", "symbol" => "UZS", "name" => "Uzbekistani som"],
    ["code" => "VEB", "symbol" => "Bs", "name" => "Venezuelan bolivar"],
    ["code" => "VND", "symbol" => "₫", "name" => "Vietnamese dong"],
    ["code" => "VUV", "symbol" => "VT", "name" => "Vanuatu vatu"],
    ["code" => "WST", "symbol" => "WS$", "name" => "Samoan tala"],
    ["code" => "XAF", "symbol" => "CFA", "name" => "Central African CFA franc"],
    ["code" => "XCD", "symbol" => "EC$", "name" => "East Caribbean dollar"],
    ["code" => "XDR", "symbol" => "SDR", "name" => "Special Drawing Rights"],
    ["code" => "XOF", "symbol" => "CFA", "name" => "West African CFA franc"],
    ["code" => "XPF", "symbol" => "F", "name" => "CFP franc"],
    ["code" => "YER", "symbol" => "YER", "name" => "Yemeni rial"],
    ["code" => "ZAR", "symbol" => "R", "name" => "South African rand"],
    ["code" => "ZMK", "symbol" => "ZK", "name" => "Zambian kwacha"],
    ["code" => "ZWR", "symbol" => "Z$", "name" => "Zimbabwean dollar"]
];

const COUNTRIES = [
    ["name" => 'Afghanistan', "code" => 'AF'],
    ["name" => 'Åland Islands', "code" => 'AX'],
    ["name" => 'Albania', "code" => 'AL'],
    ["name" => 'Algeria', "code" => 'DZ'],
    ["name" => 'American Samoa', "code" => 'AS'],
    ["name" => 'AndorrA', "code" => 'AD'],
    ["name" => 'Angola', "code" => 'AO'],
    ["name" => 'Anguilla', "code" => 'AI'],
    ["name" => 'Antarctica', "code" => 'AQ'],
    ["name" => 'Antigua and Barbuda', "code" => 'AG'],
    ["name" => 'Argentina', "code" => 'AR'],
    ["name" => 'Armenia', "code" => 'AM'],
    ["name" => 'Aruba', "code" => 'AW'],
    ["name" => 'Australia', "code" => 'AU'],
    ["name" => 'Austria', "code" => 'AT'],
    ["name" => 'Azerbaijan', "code" => 'AZ'],
    ["name" => 'Bahamas', "code" => 'BS'],
    ["name" => 'Bahrain', "code" => 'BH'],
    ["name" => 'Bangladesh', "code" => 'BD'],
    ["name" => 'Barbados', "code" => 'BB'],
    ["name" => 'Belarus', "code" => 'BY'],
    ["name" => 'Belgium', "code" => 'BE'],
    ["name" => 'Belize', "code" => 'BZ'],
    ["name" => 'Benin', "code" => 'BJ'],
    ["name" => 'Bermuda', "code" => 'BM'],
    ["name" => 'Bhutan', "code" => 'BT'],
    ["name" => 'Bolivia', "code" => 'BO'],
    ["name" => 'Bosnia and Herzegovina', "code" => 'BA'],
    ["name" => 'Botswana', "code" => 'BW'],
    ["name" => 'Bouvet Island', "code" => 'BV'],
    ["name" => 'Brazil', "code" => 'BR'],
    ["name" => 'British Indian Ocean Territory', "code" => 'IO'],
    ["name" => 'Brunei Darussalam', "code" => 'BN'],
    ["name" => 'Bulgaria', "code" => 'BG'],
    ["name" => 'Burkina Faso', "code" => 'BF'],
    ["name" => 'Burundi', "code" => 'BI'],
    ["name" => 'Cambodia', "code" => 'KH'],
    ["name" => 'Cameroon', "code" => 'CM'],
    ["name" => 'Canada', "code" => 'CA'],
    ["name" => 'Cape Verde', "code" => 'CV'],
    ["name" => 'Cayman Islands', "code" => 'KY'],
    ["name" => 'Central African Republic', "code" => 'CF'],
    ["name" => 'Chad', "code" => 'TD'],
    ["name" => 'Chile', "code" => 'CL'],
    ["name" => 'China', "code" => 'CN'],
    ["name" => 'Christmas Island', "code" => 'CX'],
    ["name" => 'Cocos (Keeling) Islands', "code" => 'CC'],
    ["name" => 'Colombia', "code" => 'CO'],
    ["name" => 'Comoros', "code" => 'KM'],
    ["name" => 'Congo', "code" => 'CG'],
    ["name" => 'Congo, The Democratic Republic of the', "code" => 'CD'],
    ["name" => 'Cook Islands', "code" => 'CK'],
    ["name" => 'Costa Rica', "code" => 'CR'],
    ["name" => 'Cote D\'Ivoire', "code" => 'CI'],
    ["name" => 'Croatia', "code" => 'HR'],
    ["name" => 'Cuba', "code" => 'CU'],
    ["name" => 'Cyprus', "code" => 'CY'],
    ["name" => 'Czech Republic', "code" => 'CZ'],
    ["name" => 'Denmark', "code" => 'DK'],
    ["name" => 'Djibouti', "code" => 'DJ'],
    ["name" => 'Dominica', "code" => 'DM'],
    ["name" => 'Dominican Republic', "code" => 'DO'],
    ["name" => 'Ecuador', "code" => 'EC'],
    ["name" => 'Egypt', "code" => 'EG'],
    ["name" => 'El Salvador', "code" => 'SV'],
    ["name" => 'Equatorial Guinea', "code" => 'GQ'],
    ["name" => 'Eritrea', "code" => 'ER'],
    ["name" => 'Estonia', "code" => 'EE'],
    ["name" => 'Ethiopia', "code" => 'ET'],
    ["name" => 'Falkland Islands (Malvinas)', "code" => 'FK'],
    ["name" => 'Faroe Islands', "code" => 'FO'],
    ["name" => 'Fiji', "code" => 'FJ'],
    ["name" => 'Finland', "code" => 'FI'],
    ["name" => 'France', "code" => 'FR'],
    ["name" => 'French Guiana', "code" => 'GF'],
    ["name" => 'French Polynesia', "code" => 'PF'],
    ["name" => 'French Southern Territories', "code" => 'TF'],
    ["name" => 'Gabon', "code" => 'GA'],
    ["name" => 'Gambia', "code" => 'GM'],
    ["name" => 'Georgia', "code" => 'GE'],
    ["name" => 'Germany', "code" => 'DE'],
    ["name" => 'Ghana', "code" => 'GH'],
    ["name" => 'Gibraltar', "code" => 'GI'],
    ["name" => 'Greece', "code" => 'GR'],
    ["name" => 'Greenland', "code" => 'GL'],
    ["name" => 'Grenada', "code" => 'GD'],
    ["name" => 'Guadeloupe', "code" => 'GP'],
    ["name" => 'Guam', "code" => 'GU'],
    ["name" => 'Guatemala', "code" => 'GT'],
    ["name" => 'Guernsey', "code" => 'GG'],
    ["name" => 'Guinea', "code" => 'GN'],
    ["name" => 'Guinea-Bissau', "code" => 'GW'],
    ["name" => 'Guyana', "code" => 'GY'],
    ["name" => 'Haiti', "code" => 'HT'],
    ["name" => 'Heard Island and Mcdonald Islands', "code" => 'HM'],
    ["name" => 'Holy See (Vatican City State)', "code" => 'VA'],
    ["name" => 'Honduras', "code" => 'HN'],
    ["name" => 'Hong Kong', "code" => 'HK'],
    ["name" => 'Hungary', "code" => 'HU'],
    ["name" => 'Iceland', "code" => 'IS'],
    ["name" => 'India', "code" => 'IN'],
    ["name" => 'Indonesia', "code" => 'ID'],
    ["name" => 'Iran, Islamic Republic Of', "code" => 'IR'],
    ["name" => 'Iraq', "code" => 'IQ'],
    ["name" => 'Ireland', "code" => 'IE'],
    ["name" => 'Isle of Man', "code" => 'IM'],
    ["name" => 'Israel', "code" => 'IL'],
    ["name" => 'Italy', "code" => 'IT'],
    ["name" => 'Jamaica', "code" => 'JM'],
    ["name" => 'Japan', "code" => 'JP'],
    ["name" => 'Jersey', "code" => 'JE'],
    ["name" => 'Jordan', "code" => 'JO'],
    ["name" => 'Kazakhstan', "code" => 'KZ'],
    ["name" => 'Kenya', "code" => 'KE'],
    ["name" => 'Kiribati', "code" => 'KI'],
    ["name" => 'Korea, Democratic People\'S Republic of', "code" => 'KP'],
    ["name" => 'Korea, Republic of', "code" => 'KR'],
    ["name" => 'Kuwait', "code" => 'KW'],
    ["name" => 'Kyrgyzstan', "code" => 'KG'],
    ["name" => 'Lao People\'S Democratic Republic', "code" => 'LA'],
    ["name" => 'Latvia', "code" => 'LV'],
    ["name" => 'Lebanon', "code" => 'LB'],
    ["name" => 'Lesotho', "code" => 'LS'],
    ["name" => 'Liberia', "code" => 'LR'],
    ["name" => 'Libyan Arab Jamahiriya', "code" => 'LY'],
    ["name" => 'Liechtenstein', "code" => 'LI'],
    ["name" => 'Lithuania', "code" => 'LT'],
    ["name" => 'Luxembourg', "code" => 'LU'],
    ["name" => 'Macao', "code" => 'MO'],
    ["name" => 'Macedonia, The Former Yugoslav Republic of', "code" => 'MK'],
    ["name" => 'Madagascar', "code" => 'MG'],
    ["name" => 'Malawi', "code" => 'MW'],
    ["name" => 'Malaysia', "code" => 'MY'],
    ["name" => 'Maldives', "code" => 'MV'],
    ["name" => 'Mali', "code" => 'ML'],
    ["name" => 'Malta', "code" => 'MT'],
    ["name" => 'Marshall Islands', "code" => 'MH'],
    ["name" => 'Martinique', "code" => 'MQ'],
    ["name" => 'Mauritania', "code" => 'MR'],
    ["name" => 'Mauritius', "code" => 'MU'],
    ["name" => 'Mayotte', "code" => 'YT'],
    ["name" => 'Mexico', "code" => 'MX'],
    ["name" => 'Micronesia, Federated States of', "code" => 'FM'],
    ["name" => 'Moldova, Republic of', "code" => 'MD'],
    ["name" => 'Monaco', "code" => 'MC'],
    ["name" => 'Mongolia', "code" => 'MN'],
    ["name" => 'Montserrat', "code" => 'MS'],
    ["name" => 'Morocco', "code" => 'MA'],
    ["name" => 'Mozambique', "code" => 'MZ'],
    ["name" => 'Myanmar', "code" => 'MM'],
    ["name" => 'Namibia', "code" => 'NA'],
    ["name" => 'Nauru', "code" => 'NR'],
    ["name" => 'Nepal', "code" => 'NP'],
    ["name" => 'Netherlands', "code" => 'NL'],
    ["name" => 'Netherlands Antilles', "code" => 'AN'],
    ["name" => 'New Caledonia', "code" => 'NC'],
    ["name" => 'New Zealand', "code" => 'NZ'],
    ["name" => 'Nicaragua', "code" => 'NI'],
    ["name" => 'Niger', "code" => 'NE'],
    ["name" => 'Nigeria', "code" => 'NG'],
    ["name" => 'Niue', "code" => 'NU'],
    ["name" => 'Norfolk Island', "code" => 'NF'],
    ["name" => 'Northern Mariana Islands', "code" => 'MP'],
    ["name" => 'Norway', "code" => 'NO'],
    ["name" => 'Oman', "code" => 'OM'],
    ["name" => 'Pakistan', "code" => 'PK'],
    ["name" => 'Palau', "code" => 'PW'],
    ["name" => 'Palestinian Territory, Occupied', "code" => 'PS'],
    ["name" => 'Panama', "code" => 'PA'],
    ["name" => 'Papua New Guinea', "code" => 'PG'],
    ["name" => 'Paraguay', "code" => 'PY'],
    ["name" => 'Peru', "code" => 'PE'],
    ["name" => 'Philippines', "code" => 'PH'],
    ["name" => 'Pitcairn', "code" => 'PN'],
    ["name" => 'Poland', "code" => 'PL'],
    ["name" => 'Portugal', "code" => 'PT'],
    ["name" => 'Puerto Rico', "code" => 'PR'],
    ["name" => 'Qatar', "code" => 'QA'],
    ["name" => 'Reunion', "code" => 'RE'],
    ["name" => 'Romania', "code" => 'RO'],
    ["name" => 'Russian Federation', "code" => 'RU'],
    ["name" => 'RWANDA', "code" => 'RW'],
    ["name" => 'Saint Helena', "code" => 'SH'],
    ["name" => 'Saint Kitts and Nevis', "code" => 'KN'],
    ["name" => 'Saint Lucia', "code" => 'LC'],
    ["name" => 'Saint Pierre and Miquelon', "code" => 'PM'],
    ["name" => 'Saint Vincent and the Grenadines', "code" => 'VC'],
    ["name" => 'Samoa', "code" => 'WS'],
    ["name" => 'San Marino', "code" => 'SM'],
    ["name" => 'Sao Tome and Principe', "code" => 'ST'],
    ["name" => 'Saudi Arabia', "code" => 'SA'],
    ["name" => 'Senegal', "code" => 'SN'],
    ["name" => 'Serbia and Montenegro', "code" => 'CS'],
    ["name" => 'Seychelles', "code" => 'SC'],
    ["name" => 'Sierra Leone', "code" => 'SL'],
    ["name" => 'Singapore', "code" => 'SG'],
    ["name" => 'Slovakia', "code" => 'SK'],
    ["name" => 'Slovenia', "code" => 'SI'],
    ["name" => 'Solomon Islands', "code" => 'SB'],
    ["name" => 'Somalia', "code" => 'SO'],
    ["name" => 'South Africa', "code" => 'ZA'],
    ["name" => 'South Georgia and the South Sandwich Islands', "code" => 'GS'],
    ["name" => 'Spain', "code" => 'ES'],
    ["name" => 'Sri Lanka', "code" => 'LK'],
    ["name" => 'Sudan', "code" => 'SD'],
    ["name" => 'Suriname', "code" => 'SR'],
    ["name" => 'Svalbard and Jan Mayen', "code" => 'SJ'],
    ["name" => 'Swaziland', "code" => 'SZ'],
    ["name" => 'Sweden', "code" => 'SE'],
    ["name" => 'Switzerland', "code" => 'CH'],
    ["name" => 'Syrian Arab Republic', "code" => 'SY'],
    ["name" => 'Taiwan, Province of China', "code" => 'TW'],
    ["name" => 'Tajikistan', "code" => 'TJ'],
    ["name" => 'Tanzania, United Republic of', "code" => 'TZ'],
    ["name" => 'Thailand', "code" => 'TH'],
    ["name" => 'Timor-Leste', "code" => 'TL'],
    ["name" => 'Togo', "code" => 'TG'],
    ["name" => 'Tokelau', "code" => 'TK'],
    ["name" => 'Tonga', "code" => 'TO'],
    ["name" => 'Trinidad and Tobago', "code" => 'TT'],
    ["name" => 'Tunisia', "code" => 'TN'],
    ["name" => 'Turkey', "code" => 'TR'],
    ["name" => 'Turkmenistan', "code" => 'TM'],
    ["name" => 'Turks and Caicos Islands', "code" => 'TC'],
    ["name" => 'Tuvalu', "code" => 'TV'],
    ["name" => 'Uganda', "code" => 'UG'],
    ["name" => 'Ukraine', "code" => 'UA'],
    ["name" => 'United Arab Emirates', "code" => 'AE'],
    ["name" => 'United Kingdom', "code" => 'GB'],
    ["name" => 'United States', "code" => 'US'],
    ["name" => 'United States Minor Outlying Islands', "code" => 'UM'],
    ["name" => 'Uruguay', "code" => 'UY'],
    ["name" => 'Uzbekistan', "code" => 'UZ'],
    ["name" => 'Vanuatu', "code" => 'VU'],
    ["name" => 'Venezuela', "code" => 'VE'],
    ["name" => 'Viet Nam', "code" => 'VN'],
    ["name" => 'Virgin Islands, British', "code" => 'VG'],
    ["name" => 'Virgin Islands, U.S.', "code" => 'VI'],
    ["name" => 'Wallis and Futuna', "code" => 'WF'],
    ["name" => 'Western Sahara', "code" => 'EH'],
    ["name" => 'Yemen', "code" => 'YE'],
    ["name" => 'Zambia', "code" => 'ZM'],
    ["name" => 'Zimbabwe', "code" => 'ZW']
];


const NOTIFICATION_FOR_RIDE_SHARE_CUSTOMER = [
    ['key' => 'trip_started', 'value' => 'Trip Started'],
    ['key' => 'trip_completed', 'value' => 'Trip Completed'],
    ['key' => 'trip_canceled', 'value' => 'Trip Canceled'],
    ['key' => 'trip_paused', 'value' => 'Trip Paused'],
    ['key' => 'trip_resumed', 'value' => 'Trip Resumed'],
    ['key' => 'another_driver_assigned', 'value' => 'Another Driver Assigned'],
    ['key' => 'driver_on_the_way', 'value' => 'Driver On The Way'],
    ['key' => 'bid_request_from_driver', 'value' => 'Bid Request From Driver'],
    ['key' => 'driver_canceled_ride_request', 'value' => 'Driver Canceled Ride Request'],
    ['key' => 'payment_successful', 'value' => 'Payment Successful'],
];

const NOTIFICATION_FOR_RIDE_SHARE_DRIVER = [
    ['key' => 'new_ride_request', 'value' => 'New Ride Request'],
    ['key' => 'bid_accepted', 'value' => 'Bid Accepted'],
    ['key' => 'trip_request_canceled', 'value' => 'Trip Request Canceled'],
    ['key' => 'customer_canceled_trip', 'value' => 'Customer Canceled Trip'],
    ['key' => 'bid_request_canceled_by_customer', 'value' => 'Bid Request Canceled By Customer'],
    ['key' => 'tips_from_customer', 'value' => 'Tips From Customer'],
    ['key' => 'received_new_bid', 'value' => 'Received New Bid'],
    ['key' => 'customer_rejected_bid', 'value' => 'Customer Rejected Bid'],
];

const NOTIFICATION_FOR_RIDE_SHARE_DRIVER_REGISTRATION = [
    ['key' => 'registration_approved', 'value' => 'Registration Approved'],
    ['key' => 'vehicle_request_approved', 'value' => 'Vehicle Request Approved'],
    ['key' => 'vehicle_request_denied', 'value' => 'Vehicle Request Denied'],
    ['key' => 'identity_image_rejected', 'value' => 'Identity Image Rejected'],
    ['key' => 'identity_image_approved', 'value' => 'Identity Image Approved'],
    ['key' => 'vehicle_active', 'value' => 'Vehicle Active'],
];


const NOTIFICATION_FOR_RIDE_SHARE_OTHERS = [
    ['key' => 'coupon_applied', 'value' => 'Coupon Applied'],
    ['key' => 'coupon_removed', 'value' => 'Coupon Removed'],
    ['key' => 'review_from_customer', 'value' => 'Review From Customer'],
    ['key' => 'review_from_driver', 'value' => 'Review From Driver'],
    ['key' => 'someone_used_your_code', 'value' => 'Someone Used Your Code'],
    ['key' => 'referral_reward_received', 'value' => 'Referral Reward Received'],
    ['key' => 'safety_alert_sent', 'value' => 'Safety Alert Sent'],
    ['key' => 'safety_problem_resolved', 'value' => 'Safety Problem Resolved'],
    ['key' => 'terms_and_conditions_updated', 'value' => 'Terms And Conditions Updated'],
    ['key' => 'privacy_policy_updated', 'value' => 'Privacy Policy Updated'],
    ['key' => 'legal_updated', 'value' => 'Legal Updated'],
    ['key' => 'new_message', 'value' => 'New Message'],
    ['key' => 'admin_message', 'value' => 'Admin Message'],
    ['key' => 'level_up', 'value' => 'Level Up'],
    ['key' => 'fund_added_by_admin', 'value' => 'Fund Added By Admin'],
    ['key' => 'admin_collected_cash', 'value' => 'Admin Collected Cash'],
    ['key' => 'withdraw_request_rejected', 'value' => 'Withdraw Request Rejected'],
    ['key' => 'withdraw_request_approved', 'value' => 'Withdraw Request Approved'],
//    ['key' => 'withdraw_request_settled', 'value' => 'Withdraw Request Settled'],
//    ['key' => 'withdraw_request_reversed', 'value' => 'Withdraw Request Reversed'],
];
