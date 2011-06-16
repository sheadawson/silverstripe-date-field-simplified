<?php




class SimpleDateField extends TextField {

	/**
	 * @var array
	 */
	protected $config = array(
		'showcalendar' => false,
		'monthbeforeday' => false,
		'dateformat' => "d-m-Y",
		'datavalueformat' => 'Y-m-d', //PHP data value format for saving into DB
		'min' => null, //you can enter "today" or "next week" or any strtotime argument!
		'max' => null,//you can enter "today" or "next week" or any strtotime argument!
	);

	/**
	 *
	 *@var Object - Date DB  Field objects
	 **/
	protected $valueObj = null;

	function __construct($name, $title = null, $value = null, $form = null, $rightTitle = null) {
		parent::__construct($name, $title, $value, $form, $rightTitle);
	}

	function FieldHolder() {
		// TODO Replace with properly extensible view helper system
		$d = Object::create('DateField_View_JQuery', $this);
		$d->onBeforeRender();
		$html = parent::FieldHolder();
		$html = $d->onAfterRender($html);
		return $html;
	}

	function Field() {
		$html = parent::Field();
		return $html;
	}

	/**
	 * Sets the internal value to ISO date format.
	 *
	 * @param String|Array $val
	 */
	function setValue($val) {
		if(empty($val)) {
			$this->value = null;
			$this->valueObj = null;
		}
		else {
		}
	}

	/**
	 * @return String ISO 8601 date, suitable for insertion into database
	 */
	function dataValue() {
		if($this->valueObj) {
			return $this->valueObj->toString($this->getConfig('datavalueformat'));
		}
		else {
			return null;
		}
	}

	function jsValidation() {

		$formID = $this->form->FormName();

		if(Validator::get_javascript_validator_handler() == 'none') return true;

			$error = _t('DateField.VALIDATIONJS', 'Please enter a valid date format (e.g. 12 June 2012).');
			$jsFunc =<<<JS

if(1 == 2) {
	validationError(el,"$error","validation",false);
	return false;
}
return true;
JS;
			Requirements :: customScript($jsFunc, 'func_validateSimpleDate_'.$formID);

			return <<<JS
if(\$('$formID')){
	if(typeof fromAnOnBlur != 'undefined'){
		if(fromAnOnBlur.name == '$this->name')
			\$('$formID').validateDate('$this->name');
	}
	else{
		\$('$formID').validateDate('$this->name');
	}
	jQuery('#$formID .simpleDateField').change(
		function() {
			var url = "/formfields/simpledatefielf/"+escape(jQuery(this).val())+"/";
			jQuery.get(
				url,
				function(returnData) {
					alert(returnData);
				}
			);
		}
	);
}
JS;
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
		if($min = $this->getConfig('min')) {
			$minDate = strtotime($min);
			if($minDate > $newDate) {
				$validator->validationError(
					$this->name,
					sprintf(
						_t('DateField.VALIDDATEMINDATE', "Your date can not be before %s."),
						date($minDate, $this->getConfig('dateformat'))
					),
					"validation",
					false
				);
				return false;
			}
		}
		if($max = $this->getConfig('max')) {
			// ISO or strtotime()
			$maxDate = strtotime($max);
			if($maxDate < $newDate) {
				$validator->validationError(
					$this->name,
					sprintf(
						_t('DateField.VALIDDATEmaxDATE', "Your date can not be after %s."),
						date($maxDate, $this->getConfig('dateformat'))
					),
					"validation",
					false
				);
				return false;
			}
		}
		return true;
	}

	/**
	 * @param string $name
	 * @param mixed $val
	 */
	function setConfig($name, $val) {
		$this->config[$name] = $val;
	}

	/**
	 * @param String $name
	 * @return mixed
	 */
	function getConfig($name) {
		return $this->config[$name];
	}

}


class SimpleDateField_Controller extends Controller {

	function ajaxvalidation() {
		$value = $this->request->param("Value");
		return self::convert_to_fancy_date($value)."|".self::convert_to_db_date($value);
	}

}

class SimpleDateField_Validation extends ValidationResult {

}