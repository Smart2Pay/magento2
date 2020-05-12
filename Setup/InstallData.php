<?php

namespace Smart2Pay\GlobalPay\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $insert_data = [

        ['AD', 'Andorra'],
        ['AE', 'United Arab Emirates'],
        ['AF', 'Afghanistan'],
        ['AG', 'Antigua and Barbuda'],
        ['AI', 'Anguilla'],
        ['AL', 'Albania'],
        ['AM', 'Armenia'],
        ['AN', 'Netherlands Antilles'],
        ['AO', 'Angola'],
        ['AQ', 'Antarctica'],
        ['AR', 'Argentina'],
        ['AS', 'American Samoa'],
        ['AT', 'Austria'],
        ['AU', 'Australia'],
        ['AW', 'Aruba'],
        ['AZ', 'Azerbaijan'],
        ['BA', 'Bosnia & Herzegowina'],
        ['BB', 'Barbados'],
        ['BD', 'Bangladesh'],
        ['BE', 'Belgium'],
        ['BF', 'Burkina Faso'],
        ['BG', 'Bulgaria'],
        ['BH', 'Bahrain'],
        ['BI', 'Burundi'],
        ['BJ', 'Benin'],
        ['BM', 'Bermuda'],
        ['BN', 'Brunei Darussalam'],
        ['BO', 'Bolivia'],
        ['BR', 'Brazil'],
        ['BS', 'Bahamas'],
        ['BT', 'Bhutan'],
        ['BV', 'Bouvet Island'],
        ['BW', 'Botswana'],
        ['BY', 'Belarus (formerly known as Byelorussia)'],
        ['BZ', 'Belize'],
        ['CA', 'Canada'],
        ['CC', 'Cocos (Keeling) Islands'],
        ['CD', 'Congo, Democratic Republic of the (formerly Zalre)'],
        ['CF', 'Central African Republic'],
        ['CG', 'Congo'],
        ['CH', 'Switzerland'],
        ['CI', 'Ivory Coast (Cote d\'Ivoire)'],
        ['CK', 'Cook Islands'],
        ['CL', 'Chile'],
        ['CM', 'Cameroon'],
        ['CN', 'China'],
        ['CO', 'Colombia'],
        ['CR', 'Costa Rica'],
        ['CU', 'Cuba'],
        ['CV', 'Cape Verde'],
        ['CX', 'Christmas Island'],
        ['CY', 'Cyprus'],
        ['CZ', 'Czech Republic'],
        ['DE', 'Germany'],
        ['DJ', 'Djibouti'],
        ['DK', 'Denmark'],
        ['DM', 'Dominica'],
        ['DO', 'Dominican Republic'],
        ['DZ', 'Algeria'],
        ['EC', 'Ecuador'],
        ['EE', 'Estonia'],
        ['EG', 'Egypt'],
        ['EH', 'Western Sahara'],
        ['ER', 'Eritrea'],
        ['ES', 'Spain'],
        ['ET', 'Ethiopia'],
        ['FI', 'Finland'],
        ['FJ', 'Fiji Islands'],
        ['FK', 'Falkland Islands (Malvinas)'],
        ['FM', 'Micronesia, Federated States of'],
        ['FO', 'Faroe Islands'],
        ['FR', 'France'],
        ['FX', 'France, Metropolitan'],
        ['GA', 'Gabon'],
        ['GB', 'United Kingdom'],
        ['GD', 'Grenada'],
        ['GE', 'Georgia'],
        ['GF', 'French Guiana'],
        ['GH', 'Ghana'],
        ['GI', 'Gibraltar'],
        ['GL', 'Greenland'],
        ['GM', 'Gambia'],
        ['GN', 'Guinea'],
        ['GP', 'Guadeloupe'],
        ['GQ', 'Equatorial Guinea'],
        ['GR', 'Greece'],
        ['GS', 'South Georgia and the South Sandwich Islands'],
        ['GT', 'Guatemala'],
        ['GU', 'Guam'],
        ['GW', 'Guinea-Bissau'],
        ['GY', 'Guyana'],
        ['HK', 'Hong Kong'],
        ['HM', 'Heard and McDonald Islands'],
        ['HN', 'Honduras'],
        ['HR', 'Croatia (local name: Hrvatska)'],
        ['HT', 'Haiti'],
        ['HU', 'Hungary'],
        ['ID', 'Indonesia'],
        ['IE', 'Ireland'],
        ['IL', 'Israel'],
        ['IN', 'India'],
        ['IO', 'British Indian Ocean Territory'],
        ['IQ', 'Iraq'],
        ['IR', 'Iran, Islamic Republic of'],
        ['IS', 'Iceland'],
        ['IT', 'Italy'],
        ['JM', 'Jamaica'],
        ['JO', 'Jordan'],
        ['JP', 'Japan'],
        ['KE', 'Kenya'],
        ['KG', 'Kyrgyzstan'],
        ['KH', 'Cambodia (formerly Kampuchea)'],
        ['KI', 'Kiribati'],
        ['KM', 'Comoros'],
        ['KN', 'Saint Kitts (Christopher) and Nevis'],
        ['KP', 'Korea, Democratic People\'s Republic of (North Korea)'],
        ['KR', 'Korea, Republic of (South Korea)'],
        ['KW', 'Kuwait'],
        ['KY', 'Cayman Islands'],
        ['KZ', 'Kazakhstan'],
        ['LA', 'Lao People\'s Democratic Republic (formerly Laos)'],
        ['LB', 'Lebanon'],
        ['LC', 'Saint Lucia'],
        ['LI', 'Liechtenstein'],
        ['LK', 'Sri Lanka'],
        ['LR', 'Liberia'],
        ['LS', 'Lesotho'],
        ['LT', 'Lithuania'],
        ['LU', 'Luxembourg'],
        ['LV', 'Latvia'],
        ['LY', 'Libyan Arab Jamahiriya'],
        ['MA', 'Morocco'],
        ['MC', 'Monaco'],
        ['MD', 'Moldova, Republic of'],
        ['MG', 'Madagascar'],
        ['MH', 'Marshall Islands'],
        ['MK', 'Macedonia, the Former Yugoslav Republic of'],
        ['ML', 'Mali'],
        ['MM', 'Myanmar (formerly Burma)'],
        ['MN', 'Mongolia'],
        ['MO', 'Macao (also spelled Macau)'],
        ['MP', 'Northern Mariana Islands'],
        ['MQ', 'Martinique'],
        ['MR', 'Mauritania'],
        ['MS', 'Montserrat'],
        ['MT', 'Malta'],
        ['MU', 'Mauritius'],
        ['MV', 'Maldives'],
        ['MW', 'Malawi'],
        ['MX', 'Mexico'],
        ['MY', 'Malaysia'],
        ['MZ', 'Mozambique'],
        ['NA', 'Namibia'],
        ['NC', 'New Caledonia'],
        ['NE', 'Niger'],
        ['NF', 'Norfolk Island'],
        ['NG', 'Nigeria'],
        ['NI', 'Nicaragua'],
        ['NL', 'Netherlands'],
        ['NO', 'Norway'],
        ['NP', 'Nepal'],
        ['NR', 'Nauru'],
        ['NU', 'Niue'],
        ['NZ', 'New Zealand'],
        ['OM', 'Oman'],
        ['PA', 'Panama'],
        ['PE', 'Peru'],
        ['PF', 'French Polynesia'],
        ['PG', 'Papua New Guinea'],
        ['PH', 'Philippines'],
        ['PK', 'Pakistan'],
        ['PL', 'Poland'],
        ['PM', 'St Pierre and Miquelon'],
        ['PN', 'Pitcairn Island'],
        ['PR', 'Puerto Rico'],
        ['PT', 'Portugal'],
        ['PW', 'Palau'],
        ['PY', 'Paraguay'],
        ['QA', 'Qatar'],
        ['RE', 'Réunion'],
        ['RO', 'Romania'],
        ['RU', 'Russian Federation'],
        ['RW', 'Rwanda'],
        ['SA', 'Saudi Arabia'],
        ['SB', 'Solomon Islands'],
        ['SC', 'Seychelles'],
        ['SD', 'Sudan'],
        ['SE', 'Sweden'],
        ['SG', 'Singapore'],
        ['SH', 'St Helena'],
        ['SI', 'Slovenia'],
        ['SJ', 'Svalbard and Jan Mayen Islands'],
        ['SK', 'Slovakia'],
        ['SL', 'Sierra Leone'],
        ['SM', 'San Marino'],
        ['SN', 'Senegal'],
        ['SO', 'Somalia'],
        ['SR', 'Suriname'],
        ['ST', 'Sco Tom'],
        ['SU', 'Union of Soviet Socialist Republics'],
        ['SV', 'El Salvador'],
        ['SY', 'Syrian Arab Republic'],
        ['SZ', 'Swaziland'],
        ['TC', 'Turks and Caicos Islands'],
        ['TD', 'Chad'],
        ['TF', 'French Southern and Antarctic Territories'],
        ['TG', 'Togo'],
        ['TH', 'Thailand'],
        ['TJ', 'Tajikistan'],
        ['TK', 'Tokelau'],
        ['TM', 'Turkmenistan'],
        ['TN', 'Tunisia'],
        ['TO', 'Tonga'],
        ['TP', 'East Timor'],
        ['TR', 'Turkey'],
        ['TT', 'Trinidad and Tobago'],
        ['TV', 'Tuvalu'],
        ['TW', 'Taiwan, Province of China'],
        ['TZ', 'Tanzania, United Republic of'],
        ['UA', 'Ukraine'],
        ['UG', 'Uganda'],
        ['UM', 'United States Minor Outlying Islands'],
        ['US', 'United States of America'],
        ['UY', 'Uruguay'],
        ['UZ', 'Uzbekistan'],
        ['VA', 'Holy See (Vatican City State)'],
        ['VC', 'Saint Vincent and the Grenadines'],
        ['VE', 'Venezuela'],
        ['VG', 'Virgin Islands (British)'],
        ['VI', 'Virgin Islands (US)'],
        ['VN', 'Viet Nam'],
        ['VU', 'Vanautu'],
        ['WF', 'Wallis and Futuna Islands'],
        ['WS', 'Samoa'],
        ['XO', 'West Africa'],
        ['YE', 'Yemen'],
        ['YT', 'Mayotte'],
        ['ZA', 'South Africa'],
        ['ZM', 'Zambia'],
        ['ZW', 'Zimbabwe'],
        ['PS', 'Palestinian Territory'],
        ['RS', 'Serbia'],
        ['ME', 'Montenegro'],

        ];

        $installer->getConnection()->insertArray(
            $installer->getTable('s2p_gp_countries'),
            ['code','name'],
            $insert_data
        );

        // /**
        //  * Install order statuses from config
        //  */
        // $data = [];
        // $statuses = [
        //     Smart2Pay::STATUS_NEW => __('Smart2Pay New Order'),
        //     Smart2Pay::STATUS_SUCCESS => __('Smart2Pay Success'),
        //     Smart2Pay::STATUS_CANCELED => __('Smart2Pay Canceled'),
        //     Smart2Pay::STATUS_FAILED => __('Smart2Pay Failed'),
        //     Smart2Pay::STATUS_EXPIRED => __('Smart2Pay Expired'),
        // ];
        //
        // foreach ($statuses as $code => $info) {
        //     $data[] = ['status' => $code, 'label' => $info];
        // }
        //
        // $installer->getConnection()->insertArray(
        //     $installer->getTable('sales_order_status'),
        //     ['status', 'label'],
        //     $data
        // );
        //
        //
        // /**
        //  * Install order states from config
        //  */
        // $data = [];
        // $states = [
        //     'new' => [
        //         'statuses' => [ Smart2Pay::STATUS_NEW ],
        //     ],
        //     'processing' => [
        //         'statuses' => [ Smart2Pay::STATUS_SUCCESS ],
        //     ],
        //     'canceled' => [
        //         'statuses' => [ Smart2Pay::STATUS_CANCELED, Smart2Pay::STATUS_FAILED, Smart2Pay::STATUS_EXPIRED ],
        //     ],
        // ];
        //
        // foreach( $states as $code => $info )
        // {
        //     if( !isset( $info['statuses'] ) or !is_array( $info['statuses'] ) )
        //         continue;
        //
        //     foreach( $info['statuses'] as $status )
        //     {
        //         $data[] = [
        //             'status' => $status,
        //             'state' => $code,
        //             'is_default' => 0,
        //             'visible_on_front' => 1,
        //         ];
        //     }
        // }
        //
        // $installer->getConnection()->insertArray(
        //     $installer->getTable('sales_order_status_state'),
        //     ['status', 'state', 'is_default', 'visible_on_front'],
        //     $data
        // );

        $installer->endSetup();
    }
}
