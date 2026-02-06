<?php

namespace KaliForms\Inc\Backend\PredefinedOptions;

if (!defined('ABSPATH')) {
    exit;
}

class Country_Options
{
    /**
     * Options array
     *
     * @var array
     */
    public $options = [];
    /**
     * Does it have sub categories
     *
     * @var boolean
     */
    public $subcategories = true;
    /**
     * Predefined option label
     *
     * @var string
     */
    public $label = '';
    /**
     * Preset id
     *
     * @var string
     */
    public $id = 'countries';
    public $countries = [];
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->set_all_countries();
        $this->set_options();
    }
    /**
     * All countries with code and contintent
     *
     * @return void
     */
    public function set_all_countries()
    {
        $antarticaCountries = [
            'AQ' => esc_html__('Antarctica (the territory South of 60 deg S)', 'kali-forms'),
            'BV' => esc_html__('Bouvet Island (Bouvetoya)', 'kali-forms'),
            'GS' => esc_html__('South Georgia and the South Sandwich Islands', 'kali-forms'),
            'TF' => esc_html__('French Southern Territories', 'kali-forms'),
            'HM' => esc_html__('Heard Island and McDonald Islands', 'kali-forms'),
        ];
        $africaCountries = [
            'DZ' => esc_html__('Algeria', 'kali-forms'),
            'AO' => esc_html__('Angola, Republic of', 'kali-forms'),
            'BW' => esc_html__('Botswana, Republic of', 'kali-forms'),
            'BI' => esc_html__('Burundi, Republic of', 'kali-forms'),
            'CM' => esc_html__('Cameroon, Republic of', 'kali-forms'),
            'CV' => esc_html__('Cape Verde, Republic of', 'kali-forms'),
            'CF' => esc_html__('Central African Republic', 'kali-forms'),
            'TD' => esc_html__('Chad, Republic of', 'kali-forms'),
            'KM' => esc_html__('Comoros, Union of the', 'kali-forms'),
            'YT' => esc_html__('Mayotte', 'kali-forms'),
            'CG' => esc_html__('Congo, Republic of the', 'kali-forms'),
            'CD' => esc_html__('Congo, Democratic Republic of the', 'kali-forms'),
            'BJ' => esc_html__('Benin, Republic of', 'kali-forms'),
            'GQ' => esc_html__('Equatorial Guinea, Republic of', 'kali-forms'),
            'ET' => esc_html__('Ethiopia, Federal Democratic Republic of', 'kali-forms'),
            'ER' => esc_html__('Eritrea, State of', 'kali-forms'),
            'DJ' => esc_html__('Djibouti, Republic of', 'kali-forms'),
            'GA' => esc_html__('Gabon, Gabonese Republic', 'kali-forms'),
            'GM' => esc_html__('Gambia, Republic of the', 'kali-forms'),
            'GH' => esc_html__('Ghana, Republic of', 'kali-forms'),
            'GN' => esc_html__('Guinea, Republic of', 'kali-forms'),
            'CI' => esc_html__('Ivory Coast, Republic of', 'kali-forms'),
            'KE' => esc_html__('Kenya, Republic of', 'kali-forms'),
            'LS' => esc_html__('Lesotho, Kingdom of', 'kali-forms'),
            'LR' => esc_html__('Liberia, Republic of', 'kali-forms'),
            'LY' => esc_html__('Libyan Arab Jamahiriya', 'kali-forms'),
            'MG' => esc_html__('Madagascar, Republic of', 'kali-forms'),
            'MW' => esc_html__('Malawi, Republic of', 'kali-forms'),
            'ML' => esc_html__('Mali, Republic of', 'kali-forms'),
            'MR' => esc_html__('Mauritania, Islamic Republic of', 'kali-forms'),
            'MU' => esc_html__('Mauritius, Republic of', 'kali-forms'),
            'MA' => esc_html__('Morocco, Kingdom of', 'kali-forms'),
            'MZ' => esc_html__('Mozambique, Republic of', 'kali-forms'),
            'NA' => esc_html__('Namibia, Republic of', 'kali-forms'),
            'NE' => esc_html__('Niger, Republic of', 'kali-forms'),
            'NG' => esc_html__('Nigeria, Federal Republic of', 'kali-forms'),
            'GW' => esc_html__('Guinea-Bissau, Republic of', 'kali-forms'),
            'RE' => esc_html__('Reunion', 'kali-forms'),
            'RW' => esc_html__('Rwanda, Republic of', 'kali-forms'),
            'SH' => esc_html__('Saint Helena', 'kali-forms'),
            'ST' => esc_html__('Sao Tome and Principe, Democratic Republic of', 'kali-forms'),
            'SN' => esc_html__('Senegal, Republic of', 'kali-forms'),
            'SC' => esc_html__('Seychelles, Republic of', 'kali-forms'),
            'SL' => esc_html__('Sierra Leone, Republic of', 'kali-forms'),
            'SO' => esc_html__('Somalia, Somali Republic', 'kali-forms'),
            'ZA' => esc_html__('South Africa, Republic of', 'kali-forms'),
            'ZW' => esc_html__('Zimbabwe, Republic of', 'kali-forms'),
            'SS' => esc_html__('South Sudan', 'kali-forms'),
            'EH' => esc_html__('Western Sahara', 'kali-forms'),
            'SD' => esc_html__('Sudan, Republic of', 'kali-forms'),
            'SZ' => esc_html__('Swaziland, Kingdom of', 'kali-forms'),
            'TG' => esc_html__('Togo, Togolese Republic', 'kali-forms'),
            'TN' => esc_html__('Tunisia, Tunisian Republic', 'kali-forms'),
            'UG' => esc_html__('Uganda, Republic of', 'kali-forms'),
            'EG' => esc_html__('Egypt, Arab Republic of', 'kali-forms'),
            'TZ' => esc_html__('Tanzania, United Republic of', 'kali-forms'),
            'BF' => esc_html__('Burkina Faso', 'kali-forms'),
            'ZM' => esc_html__('Zambia, Republic of', 'kali-forms'),
        ];
        $asiaCountries = [
            'AF' => esc_html__('Afghanistan, Islamic Republic of', 'kali-forms'),
            'AZ' => esc_html__('Azerbaijan, Republic of', 'kali-forms'),
            'BH' => esc_html__('Bahrain, Kingdom of', 'kali-forms'),
            'BD' => esc_html__('Bangladesh, People Republic of', 'kali-forms'),
            'AM' => esc_html__('Armenia, Republic of', 'kali-forms'),
            'BT' => esc_html__('Bhutan, Kingdom of', 'kali-forms'),
            'IO' => esc_html__('British Indian Ocean Territory (Chagos Archipelago)', 'kali-forms'),
            'BN' => esc_html__('Brunei Darussalam', 'kali-forms'),
            'MM' => esc_html__('Myanmar, Union of', 'kali-forms'),
            'KH' => esc_html__('Cambodia, Kingdom of', 'kali-forms'),
            'LK' => esc_html__('Sri Lanka, Democratic Socialist Republic of', 'kali-forms'),
            'CN' => esc_html__('China, People Republic of', 'kali-forms'),
            'TW' => esc_html__('Taiwan', 'kali-forms'),
            'CX' => esc_html__('Christmas Island', 'kali-forms'),
            'CC' => esc_html__('Cocos (Keeling) Islands', 'kali-forms'),
            'CY' => esc_html__('Cyprus, Republic of', 'kali-forms'),
            'GE' => esc_html__('Georgia', 'kali-forms'),
            'PS' => esc_html__('Palestinian Territory, Occupied', 'kali-forms'),
            'HK' => esc_html__('Hong Kong, Special Administrative Region of China', 'kali-forms'),
            'IN' => esc_html__('India, Republic of', 'kali-forms'),
            'ID' => esc_html__('Indonesia, Republic of', 'kali-forms'),
            'IR' => esc_html__('Iran, Islamic Republic of', 'kali-forms'),
            'IQ' => esc_html__('Iraq, Republic of', 'kali-forms'),
            'IL' => esc_html__('Israel, State of', 'kali-forms'),
            'JP' => esc_html__('Japan', 'kali-forms'),
            'KZ' => esc_html__('Kazakhstan, Republic of', 'kali-forms'),
            'JO' => esc_html__('Jordan, Hashemite Kingdom of', 'kali-forms'),
            'KP' => esc_html__('Korea, Democratic Peoples Republic of', 'kali-forms'),
            'KR' => esc_html__('Korea, Republic of', 'kali-forms'),
            'KW' => esc_html__('Kuwait, State of', 'kali-forms'),
            'KG' => esc_html__('Kyrgyz Republic', 'kali-forms'),
            'LA' => esc_html__('Lao, People Democratic Republic', 'kali-forms'),
            'LB' => esc_html__('Lebanon, Lebanese Republic', 'kali-forms'),
            'MO' => esc_html__('Macao, Special Administrative Region of China', 'kali-forms'),
            'MY' => esc_html__('Malaysia', 'kali-forms'),
            'MV' => esc_html__('Maldives, Republic of', 'kali-forms'),
            'MN' => esc_html__('Mongolia', 'kali-forms'),
            'OM' => esc_html__('Oman, Sultanate of', 'kali-forms'),
            'NP' => esc_html__('Nepal, State of', 'kali-forms'),
            'PK' => esc_html__('Pakistan, Islamic Republic of', 'kali-forms'),
            'PH' => esc_html__('Philippines, Republic of the', 'kali-forms'),
            'TL' => esc_html__('Timor-Leste, Democratic Republic of', 'kali-forms'),
            'QA' => esc_html__('Qatar, State of', 'kali-forms'),
            'RU' => esc_html__('Russian Federation', 'kali-forms'),
            'SA' => esc_html__('Saudi Arabia, Kingdom of', 'kali-forms'),
            'SG' => esc_html__('Singapore, Republic of', 'kali-forms'),
            'VN' => esc_html__('Vietnam, Socialist Republic of', 'kali-forms'),
            'SY' => esc_html__('Syrian Arab Republic', 'kali-forms'),
            'TJ' => esc_html__('Tajikistan, Republic of', 'kali-forms'),
            'TH' => esc_html__('Thailand, Kingdom of', 'kali-forms'),
            'AE' => esc_html__('United Arab Emirates', 'kali-forms'),
            'TR' => esc_html__('Turkey, Republic of', 'kali-forms'),
            'TM' => esc_html__('Turkmenistan', 'kali-forms'),
            'UZ' => esc_html__('Uzbekistan, Republic of', 'kali-forms'),
            'YE' => esc_html__('Yemen', 'kali-forms'),
            'XE' => esc_html__('Iraq-Saudi Arabia Neutral Zone', 'kali-forms'),
            'XD' => esc_html__('United Nations Neutral Zone', 'kali-forms'),
            'XS' => esc_html__('Spratly Islands', 'kali-forms'),
        ];
        $europeCountries = [
            'AL' => esc_html__('Albania, Republic of', 'kali-forms'),
            'AD' => esc_html__('Andorra, Principality of', 'kali-forms'),
            'AZ' => esc_html__('Azerbaijan, Republic of', 'kali-forms'),
            'AT' => esc_html__('Austria, Republic of', 'kali-forms'),
            'AM' => esc_html__('Armenia, Republic of', 'kali-forms'),
            'BE' => esc_html__('Belgium, Kingdom of', 'kali-forms'),
            'BA' => esc_html__('Bosnia and Herzegovina', 'kali-forms'),
            'BG' => esc_html__('Bulgaria, Republic of', 'kali-forms'),
            'BY' => esc_html__('Belarus, Republic of', 'kali-forms'),
            'HR' => esc_html__('Croatia, Republic of', 'kali-forms'),
            'CY' => esc_html__('Cyprus, Republic of', 'kali-forms'),
            'CZ' => esc_html__('Czech Republic', 'kali-forms'),
            'DK' => esc_html__('Denmark, Kingdom of', 'kali-forms'),
            'EE' => esc_html__('Estonia, Republic of', 'kali-forms'),
            'FO' => esc_html__('Faroe Islands', 'kali-forms'),
            'FI' => esc_html__('Finland, Republic of', 'kali-forms'),
            'AX' => esc_html__('Åland Islands', 'kali-forms'),
            'FR' => esc_html__('France, French Republic', 'kali-forms'),
            'GE' => esc_html__('Georgia', 'kali-forms'),
            'DE' => esc_html__('Germany, Federal Republic of', 'kali-forms'),
            'GI' => esc_html__('Gibraltar', 'kali-forms'),
            'GR' => esc_html__('Greece, Hellenic Republic', 'kali-forms'),
            'VA' => esc_html__('Holy See (Vatican City State)', 'kali-forms'),
            'HU' => esc_html__('Hungary, Republic of', 'kali-forms'),
            'IS' => esc_html__('Iceland, Republic of', 'kali-forms'),
            'IE' => esc_html__('Ireland', 'kali-forms'),
            'IT' => esc_html__('Italy, Italian Republic', 'kali-forms'),
            'KZ' => esc_html__('Kazakhstan, Republic of', 'kali-forms'),
            'LV' => esc_html__('Latvia, Republic of', 'kali-forms'),
            'LI' => esc_html__('Liechtenstein, Principality of', 'kali-forms'),
            'LT' => esc_html__('Lithuania, Republic of', 'kali-forms'),
            'LU' => esc_html__('Luxembourg, Grand Duchy of', 'kali-forms'),
            'MT' => esc_html__('Malta, Republic of', 'kali-forms'),
            'MC' => esc_html__('Monaco, Principality of', 'kali-forms'),
            'MD' => esc_html__('Moldova, Republic of', 'kali-forms'),
            'ME' => esc_html__('Montenegro, Republic of', 'kali-forms'),
            'NL' => esc_html__('Netherlands, Kingdom of the', 'kali-forms'),
            'NO' => esc_html__('Norway, Kingdom of', 'kali-forms'),
            'PL' => esc_html__('Poland, Republic of', 'kali-forms'),
            'PT' => esc_html__('Portugal, Portuguese Republic', 'kali-forms'),
            'RO' => esc_html__('Romania', 'kali-forms'),
            'RU' => esc_html__('Russian Federation', 'kali-forms'),
            'SM' => esc_html__('San Marino, Republic of', 'kali-forms'),
            'RS' => esc_html__('Serbia, Republic of', 'kali-forms'),
            'SK' => esc_html__('Slovakia (Slovak Republic)', 'kali-forms'),
            'SI' => esc_html__('Slovenia, Republic of', 'kali-forms'),
            'ES' => esc_html__('Spain, Kingdom of', 'kali-forms'),
            'SJ' => esc_html__('Svalbard & Jan Mayen Islands', 'kali-forms'),
            'SE' => esc_html__('Sweden, Kingdom of', 'kali-forms'),
            'CH' => esc_html__('Switzerland, Swiss Confederation', 'kali-forms'),
            'TR' => esc_html__('Turkey, Republic of', 'kali-forms'),
            'UA' => esc_html__('Ukraine', 'kali-forms'),
            'MK' => esc_html__('Macedonia, The Former Yugoslav Republic of', 'kali-forms'),
            'GB' => esc_html__('United Kingdom of Great Britain & Northern Ireland', 'kali-forms'),
            'GG' => esc_html__('Guernsey, Bailiwick of', 'kali-forms'),
            'JE' => esc_html__('Jersey, Bailiwick of', 'kali-forms'),
            'IM' => esc_html__('Isle of Man', 'kali-forms'),
        ];
        $naCountries = [
            'AG' => esc_html__('Antigua and Barbuda', 'kali-forms'),
            'BS' => esc_html__('Bahamas, Commonwealth of the', 'kali-forms'),
            'BB' => esc_html__('Barbados', 'kali-forms'),
            'BM' => esc_html__('Bermuda', 'kali-forms'),
            'BZ' => esc_html__('Belize', 'kali-forms'),
            'VG' => esc_html__('British Virgin Islands', 'kali-forms'),
            'CA' => esc_html__('Canada', 'kali-forms'),
            'KY' => esc_html__('Cayman Islands', 'kali-forms'),
            'CR' => esc_html__('Costa Rica, Republic of', 'kali-forms'),
            'CU' => esc_html__('Cuba, Republic of', 'kali-forms'),
            'DM' => esc_html__('Dominica, Commonwealth of', 'kali-forms'),
            'DO' => esc_html__('Dominican Republic', 'kali-forms'),
            'SV' => esc_html__('El Salvador, Republic of', 'kali-forms'),
            'GL' => esc_html__('Greenland', 'kali-forms'),
            'GD' => esc_html__('Grenada', 'kali-forms'),
            'GP' => esc_html__('Guadeloupe', 'kali-forms'),
            'GT' => esc_html__('Guatemala, Republic of', 'kali-forms'),
            'HT' => esc_html__('Haiti, Republic of', 'kali-forms'),
            'HN' => esc_html__('Honduras, Republic of', 'kali-forms'),
            'JM' => esc_html__('Jamaica', 'kali-forms'),
            'MQ' => esc_html__('Martinique', 'kali-forms'),
            'MX' => esc_html__('Mexico, United Mexican States', 'kali-forms'),
            'MS' => esc_html__('Montserrat', 'kali-forms'),
            'AN' => esc_html__('Netherlands Antilles', 'kali-forms'),
            'CW' => esc_html__('Curaçao', 'kali-forms'),
            'AW' => esc_html__('Aruba', 'kali-forms'),
            'SX' => esc_html__('Sint Maarten (Netherlands)', 'kali-forms'),
            'BQ' => esc_html__('Bonaire, Sint Eustatius and Saba', 'kali-forms'),
            'NI' => esc_html__('Nicaragua, Republic of', 'kali-forms'),
            'UM' => esc_html__('United States Minor Outlying Islands', 'kali-forms'),
            'PA' => esc_html__('Panama, Republic of', 'kali-forms'),
            'PR' => esc_html__('Puerto Rico, Commonwealth of', 'kali-forms'),
            'BL' => esc_html__('Saint Barthelemy', 'kali-forms'),
            'KN' => esc_html__('Saint Kitts and Nevis, Federation of', 'kali-forms'),
            'AI' => esc_html__('Anguilla', 'kali-forms'),
            'LC' => esc_html__('Saint Lucia', 'kali-forms'),
            'MF' => esc_html__('Saint Martin', 'kali-forms'),
            'PM' => esc_html__('Saint Pierre and Miquelon', 'kali-forms'),
            'VC' => esc_html__('Saint Vincent and the Grenadines', 'kali-forms'),
            'TT' => esc_html__('Trinidad and Tobago, Republic of', 'kali-forms'),
            'TC' => esc_html__('Turks and Caicos Islands', 'kali-forms'),
            'US' => esc_html__('United States of America', 'kali-forms'),
            'VI' => esc_html__('United States Virgin Islands', 'kali-forms'),
        ];
        $saCountries = [
            'AR' => esc_html__('Argentina, Argentine Republic', 'kali-forms'),
            'BO' => esc_html__('Bolivia, Republic of', 'kali-forms'),
            'BR' => esc_html__('Brazil, Federative Republic of', 'kali-forms'),
            'CL' => esc_html__('Chile, Republic of', 'kali-forms'),
            'CO' => esc_html__('Colombia, Republic of', 'kali-forms'),
            'EC' => esc_html__('Ecuador, Republic of', 'kali-forms'),
            'FK' => esc_html__('Falkland Islands (Malvinas)', 'kali-forms'),
            'GF' => esc_html__('French Guiana', 'kali-forms'),
            'GY' => esc_html__('Guyana, Co-operative Republic of', 'kali-forms'),
            'PY' => esc_html__('Paraguay, Republic of', 'kali-forms'),
            'PE' => esc_html__('Peru, Republic of', 'kali-forms'),
            'SR' => esc_html__('Suriname, Republic of', 'kali-forms'),
            'UY' => esc_html__('Uruguay, Eastern Republic of', 'kali-forms'),
            'VE' => esc_html__('Venezuela, Bolivarian Republic of', 'kali-forms'),
        ];
        $ocCountries = [
            'AS' => esc_html__('American Samoa', 'kali-forms'),
            'AU' => esc_html__('Australia, Commonwealth of', 'kali-forms'),
            'SB' => esc_html__('Solomon Islands', 'kali-forms'),
            'CK' => esc_html__('Cook Islands', 'kali-forms'),
            'FJ' => esc_html__('Fiji, Republic of the Fiji Islands', 'kali-forms'),
            'PF' => esc_html__('French Polynesia', 'kali-forms'),
            'KI' => esc_html__('Kiribati, Republic of', 'kali-forms'),
            'GU' => esc_html__('Guam', 'kali-forms'),
            'NR' => esc_html__('Nauru, Republic of', 'kali-forms'),
            'NC' => esc_html__('New Caledonia', 'kali-forms'),
            'VU' => esc_html__('Vanuatu, Republic of', 'kali-forms'),
            'NZ' => esc_html__('New Zealand', 'kali-forms'),
            'NU' => esc_html__('Niue', 'kali-forms'),
            'NF' => esc_html__('Norfolk Island', 'kali-forms'),
            'MP' => esc_html__('Northern Mariana Islands, Commonwealth of the', 'kali-forms'),
            'UM' => esc_html__('United States Minor Outlying Islands', 'kali-forms'),
            'FM' => esc_html__('Micronesia, Federated States of', 'kali-forms'),
            'MH' => esc_html__('Marshall Islands, Republic of the', 'kali-forms'),
            'PW' => esc_html__('Palau, Republic of', 'kali-forms'),
            'PG' => esc_html__('Papua New Guinea, Independent State of', 'kali-forms'),
            'PN' => esc_html__('Pitcairn Islands', 'kali-forms'),
            'TK' => esc_html__('Tokelau', 'kali-forms'),
            'TO' => esc_html__('Tonga, Kingdom of', 'kali-forms'),
            'TV' => esc_html__('Tuvalu', 'kali-forms'),
            'WF' => esc_html__('Wallis and Futuna', 'kali-forms'),
            'WS' => esc_html__('Samoa, Independent State of', 'kali-forms'),
            'XX' => esc_html__('Disputed Territory', 'kali-forms'),
        ];
        $allCountries = array_merge(
            $africaCountries,
            $antarticaCountries,
            $asiaCountries,
            $europeCountries,
            $naCountries,
            $saCountries,
            $ocCountries
        );
        ksort($allCountries);
        $this->countries = [
            'all' => [
                'label' => esc_html__('All Countries', 'kali-forms'),
                'code' => 'all',
                'countries' => $allCountries,
            ],
            'africa' => [
                'label' => esc_html__('Africa', 'kali-forms'),
                'code' => 'AF',
                'countries' => $africaCountries,
            ],
            'antarctica' => [
                'label' => esc_html__('Antarctica', 'kali-forms'),
                'code' => 'AN',
                'countries' => $antarticaCountries,
            ],
            'asia' => [
                'label' => esc_html__('Asia', 'kali-forms'),
                'code' => 'AS',
                'countries' => $asiaCountries,
            ],
            'europe' => [
                'label' => esc_html__('Europe', 'kali-forms'),
                'code' => 'EU',
                'countries' => $europeCountries,
            ],
            'northAmerica' => [
                'label' => esc_html__('North America', 'kali-forms'),
                'code' => 'NA',
                'countries' => $naCountries,
            ],
            'southAmerica' => [
                'label' => esc_html__('South America', 'kali-forms'),
                'code' => 'SA',
                'countries' => $saCountries,
            ],
            'oceania' => [
                'label' => esc_html__('Oceania', 'kali-forms'),
                'code' => 'OC',
                'countries' => $ocCountries,
            ],
        ];
    }
    /**
     * Set options
     *
     * @return void
     */
    public function set_options()
    {
        $this->label = esc_html__('Countries', 'kali-forms');
        foreach ($this->countries as $continent) {
            $this->options[] = [
                'id' => $continent['code'],
                'label' => $continent['label'],
                'options' => $continent['countries'],
            ];
        }
    }
}
