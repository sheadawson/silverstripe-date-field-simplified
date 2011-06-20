<?php

/**
 * @author nicolaas [at] sunnysideup.co.nz
 * 
 * 
 */


class SimpleDateField extends TextField {

	/**
	 * @var array
	 */
	protected static $config = array(
		'showcalendar' => true,
		'monthbeforeday' => false,
		'righttitle' => "",
		'dateformat' => "l j F Y", //see PHP Date function
		'datavalueformat' => 'Y-m-d', //PHP data value format for saving into DB
		'minDate' => "", //you can enter "today" or "next week" or any strtotime argument!
		'maxDate' => "", //you can enter "today" or "next week" or any strtotime argument!
		'locale' => "" //alternative locale for JS Calendar
	);
		static function set_config($a) {self::$config = $a;}
		static function add_config($key, $value) {self::$config[$key] = $value;}
		//static function remove_config($key) {unset(self::$config[$key]);}
		static function get_config() {return self::$config;}
		static function get_config_item($name) {return self::$config[$name];}
		

	function __construct($name, $title = null, $value = null, $form = null, $rightTitle = '', $config = array()) {
		parent::__construct($name, $title, $value, $form);
		if($config && count($config)) {
			foreach($config as $name => $value) {
				self::$config[$name] = $value;
			}
		}
		$rightTitle = $this->getConfig("righttitle");
		if(!$rightTitle) {
			$rightTitle = "  ";
		}
		$this->setRightTitle($rightTitle);
	}

	function Field() {

		//GENERAL
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript("datefield_simplified/javascript/SimpleDateField.js");
		$this->addExtraClass("simpledatefield");

		//CALENDAR STUFF
		if($this->getConfig('showcalendar')) {
			
			Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery.ui.all.css');
			Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-ui/jquery.ui.core.js');
			Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-ui/jquery.ui.datepicker.js');
			
			// Include language files (if required)
			$myLang = $this->getConfig("locale");
			$lang = i18n::get_locale();
			$shortLang = substr($lang, 0,2);
			$altLang = str_replace('_', '-', $lang);
			// TODO Check for existence of locale to avoid unnecessary 404s from the CDN
			$fileNameArray = array(
				$myLang => sprintf('jquery.ui.datepicker-%s.js',$myLang),
				$lang => sprintf('jquery.ui.datepicker-%s.js',$lang),
				$shortLang => sprintf('jquery.ui.datepicker-%s.js',$shortLang),
				$altLang => sprintf('jquery.ui.datepicker-%s.js',$altLang)
			);
			foreach($fileNameArray as $key => $fileName) {
				$fileName =   THIRDPARTY_DIR . '/jquery-ui/i18n/' . $fileName;
				if(file_exists(Director::baseFolder() . "/" . $fileName)) {
					Requirements::javascript($fileName);
					self::add_config("locale", $key);
					break;
				}
			}
		}

		//CUSTOM STUFF
		$fieldID = $this->id();
		//TO DO:::::: THIS DOES NOT APPEAR TO BE PROPER JSON !
		
		$settingsArray = array();
		$jsConfigArray = self::convert_array_between_string_and_proper_value(self::get_config(), false);
		foreach($jsConfigArray as $key => $value) {
			if(is_string($value)) {
				$value = "'".Convert::raw2js($value)."'";
			}
			$settingsArray []= "$key : ". $value;
		}
		$settings = "{".implode(",",$settingsArray)."}";
		$url = Convert::raw2js(SimpleDateField_Controller::get_url()."/ajaxvalidation/");
		$jsFuncField =<<<JS
simpledatefield_$fieldID = $settings;
jQuery('#$fieldID').live(
	"change",
	function() {
		var id = jQuery(this).attr("id");
		var value_value = jQuery(this).val();
		url_value = '$url';
		SimpleDateFieldAjaxValidation.sendInput(id, value_value, url_value, simpledatefield_$fieldID);
	}
);
JS;
		if($this->getConfig('showcalendar')) {
			$jsSettingsArray = Array();
			if($this->getConfig('monthbeforeday')) {
				$jsFormat = 'mm/dd/yy';
				$phpFormat = 'm/d/Y';
			}
			else {
				$jsFormat = 'dd-mm-yy';
				$phpFormat = 'd-m-Y';
			}
			$jsSettingsArray[] = " dateFormat: '$jsFormat'";
			if($maxDate = self::get_config_item('maxDate')) {
				$maxDate = strtotime($maxDate);
				if($maxDate) {
					$jsSettingsArray[] = " maxDate: '".date($phpFormat, $maxDate)."'";
				}
			}
			if($minDate = self::get_config_item('minDate')) {
				$minDate = strtotime($minDate);
				if($minDate) {
					$jsSettingsArray[] = " minDate: '".date($phpFormat, $minDate)."'";
				}
			}
			$jsSettingsString = "{".implode(",", $jsSettingsArray)."}";
			$jsFuncField .=<<<JS
jQuery("#$fieldID").live(
	"click",
	function() {
		if(simpledatefield_$fieldID.locale && jQuery.datepicker.regional[simpledatefield_$fieldID.locale]) {
			config = jQuery.extend(simpledatefield_$fieldID, jQuery.datepicker.regional[simpledatefield_$fieldID.locale], {});
		}
		jQuery('#$fieldID').datepicker($jsSettingsString);
		jQuery('#$fieldID').datepicker('show');
	}
);
JS;
		}
		Requirements :: customScript($jsFuncField, 'func_SimpleDateField'.$fieldID);		
		$html = parent::Field();
		return $html;
	}

	/**
	 * @return String ISO 8601 date, suitable for insertion into database
	 */
	function dataValue() {
		if($this->value) {
			$ts = self::convert_to_ts_or_error($this->value);
			if(is_numeric($ts)) {
				return date($this->getConfig('datavalueformat'), $ts);
			}
		}
		return null;
	}

	/**
	 * @return Boolean
	 */
	function validate($validator) {
		$valid = true;
		// Don't validate empty fields
		if(empty($this->value)) return true;
		// date format
		// min/max - Assumes that the date value was valid in the first place
		$tsOrError = self::convert_to_ts_or_error($this->value);
		if(is_numeric($tsOrError)) {
			return true;
		}
		else {
			$validator->validationError(
				$this->name,
				$tsOrError,
				"validation",
				false
			);
			return false;
		}
	}

	/**
	 * @param string $name
	 * @param mixed $val
	 */
	function setConfig($name, $val) {
		self::$config[$name] = $val;
	}

	/**
	 * @param String $name
	 * @return mixed
	 */
	function getConfig($name) {
		return self::$config[$name];
	}

	public static function convert_to_ts_or_error($rawInput) {
		//return strtotime("24-06-2012");
		$tsOrError = null;
		if(self::get_config_item('monthbeforeday')) {
			$cleanedInput = str_replace("-", "/", $rawInput);
		}
		else {
			$cleanedInput = str_replace("/", "-", $rawInput);
		}
		if($cleanedInput) {
			$tsOrError = intval(strtotime($cleanedInput));
		}
		if(is_numeric($tsOrError) && $tsOrError > 0) {
			if($minDate = self::get_config_item('minDate')) {
				$minDate = strtotime($minDate);
				if($minDate) {
					if($minDate > $tsOrError) {
						$tsOrError = sprintf(_t('SimpleDateField.VALIDDATEMINDATE', "Your date can not be before %s."),date(self::get_config_item('dateformat'), $minDate));
					}
				}
			}
			if($maxDate = self::get_config_item('maxDate')) {
				// ISO or strtotime()
				$maxDate = strtotime($maxDate);
				if($maxDate) {
					if($maxDate < $tsOrError) {
						$tsOrError = sprintf(_t('SimpleDateField.VALIDDATEMAXDATE', "Your date can not be after %s."),date(self::get_config_item('dateformat'), $maxDate));
					}
				}
			}
		}
		if(!$tsOrError) {
			if(!trim($rawInput)) {
				$tsOrError = sprintf(_t('SimpleDateField.VALIDDATEDATE', "You need to enter a valid date."),$rawInput);
			}
			else {
				$tsOrError = sprintf(_t('SimpleDateField.VALIDDATEDATE', "We did not understand the date you entered '%s'."),$rawInput);
			}
		}
		return $tsOrError;
	}

	public static function convert_to_fancy_date($rawInput) {
		$tsOrError = self::convert_to_ts_or_error($rawInput);
		if(is_numeric($tsOrError) && $tsOrError) {
			return Convert::raw2json(
				array(
					"value" => date(self::get_config_item("dateformat"), $tsOrError ),
					"success" => 1
				)
			);
		}
		else {
			return Convert::raw2json(
				array(
					"value" =>  $tsOrError,
					"success" => 0
				)
			);
		}
	}

	public function convert_array_between_string_and_proper_value($input, $fromString = true) {
		$array = array(
			"false" => false,
			"true" => true,
			"null" => null
		);
		$output = array();
		if($input && count($input)) {
			foreach($input as $key => $value) {
				if(is_array($value)) {
					$value = SimpleDateField::convert_array_between_string_and_proper_value($value, $fromString);
				}
				else {
					if($fromString) {
						if(isset($array[$value])) {
							$value = $array[$value];
						}
					}
					else {
						$newValue = array_search($value, $array, true);
						if($newValue) {
							$value = $newValue;
						}
					}
				}
				$output[$key] = $value;
			}
		}
		return $output;

	}
}


class SimpleDateField_Controller extends Controller {

	protected static $url = 'formfields-simpledatefield';
		static function set_url($s) {self::$url = $s;}
		static function get_url() {return self::$url;}

	function ajaxvalidation($request) {
		$rawInput = '';
		if(isset($_GET["value"])) {
			$rawInput = ($_GET["value"]);
		}
		if(isset($_GET["settings"])) {
			SimpleDateField::set_config(SimpleDateField::convert_array_between_string_and_proper_value($_GET["settings"], true));
		}		
		return SimpleDateField::convert_to_fancy_date($rawInput);
	}


}


class SimpleDateField_Editable extends EditableFormField {

	static $db = array(
		"ShowCalendar" => "Boolean",
		"OnlyPastDates" => "Boolean",
		"OnlyFutureDates" => "Boolean",
		"MonthBeforeDay" => "Boolean",
		"ExplanationForEnteringDates" => "Varchar(120)"
	);

	static $singular_name = 'Simple ]Date Field';

	static $plural_name = 'Simple Date Fields';

	public function Icon() {
		return 'userforms/images/editabledatefield.png';
	}

	
	function getFieldConfiguration() {
		$fields = parent::getFieldConfiguration();
		// eventually replace hard-coded "Fields"?
		$baseName = "Fields[$this->ID]";
		$ShowCalendar = ($this->getSetting('ShowCalendar')) ? $this->getSetting('ShowCalendar') : '0';
		$OnlyPastDates = ($this->getSetting('OnlyPastDates')) ? $this->getSetting('OnlyPastDates') : '0';
		$OnlyFutureDates = ($this->getSetting('OnlyFutureDates')) ? $this->getSetting('OnlyFutureDates') : '0';
		$MonthBeforeDay = ($this->getSetting('MonthBeforeDay')) ? $this->getSetting('MonthBeforeDay') : '0';
		$ExplanationForEnteringDates = ($this->getSetting('ExplanationForEnteringDates')) ? $this->getSetting('ExplanationForEnteringDates') : '';
		$extraFields = new FieldSet(
			new FieldGroup(
				_t('SimpleDateField_Editable.DATESETTINGS', 'Date Settings'),
				new CheckboxField($baseName . "[CustomSettings][ShowCalendar]", "Show Calendar", $ShowCalendar),
				new CheckboxField($baseName . "[CustomSettings][OnlyPastDates]", "Only Past Dates?", $OnlyPastDates),
				new CheckboxField($baseName . "[CustomSettings][OnlyFutureDates]", "Only Future Dates?", $OnlyFutureDates),
				new CheckboxField($baseName . "[CustomSettings][MonthBeforeDay]", "Month before day (e.g. Jan 11 2011)?", $MonthBeforeDay),
				new TextField($baseName . "[CustomSettings][ExplanationForEnteringDates]", "Explanation for entering dates", $ExplanationForEnteringDates)
			)
		);
		
		$fields->merge($extraFields);
		return $fields;		
	}

	public function getFormField() {
		$field = new SimpleDateField($this->Name, $this->Title);
		if($this->getSetting('ShowCalendar')) {
			$field->setConfig("showcalendar", true);
		}
		if($this->getSetting('OnlyPastDates')) {
			$field->setConfig("maxDate", "today");
		}
		elseif($this->getSetting('OnlyFutureDates')) {
			$field->setConfig("minDate", "today");
		}
		if($this->getSetting('MonthBeforeDay')) {
			$field->setConfig("dateformat", 'l F j Y');
			$field->setConfig("monthbeforeday", true);
		}
		if($this->getSetting('ExplanationForEnteringDates')) {
			$field->setRightTitle($this->getSetting('ExplanationForEnteringDates'));
		}
		return $field;
	}
}
