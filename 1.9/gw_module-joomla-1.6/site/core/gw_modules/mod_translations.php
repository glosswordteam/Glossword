<?php
/* The common functions for $target */
class gw_mod_translations extends site_prepend
{
	/* */
	public function autoexec()
	{
		if ( $this->gv['sef_output'] != 'js' && $this->gv['sef_output'] != 'css' && $this->gv['sef_output'] != 'ajax' )
		{
			$this->oTpl->assign( 'v:h_tabs', '<div class="gw-actions">'.implode(' ', $this->get_actions() ).'</div>' );
		}
	}
	/* */
	public function get_statuses()
	{
 		return array( 
			GW_STATUS_OFF => $this->oTkit->_( 1070 ), 
			GW_STATUS_ON => $this->oTkit->_( 1069 ), 
			GW_STATUS_REMOVE => $this->oTkit->_( 1073 ) 
		);
	}
	/* */
	public function get_statuses_classnames()
	{
 		return array( 
			GW_STATUS_OFF => 'state-warning', 
			GW_STATUS_ON => 'state-allow', 
			GW_STATUS_REMOVE => 'state-warning' 
		);
	}
	/* */
	public function count_pids_total()
	{
		$this->oDb->select( 'count(*) as cnt' );
		$this->oDb->from( array( 'pid p' ) );
		$ar_sql = $this->oDb->get()->result_array();
		return isset( $ar_sql[0]['cnt'] ) ? $ar_sql[0]['cnt'] : 0; 
	}
	/* */
	public function count_translated_total()
	{
		$this->oDb->select( 'count(*) cnt, tv.id_lang' );
		$this->oDb->from( array( 'tv tv', 'languages l' ) );
		$this->oDb->where( array( 'tv.is_complete' => '1' ) );
		$this->oDb->where( array( 'l.is_active' => '1' ) );
		$this->oDb->where( array( 'l.id_lang = tv.id_lang' => NULL ) );
		$this->oDb->group_by( 'tv.id_lang' );
		$ar_sql = $this->oDb->get()->result_array();
		$ar = array();
		foreach ( $ar_sql as $ar_v )
		{
			 $ar[$ar_v['id_lang']] = $ar_v['cnt'];
		}
		return $ar;
	}
	/* */
	public function get_actions()
	{
		$oHref = $this->oHtmlAdm->oHref();
		$oHref->set( 't', $this->gv['target'] );
		$ar_ctrl = array();
		if ( $this->oSess->is( 'sys-settings' ) )
		{
			$oHref->set( 'a', 'manage' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1006 ).': '.$this->oTkit->_( 1190 ) ),
				$this->oTkit->_( 1006 )
			);
			/* */
			$oHref->set( 't', 'langs' );
			$oHref->set( 'a', 'manage' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1006 ).': '.$this->oTkit->_( 1181 ) ),
				$this->oTkit->_( 1181 ) . $this->V->str_class_shortcut
			);
			$oHref->set( 't', 'tvs' );
			$oHref->set( 'a', 'manage' );
			$ar_ctrl[] = $this->oHtmlAdm->a_href(
				array( $this->V->file_index, '#area' => $oHref->get() ),
				array( 'title' => $this->oTkit->_( 1006 ) .': '.$this->oTkit->_( 1182 ) ),
				$this->oTkit->_( 1182 ) . $this->V->str_class_shortcut
			);
		}
		return $ar_ctrl;
	}
	
	/* */
	public static function get_regions()
	{
		$ar_reg['AF'] = 'Afghanistan (AF)';
		$ar_reg['AX'] = 'Åland Islands (AX)';
		$ar_reg['AL'] = 'Albania (AL)';
		$ar_reg['DZ'] = 'Algeria (DZ)';
		$ar_reg['AS'] = 'American Samoa (AS)';
		$ar_reg['AD'] = 'Andorra (AD)';
		$ar_reg['AO'] = 'Angola (AO)';
		$ar_reg['AI'] = 'Anguilla (AI)';
		$ar_reg['AQ'] = 'Antarctica (AQ)';
		$ar_reg['AG'] = 'Antigua and Barbuda (AG)';
		$ar_reg['AR'] = 'Argentina (AR)';
		$ar_reg['AM'] = 'Armenia (AM)';
		$ar_reg['AW'] = 'Aruba (AW)';
		$ar_reg['AC'] = 'Ascension Island (AC)';
		$ar_reg['AU'] = 'Australia (AU)';
		$ar_reg['AT'] = 'Austria (AT)';
		$ar_reg['AZ'] = 'Azerbaijan (AZ)';
		$ar_reg['BS'] = 'Bahamas (BS)';
		$ar_reg['BH'] = 'Bahrain (BH)';
		$ar_reg['BD'] = 'Bangladesh (BD)';
		$ar_reg['BB'] = 'Barbados (BB)';
		$ar_reg['BY'] = 'Belarus (BY)';
		$ar_reg['BE'] = 'Belgium (BE)';
		$ar_reg['BZ'] = 'Belize (BZ)';
		$ar_reg['BJ'] = 'Benin (BJ)';
		$ar_reg['BM'] = 'Bermuda (BM)';
		$ar_reg['BT'] = 'Bhutan (BT)';
		$ar_reg['BO'] = 'Bolivia (BO)';
		$ar_reg['BA'] = 'Bosnia and Herzegovina (BA)';
		$ar_reg['BW'] = 'Botswana (BW)';
		$ar_reg['BV'] = 'Bouvet Island (BV)';
		$ar_reg['BR'] = 'Brazil (BR)';
		$ar_reg['IO'] = 'British Indian Ocean Territory (IO)';
		$ar_reg['VG'] = 'British Virgin Islands (VG)';
		$ar_reg['BN'] = 'Brunei Darussalam (BN)';
		$ar_reg['BG'] = 'Bulgaria (BG)';
		$ar_reg['BF'] = 'Burkina Faso (BF)';
		$ar_reg['BI'] = 'Burundi (BI)';
		$ar_reg['KH'] = 'Cambodia (KH)';
		$ar_reg['CM'] = 'Cameroon (CM)';
		$ar_reg['CA'] = 'Canada (CA)';
		$ar_reg['IC'] = 'Canary Islands (IC)';
		$ar_reg['CV'] = 'Cape Verde (CV)';
		$ar_reg['KY'] = 'Cayman Islands (KY)';
		$ar_reg['CF'] = 'Central African Republic (CF)';
		$ar_reg['EA'] = 'Ceuta, Melilla (EA)';
		$ar_reg['TD'] = 'Chad (TD)';
		$ar_reg['CL'] = 'Chile (CL)';
		$ar_reg['CN'] = 'China (CN)';
		$ar_reg['CX'] = 'Christmas Island (CX)';
		$ar_reg['CP'] = 'Clipperton Island (CP)';
		$ar_reg['CC'] = 'Cocos (Keeling) Islands (CC)';
		$ar_reg['CO'] = 'Colombia (CO)';
		$ar_reg['KM'] = 'Comoros (KM)';
		$ar_reg['CG'] = 'Congo (CG)';
		$ar_reg['CK'] = 'Cook Islands (CK)';
		$ar_reg['CR'] = 'Costa Rica (CR)';
		$ar_reg['CI'] = 'Côte d\'Ivoire (CI)';
		$ar_reg['HR'] = 'Croatia (HR)';
		$ar_reg['CU'] = 'Cuba (CU)';
		$ar_reg['CY'] = 'Cyprus (CY)';
		$ar_reg['CZ'] = 'Czech Republic (CZ)';
		$ar_reg['KP'] = 'Democratic People\'s Republic of Korea (KP)';
		$ar_reg['DK'] = 'Denmark (DK)';
		$ar_reg['DG'] = 'Diego Garcia (DG)';
		$ar_reg['DJ'] = 'Djibouti (DJ)';
		$ar_reg['DM'] = 'Dominica (DM)';
		$ar_reg['DO'] = 'Dominican Republic (DO)';
		$ar_reg['EC'] = 'Ecuador (EC)';
		$ar_reg['EG'] = 'Egypt (EG)';
		$ar_reg['SV'] = 'El Salvador (SV)';
		$ar_reg['GQ'] = 'Equatorial Guinea (GQ)';
		$ar_reg['ER'] = 'Eritrea (ER)';
		$ar_reg['EE'] = 'Estonia (EE)';
		$ar_reg['ET'] = 'Ethiopia (ET)';
		$ar_reg['EU'] = 'European Union (EU)';
		$ar_reg['FK'] = 'Falkland Islands (Malvinas) (FK)';
		$ar_reg['FO'] = 'Faroe Islands (FO)';
		$ar_reg['FM'] = 'Federated States of Micronesia (FM)';
		$ar_reg['FJ'] = 'Fiji (FJ)';
		$ar_reg['FI'] = 'Finland (FI)';
		$ar_reg['FR'] = 'France (FR)';
		$ar_reg['GF'] = 'French Guiana (GF)';
		$ar_reg['PF'] = 'French Polynesia (PF)';
		$ar_reg['TF'] = 'French Southern Territories (TF)';
		$ar_reg['GA'] = 'Gabon (GA)';
		$ar_reg['GM'] = 'Gambia (GM)';
		$ar_reg['GE'] = 'Georgia (GE)';
		$ar_reg['DE'] = 'Germany (DE)';
		$ar_reg['GH'] = 'Ghana (GH)';
		$ar_reg['GI'] = 'Gibraltar (GI)';
		$ar_reg['GR'] = 'Greece (GR)';
		$ar_reg['GL'] = 'Greenland (GL)';
		$ar_reg['GD'] = 'Grenada (GD)';
		$ar_reg['GP'] = 'Guadeloupe (GP)';
		$ar_reg['GU'] = 'Guam (GU)';
		$ar_reg['GT'] = 'Guatemala (GT)';
		$ar_reg['GG'] = 'Guernsey (GG)';
		$ar_reg['GN'] = 'Guinea (GN)';
		$ar_reg['GW'] = 'Guinea-Bissau (GW)';
		$ar_reg['GY'] = 'Guyana (GY)';
		$ar_reg['HT'] = 'Haiti (HT)';
		$ar_reg['HM'] = 'Heard Island and McDonald Islands (HM)';
		$ar_reg['VA'] = 'Holy See (Vatican City State) (VA)';
		$ar_reg['HN'] = 'Honduras (HN)';
		$ar_reg['HK'] = 'Hong Kong (HK)';
		$ar_reg['HU'] = 'Hungary (HU)';
		$ar_reg['IS'] = 'Iceland (IS)';
		$ar_reg['IN'] = 'India (IN)';
		$ar_reg['ID'] = 'Indonesia (ID)';
		$ar_reg['IQ'] = 'Iraq (IQ)';
		$ar_reg['IE'] = 'Ireland (IE)';
		$ar_reg['IR'] = 'Islamic Republic of Iran (IR)';
		$ar_reg['IM'] = 'Isle of Man (IM)';
		$ar_reg['IL'] = 'Israel (IL)';
		$ar_reg['IT'] = 'Italy (IT)';
		$ar_reg['JM'] = 'Jamaica (JM)';
		$ar_reg['JP'] = 'Japan (JP)';
		$ar_reg['JE'] = 'Jersey (JE)';
		$ar_reg['JO'] = 'Jordan (JO)';
		$ar_reg['KZ'] = 'Kazakhstan (KZ)';
		$ar_reg['KE'] = 'Kenya (KE)';
		$ar_reg['KI'] = 'Kiribati (KI)';
		$ar_reg['KW'] = 'Kuwait (KW)';
		$ar_reg['KG'] = 'Kyrgyzstan (KG)';
		$ar_reg['LA'] = 'Lao People\'s Democratic Republic (LA)';
		$ar_reg['LV'] = 'Latvia (LV)';
		$ar_reg['LB'] = 'Lebanon (LB)';
		$ar_reg['LS'] = 'Lesotho (LS)';
		$ar_reg['LR'] = 'Liberia (LR)';
		$ar_reg['LY'] = 'Libyan Arab Jamahiriya (LY)';
		$ar_reg['LI'] = 'Liechtenstein (LI)';
		$ar_reg['LT'] = 'Lithuania (LT)';
		$ar_reg['LU'] = 'Luxembourg (LU)';
		$ar_reg['MO'] = 'Macao (MO)';
		$ar_reg['MG'] = 'Madagascar (MG)';
		$ar_reg['MW'] = 'Malawi (MW)';
		$ar_reg['MY'] = 'Malaysia (MY)';
		$ar_reg['MV'] = 'Maldives (MV)';
		$ar_reg['ML'] = 'Mali (ML)';
		$ar_reg['MT'] = 'Malta (MT)';
		$ar_reg['MH'] = 'Marshall Islands (MH)';
		$ar_reg['MQ'] = 'Martinique (MQ)';
		$ar_reg['MR'] = 'Mauritania (MR)';
		$ar_reg['MU'] = 'Mauritius (MU)';
		$ar_reg['YT'] = 'Mayotte (YT)';
		$ar_reg['MX'] = 'Mexico (MX)';
		$ar_reg['MD'] = 'Moldova (MD)';
		$ar_reg['MC'] = 'Monaco (MC)';
		$ar_reg['MN'] = 'Mongolia (MN)';
		$ar_reg['ME'] = 'Montenegro (ME)';
		$ar_reg['MS'] = 'Montserrat (MS)';
		$ar_reg['MA'] = 'Morocco (MA)';
		$ar_reg['MZ'] = 'Mozambique (MZ)';
		$ar_reg['MM'] = 'Myanmar (MM)';
		$ar_reg['NA'] = 'Namibia (NA)';
		$ar_reg['NR'] = 'Nauru (NR)';
		$ar_reg['NP'] = 'Nepal (NP)';
		$ar_reg['AN'] = 'Netherlands Antilles (AN)';
		$ar_reg['NL'] = 'Netherlands (NL)';
		$ar_reg['NC'] = 'New Caledonia (NC)';
		$ar_reg['NZ'] = 'New Zealand (NZ)';
		$ar_reg['NI'] = 'Nicaragua (NI)';
		$ar_reg['NE'] = 'Niger (NE)';
		$ar_reg['NG'] = 'Nigeria (NG)';
		$ar_reg['NU'] = 'Niue (NU)';
		$ar_reg['NF'] = 'Norfolk Island (NF)';
		$ar_reg['MP'] = 'Northern Mariana Islands (MP)';
		$ar_reg['NO'] = 'Norway (NO)';
		$ar_reg['PS'] = 'Occupied Palestinian Territory (PS)';
		$ar_reg['OM'] = 'Oman (OM)';
		$ar_reg['PK'] = 'Pakistan (PK)';
		$ar_reg['PW'] = 'Palau (PW)';
		$ar_reg['PA'] = 'Panama (PA)';
		$ar_reg['PG'] = 'Papua New Guinea (PG)';
		$ar_reg['PY'] = 'Paraguay (PY)';
		$ar_reg['PE'] = 'Peru (PE)';
		$ar_reg['PH'] = 'Philippines (PH)';
		$ar_reg['PN'] = 'Pitcairn (PN)';
		$ar_reg['PL'] = 'Poland (PL)';
		$ar_reg['PT'] = 'Portugal (PT)';
		$ar_reg['PR'] = 'Puerto Rico (PR)';
		$ar_reg['QA'] = 'Qatar (QA)';
		$ar_reg['KR'] = 'Republic of Korea (KR)';
		$ar_reg['RE'] = 'Réunion (RE)';
		$ar_reg['RO'] = 'Romania (RO)';
		$ar_reg['RU'] = 'Russian Federation (RU)';
		$ar_reg['RW'] = 'Rwanda (RW)';
		$ar_reg['BL'] = 'Saint Barthélemy (BL)';
		$ar_reg['SH'] = 'Saint Helena (SH)';
		$ar_reg['KN'] = 'Saint Kitts and Nevis (KN)';
		$ar_reg['LC'] = 'Saint Lucia (LC)';
		$ar_reg['MF'] = 'Saint Martin (MF)';
		$ar_reg['PM'] = 'Saint Pierre and Miquelon (PM)';
		$ar_reg['VC'] = 'Saint Vincent and the Grenadines (VC)';
		$ar_reg['WS'] = 'Samoa (WS)';
		$ar_reg['SM'] = 'San Marino (SM)';
		$ar_reg['ST'] = 'Sao Tome and Principe (ST)';
		$ar_reg['SA'] = 'Saudi Arabia (SA)';
		$ar_reg['SN'] = 'Senegal (SN)';
		$ar_reg['RS'] = 'Serbia (RS)';
		$ar_reg['SC'] = 'Seychelles (SC)';
		$ar_reg['SL'] = 'Sierra Leone (SL)';
		$ar_reg['SG'] = 'Singapore (SG)';
		$ar_reg['SK'] = 'Slovakia (SK)';
		$ar_reg['SI'] = 'Slovenia (SI)';
		$ar_reg['SB'] = 'Solomon Islands (SB)';
		$ar_reg['SO'] = 'Somalia (SO)';
		$ar_reg['ZA'] = 'South Africa (ZA)';
		$ar_reg['GS'] = 'South Georgia and the South Sandwich Islands (GS)';
		$ar_reg['ES'] = 'Spain (ES)';
		$ar_reg['LK'] = 'Sri Lanka (LK)';
		$ar_reg['SD'] = 'Sudan (SD)';
		$ar_reg['SR'] = 'Suriname (SR)';
		$ar_reg['SJ'] = 'Svalbard and Jan Mayen (SJ)';
		$ar_reg['SZ'] = 'Swaziland (SZ)';
		$ar_reg['SE'] = 'Sweden (SE)';
		$ar_reg['CH'] = 'Switzerland (CH)';
		$ar_reg['SY'] = 'Syrian Arab Republic (SY)';
		$ar_reg['TW'] = 'Taiwan, Province of China (TW)';
		$ar_reg['TJ'] = 'Tajikistan (TJ)';
		$ar_reg['TH'] = 'Thailand (TH)';
		$ar_reg['CD'] = 'The Democratic Republic of the Congo (CD)';
		$ar_reg['MK'] = 'The Former Yugoslav Republic of Macedonia (MK)';
		$ar_reg['TL'] = 'Timor-Leste (TL)';
		$ar_reg['TG'] = 'Togo (TG)';
		$ar_reg['TK'] = 'Tokelau (TK)';
		$ar_reg['TO'] = 'Tonga (TO)';
		$ar_reg['TT'] = 'Trinidad and Tobago (TT)';
		$ar_reg['TA'] = 'Tristan da Cunha (TA)';
		$ar_reg['TN'] = 'Tunisia (TN)';
		$ar_reg['TR'] = 'Turkey (TR)';
		$ar_reg['TM'] = 'Turkmenistan (TM)';
		$ar_reg['TC'] = 'Turks and Caicos Islands (TC)';
		$ar_reg['TV'] = 'Tuvalu (TV)';
		$ar_reg['VI'] = 'U.S. Virgin Islands (VI)';
		$ar_reg['UG'] = 'Uganda (UG)';
		$ar_reg['UA'] = 'Ukraine (UA)';
		$ar_reg['AE'] = 'United Arab Emirates (AE)';
		$ar_reg['GB'] = 'United Kingdom (GB)';
		$ar_reg['TZ'] = 'United Republic of Tanzania (TZ)';
		$ar_reg['UM'] = 'United States Minor Outlying Islands (UM)';
		$ar_reg['US'] = 'United States (US)';
		$ar_reg['UY'] = 'Uruguay (UY)';
		$ar_reg['UZ'] = 'Uzbekistan (UZ)';
		$ar_reg['VU'] = 'Vanuatu (VU)';
		$ar_reg['VE'] = 'Venezuela (VE)';
		$ar_reg['VN'] = 'Viet Nam (VN)';
		$ar_reg['WF'] = 'Wallis and Futuna (WF)';
		$ar_reg['EH'] = 'Western Sahara (EH)';
		$ar_reg['YE'] = 'Yemen (YE)';
		$ar_reg['ZM'] = 'Zambia (ZM)';
		$ar_reg['ZW'] = 'Zimbabwe (ZW)';

		$ar_reg['ZZ'] = 'Private use (ZZ)';

		$ar_reg['2'] = 'Africa (2)';
		$ar_reg['19'] = 'Americas (19)';
		$ar_reg['142'] = 'Asia (142)';
		$ar_reg['53'] = 'Australia and New Zealand (53)';
		$ar_reg['29'] = 'Caribbean (29)';
		$ar_reg['13'] = 'Central America (13)';
		$ar_reg['143'] = 'Central Asia (143)';
		$ar_reg['14'] = 'Eastern Africa (14)';
		$ar_reg['30'] = 'Eastern Asia (30)';
		$ar_reg['151'] = 'Eastern Europe (151)';
		$ar_reg['150'] = 'Europe (150)';
		$ar_reg['419'] = 'Latin America and the Caribbean (419)';
		$ar_reg['54'] = 'Melanesia (54)';
		$ar_reg['57'] = 'Micronesia (57)';
		$ar_reg['17'] = 'Middle Africa (17)';
		$ar_reg['15'] = 'Northern Africa (15)';
		$ar_reg['21'] = 'Northern America (21)';
		$ar_reg['154'] = 'Northern Europe (154)';
		$ar_reg['9'] = 'Oceania (9)';
		$ar_reg['61'] = 'Polynesia (61)';
		$ar_reg['5'] = 'South America (5)';
		$ar_reg['35'] = 'South-Eastern Asia (35)';
		$ar_reg['18'] = 'Southern Africa (18)';
		$ar_reg['34'] = 'Southern Asia (34)';
		$ar_reg['39'] = 'Southern Europe (39)';
		$ar_reg['11'] = 'Western Africa (11)';
		$ar_reg['145'] = 'Western Asia (145)';
		$ar_reg['155'] = 'Western Europe (155)';
		$ar_reg['1'] = 'World (1)';
		return $ar_reg;
	}
}

?>