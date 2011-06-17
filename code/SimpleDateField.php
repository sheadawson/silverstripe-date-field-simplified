<?php




class SimpleDateField extends TextField {

	/**
	 * @var array
	 */
	protected static $config = array(
		'monthbeforeday' => true,
		'righttitle' => "you can use 'tomorrow', 'next sunday' or '30-12-2012'",
		'showcalendar' => false,
		'dateformat' => "l j F Y", //see PHP Date function
		'datavalueformat' => 'Y-m-d', //PHP data value format for saving into DB
		'min' => null, //you can enter "today" or "next week" or any strtotime argument!
		'max' => null,//you can enter "today" or "next week" or any strtotime argument!
	);
		static function set_config($a) {self::$config = $a;}
		//static function add_config($key, $value) {self::$config[$key] = $value;}
		//static function remove_config($key) {unset(self::$config[$key]);}
		static function get_config() {return self::$config;}
		static function get_config_item($name) {return self::$config[$name];}
		

	function __construct($name, $title = null, $value = null, $form = null, $rightTitle = null) {
		parent::__construct($name, $title, $value, $form);
		if(!$this->getConfig("showcalendar")) {
			if(!$rightTitle) {
				$rightTitle = $this->getConfig("righttitle");
			}
		}
		$this->setRightTitle($rightTitle);
	}

	function Field() {
		$settings = addslashes(serialize(self::get_config()));
		$formID = $this->form->FormName();
		$fieldID = $this->id();
		$url = SimpleDateField_Controller::get_url();
		$this->addExtraClass("simpledatefield");
		$jsFuncField =<<<JS
	jQuery('#$fieldID').change(
		function() {
			var id = jQuery(this).attr("id");
			var value_value = escape(jQuery(this).val());
			url_value = "\/$url\/ajaxvalidation\/";
			settings_value = escape('$settings');
			getSimpleDateValue(id, value_value, url_value, settings_value);
		}
	);
JS;
		$jsFuncGeneral =<<<JS
function SimpleDateFieldAjaxValidation(id, value_value, url_value, settings_value) {
	jQuery.ajax(
		{
			url: url_value,
			data: ({value: value_value,settings: settings_value}),
			success: function(returnData) {
				array = returnData.split("|");
				if(!array[1] || array[1] == "0") {
					jQuery("#" + id).attr("value","?");
					jQuery("label[for='"+id+"'].right").text(array[0]);
				}
				else {
					jQuery("label[for='"+id+"'].right").text(" ");
					jQuery("#" + id).attr("value",array[0]);
				}
				
			}
		}
	);
}
JS;

		Requirements :: customScript($jsFuncGeneral, 'func_SimpleDateField');		
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


	static function convert_to_ts_or_error($rawInput) {
		$tsOrError = null;
		if(self::get_config_item('monthbeforeday')) {
			$cleanedInput = str_replace("-", "/", $rawInput);
		}
		else {
			$cleanedInput = str_replace("/", "-", $rawInput);
		}
		// min/max - Assumes that the date value was valid in the first place
		if($cleanedInput) {
			$tsOrError = intval(strtotime($cleanedInput));
		}
		if(is_numeric($tsOrError)) {
			if($min = self::get_config_item('min')) {
				$minDate = strtotime($min);
				if($minDate > $tsOrError) {
					$tsOrError = sprintf(_t('SimpleDateField.VALIDDATEMINDATE', "Your date can not be before %s."),date(self::get_config_item('dateformat'), $minDate));
				}
			}
			if($max = self::get_config_item('max')) {
				// ISO or strtotime()
				$maxDate = strtotime($max);
				if($maxDate < $tsOrError) {
					$tsOrError = sprintf(_t('DateField.VALIDDATEmaxDATE', "Your date can not be after %s."),date(self::get_config_item('dateformat'), $maxDate));
				}
			}
		}
		if(!$tsOrError) {
			if(!trim($rawInput)) {
				$tsOrError = sprintf(_t('DateField.VALIDDATEmaxDATE', "You need to enter a valid date."),$rawInput);
			}
			else {
				$tsOrError = sprintf(_t('DateField.VALIDDATEmaxDATE', "We did not understand the date you entered '%s'."),$rawInput);
			}
		}
		return $tsOrError;
	}

	static function convert_to_fancy_date($rawInput) {
		$tsOrError = self::convert_to_ts_or_error($rawInput);
		if(!is_numeric($tsOrError)) {
			return "$tsOrError|0";
		}
		return date(self::get_config_item("dateformat"), $tsOrError )."|1";
	}

}


class SimpleDateField_Controller extends Controller {

	protected static $url = 'formfields-simpledatefield';
		static function set_url($s) {self::$url = $s;}
		static function get_url() {return self::$url;}

	function ajaxvalidation($request) {
		$rawInput = '';
		if(isset($_GET["value"])) {
			$rawInput = $_GET["value"];
		}
		if(isset($_GET["settings"])) {
			SimpleDateField::set_config(unserialize($_GET["settings"]));
		}		
		return SimpleDateField::convert_to_fancy_date($rawInput);
	}

}


class SimpleDateField_Editable extends EditableFormField {

	static $db = array(
		"OnlyPastDates" => "Boolean",
		"OnlyFutureDates" => "Boolean",
		"MonthBeforeDay" => "Boolean",
		"ExplanationForEnteringDates" => "Varchar(120)"
	);

	static $singular_name = 'Date Field';

	static $plural_name = 'Date Fields';

	public function Icon() {
		return 'userforms/images/editabledatefield.png';
	}

	
	function getFieldConfiguration() {
		$fields = parent::getFieldConfiguration();
		// eventually replace hard-coded "Fields"?
		$baseName = "Fields[$this->ID]";
		$OnlyPastDates = ($this->getSetting('OnlyPastDates')) ? $this->getSetting('OnlyPastDates') : '0';
		$OnlyFutureDates = ($this->getSetting('OnlyFutureDates')) ? $this->getSetting('OnlyFutureDates') : '0';
		$MonthBeforeDay = ($this->getSetting('MonthBeforeDay')) ? $this->getSetting('MonthBeforeDay') : '0';
		$ExplanationForEnteringDates = ($this->getSetting('ExplanationForEnteringDates')) ? $this->getSetting('ExplanationForEnteringDates') : '';
		$extraFields = new FieldSet(
			new FieldGroup(
				_t('SimpleDateField_Editable.DATESETTINGS', 'Date Settings'),
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
		if($this->getSetting('OnlyPastDates')) {
			$field->setConfig("max", "today");
		}
		elseif($this->getSetting('OnlyFutureDates')) {
			$field->setConfig("min", "today");
		}
		if($this->getSetting('MonthBeforeDay')) {
			$field->setConfig("monthbeforeday", true);
		}
		if($this->getSetting('ExplanationForEnteringDates')) {
			$field->setRightTitle($this->getSetting('ExplanationForEnteringDates'));
		}
		return $field;
	}
}
