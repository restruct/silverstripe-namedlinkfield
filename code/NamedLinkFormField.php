<?php
/**
 * Description of LinkFormField
 *
 * @TODO: check http://localhost/1_documentation/ss-3.3/source-class-HtmlEditorField_Toolbar.html#_LinkForm
 * for inline adding of files etc
 */
class NamedLinkFormField extends FormField {
	
	public static $module_dir = ''; // This is initially set in _config.php
	
	private static $url_handlers = array(
		'$Action!/$ID' => '$Action'
	);

	private static $allowed_actions = array(
		'tree', // treedropdown (Page)
		'treefile', // treedropdown (File)
		'load' // dependentdropdown
	);
	
	/**
	 * @var FormField
	 */
	protected $fieldPageID = null;

	/**
	 * @var FormField
	 */
	protected $fieldFileID = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldCustomURL = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldShortcode = null;

	/**
	 * @var FormField
	 */
	protected $fieldPageAnchor = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldTitle = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldLinkmode = null;

	public function __construct($name, $title = null, $value = null, $form = null) {

		// Create a callable function that returns an array of options for the DependentDropdownField.
		// When the value of the field it depends on changes, this function is called passing the
		// updated value as the first parameter ($val)
		$getanchors = function($page_id) {
			// Copied from HtmlEditorField_Toolbar::getanchors()
			if (($page = Page::get()->byID($page_id)) && !empty($page)) {
//			if (!$page->canView()) { /* ERROR? */ }
				// Similar to the regex found in HtmlEditorField.js / getAnchors method.
				if (preg_match_all("/\s(name|id)=\"([^\"]+?)\"|\s(name|id)='([^']+?)'/im", $page->Content, $matches)) {
//					var_dump(array_filter(array_merge($matches[2], $matches[4])));
					$anchors = array_filter(array_merge($matches[2], $matches[4]));
					return array_combine($anchors, $anchors);
				}
			}
		};


		// naming with underscores to prevent values from actually being saved somewhere
		$this->fieldCustomURL = new TextField("{$name}[CustomURL]", '', '', 300, $form);
		$this->fieldShortcode = new TextField("{$name}[Shortcode]", '', '', 300, $form);
		$this->fieldPageID = new TreeDropdownField("{$name}[PageID]", '', 'SiteTree', 'ID', 'MenuTitle');
		$this->fieldPageID->setForm($form);
//		$this->fieldPageAnchor = new DropdownField("{$name}[PageAnchor]", 'Anchor:',array(), '', $form);
		// The DependentDropdownField, setting the source as the callable function
		// and setting the field it depends on to the appropriate field
		$this->fieldPageAnchor = DependentDropdownField::create(
				"{$name}[PageAnchor]",
				'Text-anchor:',
//				$this->getAnchors,
				$getanchors,
				$form
			)
			->setEmptyString('Page anchor: (none)')
			->setDepends($this->fieldPageID)
//			->setHasEmptyDefault(true)
		;
		$this->fieldFileID = new TreeDropdownField("{$name}[FileID]", '', 'File', 'ID', 'Name');
		$this->fieldFileID->addExtraClass('filetree');
		$this->fieldFileID->setForm($form);
		$this->fieldTitle = new TextField("{$name}[Title]", 'Title: ', '', 300, $form);
		$this->fieldLinkmode = new DropdownField("{$name}[Linkmode]", 'Type: ',
				array(
					'Page' => 'Page',
					'URL' => 'URL',
					'File' => 'File',
					'Email' => 'Email',
					'Shortcode' => 'Shortcode',
				), '', $form);
		$this->fieldLinkmode->addExtraClass('LinkModePicker');
		parent::__construct($name, $title, $value, $form);
	}

	public function setForm($form) {
		$this->fieldPageID->setForm($form);
		$this->fieldPageAnchor->setForm($form);
		$this->fieldFileID->setForm($form);
		$this->fieldCustomURL->setForm($form);
		$this->fieldShortcode->setForm($form);
		$this->fieldTitle->setForm($form);
		$this->fieldLinkmode->setForm($form);
		return parent::setForm($form);
	}

	public function setName($name){
		$this->fieldPageID->setName("{$name}[PageID]");
		$this->fieldPageAnchor->setName("{$name}[PageAnchor]");
		$this->fieldFileID->setName("{$name}[FileID]");
		$this->fieldCustomURL->setName("{$name}[CustomURL]");
		$this->fieldShortcode->setName("{$name}[Shortcode]");
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
				'<label class="right">(&uarr; Select Page to link to (click again to unset))</label>' .
			"</div>" .
			"<div class=\"fieldgroupField LinkFormFieldPageAnchor\">" .
				$this->fieldPageAnchor->SmallFieldHolder() .
//				'<label class="right">(&uarr; Anchor on page (optional))</label>' .
			"</div>" .
			"<div class=\"fieldgroupField LinkFormFieldFileID\">" .
				$this->fieldFileID->SmallFieldHolder() .
				'<label class="right">(&uarr; Select File to link to (click again to unset))</label>' .
			"</div>" .
			"<div class=\"fieldgroupField LinkFormFieldCustomURL\">" .
				$this->fieldCustomURL->SmallFieldHolder() . 
				'<label class="right">(&uarr; Enter URL/E-mail)</label>' .
			"</div>" .
			"<div class=\"fieldgroupField LinkFormFieldShortcode\">" .
				$this->fieldShortcode->SmallFieldHolder() .
				'<label class="right">(&uarr; Enter Shortcode)</label>' .
			"</div>" .
		"</div>";
	}

	public function setValue($val) {
		
		$this->value = $val;
		if(is_array($val)) {
			$this->fieldPageID->setValue($val['PageID']);
			if(isset($val['PageAnchor'])) {
			    $this->fieldPageAnchor->setValue($val['PageAnchor']);
			}
			$this->fieldFileID->setValue($val['FileID']);
			$this->fieldCustomURL->setValue($val['CustomURL']);
			$this->fieldShortcode->setValue($val['Shortcode']);
			$this->fieldTitle->setValue($val['Title']);
			$this->fieldLinkmode->setValue($val['Linkmode']);
		} elseif($val instanceof NamedLinkField) {
			$this->fieldPageID->setValue($val->getPageID());
			$this->fieldPageAnchor->setValue($val->getPageAnchor());
			$this->fieldFileID->setValue($val->getFileID());
			$this->fieldCustomURL->setValue($val->getCustomURL());
			$this->fieldShortcode->setValue($val->getShortcode());
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
				"PageAnchor" => $this->fieldPageAnchor->Value(),
				"FileID" => $this->fieldFileID->Value(),
				"CustomURL" => $this->fieldCustomURL->Value(),
				"Shortcode" => $this->fieldShortcode->Value(),
				"Title" => $this->fieldTitle->Value(),
				"Linkmode" => $this->fieldLinkmode->Value()
			));
		} else {
			if(!is_object($dataObject->$fieldName)) $dataObject->$fieldName = NamedLinkField::create();
			$dataObject->$fieldName->setPageID($this->fieldPageID->Value()); 
			$dataObject->$fieldName->setPageAnchor($this->fieldPageAnchor->Value());
			$dataObject->$fieldName->setCustomURL($this->fieldCustomURL->Value());
			$dataObject->$fieldName->setShortcode($this->fieldShortcode->Value());
			$dataObject->$fieldName->setFileID($this->fieldFileID->Value());
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
			$this->fieldPageAnchor = $this->fieldPageAnchor->performReadonlyTransformation();
			$this->fieldCustomURL = $this->fieldCustomURL->performReadonlyTransformation();
			$this->fieldShortcode = $this->fieldShortcode->performReadonlyTransformation();
			$this->fieldFileID = $this->fieldFileID->performReadonlyTransformation();
			$this->fieldTitle = $this->fieldTitle->performReadonlyTransformation();
			$this->fieldLinkmode = $this->fieldLinkmode->performReadonlyTransformation();
		}
	}

	// pass the request on to TreeDropdown
	public function treefile($request)
	{
		return $this->fieldFileID->tree($request);
	}

	// pass the request on to TreeDropdown
	public function tree($request) {
		return $this->fieldPageID->tree($request);
	}

	// pass the request on to DependentDropdown
	public function load($request)
	{
		return $this->fieldPageAnchor->load($request);
	}

//	public function getAnchors($page_id) {
//		// Copied from HtmlEditorField_Toolbar::getanchors()
//		if (($page = Page::get()->byID($page_id)) && !empty($page)) {
////			if (!$page->canView()) { /* ERROR? */ }
//			// Similar to the regex found in HtmlEditorField.js / getAnchors method.
//			if (preg_match_all("/\s(name|id)=\"([^\"]+?)\"|\s(name|id)='([^']+?)'/im", $page->Content, $matches)) {
//				return array_filter(array_merge($matches[2], $matches[4]));
//			}
//		}
//	}


}

