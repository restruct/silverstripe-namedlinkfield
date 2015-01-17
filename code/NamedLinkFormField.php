<?php
/**
 * Description of LinkFormField
 *
 * @author Simon
 */
class NamedLinkFormField extends FormField {
	
	public static $module_dir = ''; // This is initially set in _config.php
	
	private static $url_handlers = array(
		'$Action!/$ID' => '$Action'
	);

	private static $allowed_actions = array(
		'tree'
	);
	
	/**
	 * @var FormField
	 */
	protected $fieldPageID = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldCustomURL = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldTitle = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldLinkmode = null;
	
	public function __construct($name, $title = null, $value = null, $form = null) {
		
		// naming with underscores to prevent values from actually being saved somewhere
		$this->fieldCustomURL = new TextField("{$name}[CustomURL]", '', '', 300, $form);
		$this->fieldPageID = new TreeDropdownField("{$name}[PageID]", '', 'SiteTree', 'ID', 'Title');
		$this->fieldTitle = new TextField("{$name}[Title]", 'Title: ', '', 300, $form);
		$this->fieldLinkmode = new DropdownField("{$name}[Linkmode]", 'Type: ', 
				array(
					'internal' => 'Internal',
					'external' => 'External'
				), '', $form);
		$this->fieldLinkmode->addExtraClass('LinkModePicker');
		$this->fieldPageID->setForm($form);
		parent::__construct($name, $title, $value, $form);
	}

	public function setForm($form) {
		$this->fieldPageID->setForm($form);
		$this->fieldCustomURL->setForm($form);
		$this->fieldTitle->setForm($form);
		$this->fieldLinkmode->setForm($form);
		return parent::setForm($form);
	}

	public function setName($name){
		$this->fieldPageID->setName("{$name}[PageID]");
		$this->fieldCustomURL->setName("{$name}[CustomURL]");
		$this->fieldTitle->setName("{$name}[Title]");
		$this->fieldLinkmode->setName("{$name}[Linkmode]");
		return parent::setName($name);
	}
	
	/**
	 * @return string
	 */
	public function Field($properties = array()) {
		Requirements::javascript(self::$module_dir . '/js/LinkFormField.js');
		Requirements::css(self::$module_dir . '/css/linkfield.css');
		return "<div class=\"fieldgroup LinkFormField \">" .
			"<div class=\"fieldgroupField LinkFormFieldTitle\">" . 
				$this->fieldTitle->SmallFieldHolder() . 
			"</div>" .
			"<div class=\"fieldgroupField LinkFormFieldLinkmode\">" . 
				$this->fieldLinkmode->SmallFieldHolder() . 
			"</div>" . 
			"<div class=\"fieldgroupField LinkFormFieldPageID\">" . 
				$this->fieldPageID->SmallFieldHolder() . 
				'<label class="right">(&uarr; Select internal page to link to (click again to unset))</label>' .
			"</div>" . 
			"<div class=\"fieldgroupField LinkFormFieldCustomURL\">" . 
				$this->fieldCustomURL->SmallFieldHolder() . 
				'<label class="right">(&uarr; Enter external URL)</label>' .
			"</div>" .
		"</div>";
	}

	public function setValue($val) {
		
		$this->value = $val;
		if(is_array($val)) {
			$this->fieldPageID->setValue($val['PageID']);
			$this->fieldCustomURL->setValue($val['CustomURL']);
			$this->fieldTitle->setValue($val['Title']);
			$this->fieldLinkmode->setValue($val['Linkmode']);
		} elseif($val instanceof NamedLinkField) {
			$this->fieldPageID->setValue($val->getPageID());
			$this->fieldCustomURL->setValue($val->getCustomURL());
			$this->fieldTitle->setValue($val->getTitle());
			$this->fieldLinkmode->setValue($val->getLinkmode());
		}
	}
	
	/**
	 * SaveInto checks if set-methods are available and use them instead of setting the values directly. saveInto
	 * initiates a new LinkField class object to pass through the values to the setter method.
	 */
	public function saveInto(DataObjectInterface $dataObject) {
		
		$fieldName = $this->name;
		if($dataObject->hasMethod("set$fieldName")) {
			$dataObject->$fieldName = DBField::create('NamedLinkField', array(
				"PageID" => $this->fieldPageID->Value(),
				"CustomURL" => $this->fieldCustomURL->Value(),
				"Title" => $this->fieldTitle->Value(),
				"Linkmode" => $this->fieldLinkmode->Value()
			));
		} else {
			if(!is_object($dataObject->$fieldName)) $dataObject->$fieldName = NamedLinkField::create();
			$dataObject->$fieldName->setPageID($this->fieldPageID->Value()); 
			$dataObject->$fieldName->setCustomURL($this->fieldCustomURL->Value());
			$dataObject->$fieldName->setTitle($this->fieldTitle->Value()); 
			$dataObject->$fieldName->setLinkmode($this->fieldLinkmode->Value());
		}
	}

	/**
	 * Returns a readonly version of this field.
	 */
	public function performReadonlyTransformation() {
		return new ReadonlyField($this->Name, $this->Title, $this->Value);
	}
	
	/**
	 * @todo Implement removal of readonly state with $bool=false
	 * @todo Set readonly state whenever field is recreated, e.g. in setAllowedCurrencies()
	 */
	public function setReadonly($bool) {
		parent::setReadonly($bool);
		
		if($bool) {
			$this->fieldPageID = $this->fieldPageID->performReadonlyTransformation();
			$this->fieldCustomURL = $this->fieldCustomURL->performReadonlyTransformation();
			$this->fieldTitle = $this->fieldTitle->performReadonlyTransformation();
			$this->fieldLinkmode = $this->fieldLinkmode->performReadonlyTransformation();
		}
	}
	
	public function tree($request) {
//		return str_replace(
//			"<ul class=\"tree\">\n", 
//			"<ul class=\"tree\">\n" . '<li id="selector-' . $this->name . '[PageID]-0"><a>(None / Custom URL)</a></li>',
//			$this->fieldPageID->tree($request)
//		);
		return $this->fieldPageID->tree($request);
	}
	
}

