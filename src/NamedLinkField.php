<?php

namespace Restruct\SilverStripe\ORM\FieldType;

use SilverStripe\Dev\Debug;
use SilverStripe\ORM\FieldType\DBComposite;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBEnum;
use Restruct\SilverStripe\Forms\NamedLinkFormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\ArrayList;


/**
 * A link field which will store a link in the database.
 *
 */
class NamedLinkField extends DBComposite
{

    /**
     * @var int The PageID for this link.
     */
    protected $page_id;

    /**
     * @var string A custom URL for this link (or e-mail address)
     */
    protected $custom_url;

    /**
     * @var string A shortcode
     */
    protected $shortcode;

    /**
     * @var int The FileID for this link.
     */
    protected $file_id;

    /**
     * @var string A custom URL for this link
     */
    protected $page_anchor;

    /**
     * @var string A title for this link.
     */
    protected $title;

    /**
     * @var string A LinkType for this link
     */
    protected $LinkType;

    /**
     * @var boolean Is this record changed or not?
     */
    protected $isChanged = false;


    /**
     * Similiar to {@link DataObject::$db},
     * holds an array of composite field names.
     * Don't include the fields "main name",
     * it will be prefixed in {@link requireField()}.
     *
     * @var array $composite_db
     */
    private static $composite_db = array(
        //'PageID' => 'Int',
        'PageID' => 'Varchar', // seems only way to prevent "Column 'LinkPageID' cannot be null" error
        'PageAnchor' => 'Varchar',
        'FileID' => 'Varchar',
        'CustomURL' => 'Varchar(2000)',
        'Shortcode' => 'Varchar(255)',
        'Title' => 'Varchar(255)',
        'Linkmode' => "Enum(array('Page','URL','File','Email','Shortcode'))"
    );

//	public function __construct($name = null) {
//		parent::__construct($name);
//	}

//	/**
//	 * Set the value of this field in various formats.
//	 * Used by {@link DataObject->getField()}, {@link DataObject->setCastedField()}
//	 * {@link DataObject->dbObject()} and {@link DataObject->write()}.
//	 *
//	 * As this method is used both for initializing the field after construction,
//	 * and actually changing its values, it needs a {@link $markChanged}
//	 * parameter.
//	 *
//	 * @param DBField|array $value
//	 * @param array $record Map of values loaded from the database
//	 * @param boolean $markChanged Indicate wether this field should be marked changed.
//	 *  Set to FALSE if you are initializing this field after construction, rather
//	 *  than setting a new value.
//	 */
//	public function setValue($value, $record = null, $markChanged = true){
//
//		if ($value instanceof NamedLinkField && $value->exists()) {
//
//			$this->setPageID($value->getPageID(), $markChanged);
//			$this->setPageAnchor($value->getPageAnchor(), $markChanged);
//			$this->setCustomURL($value->getCustomURL(), $markChanged);
//			$this->setShortcode($value->getShortcode(), $markChanged);
//			$this->setFileID($value->getFileID(), $markChanged);
//			$this->setTitle($value->getTitle(), $markChanged);
//			$this->setLinkType($value->getLinkType(), $markChanged);
//		} elseif ( $record &&
//					( isset($record[$this->name . 'PageID'])
//						|| isset($record[$this->name . 'CustomURL'])
//						|| isset($record[$this->name . 'Shortcode'])
//						|| isset($record[$this->name . 'Title'])
//						|| isset($record[$this->name . 'LinkType']) )
//				) {
//			$this->setPageID(
//				(isset($record[$this->name . 'PageID'])) ? $record[$this->name . 'PageID'] : null,
//				$markChanged
//			);
//			$this->setPageAnchor(
//				(isset($record[$this->name . 'PageAnchor'])) ? $record[$this->name . 'PageAnchor'] : null,
//				$markChanged
//			);
//			$this->setCustomURL(
//				(isset($record[$this->name . 'CustomURL'])) ? $record[$this->name . 'CustomURL'] : null,
//				$markChanged
//			);
//			$this->setShortcode(
//				(isset($record[$this->name . 'Shortcode'])) ? $record[$this->name . 'Shortcode'] : null,
//				$markChanged
//			);
//			$this->setFileID(
//				(isset($record[$this->name . 'FileID'])) ? $record[$this->name . 'FileID'] : null,
//				$markChanged
//			);
//			$this->setTitle(
//				(isset($record[$this->name . 'Title'])) ? $record[$this->name . 'Title'] : null,
//				$markChanged
//			);
//			$this->setLinkType(
//				(isset($record[$this->name . 'LinkType'])) ? $record[$this->name . 'LinkType'] : null,
//				$markChanged
//			);
//
//		} else if (is_array($value)) {
//			if (array_key_exists('PageID', $value)) {
//				$this->setPageID($value['PageID'], $markChanged);
//			}
//			if (array_key_exists('PageAnchor', $value)) {
//				$this->setPageAnchor($value['PageAnchor'], $markChanged);
//			}
//			if (array_key_exists('CustomURL', $value)) {
//				$this->setCustomURL($value['CustomURL'], $markChanged);
//			}
//			if (array_key_exists('Shortcode', $value)) {
//				$this->setCustomURL($value['Shortcode'], $markChanged);
//			}
//			if (array_key_exists('PageID', $value)) {
//				$this->setFileID($value['FileID'], $markChanged);
//			}
//			if (array_key_exists('Title', $value)) {
//				$this->setTitle($value['Title'], $markChanged);
//			}
//			if (array_key_exists('LinkType', $value)) {
//				$this->setLinkType($value['LinkType'], $markChanged);
//			}
//		} else {
////			user_error('Invalid value in LinkField->setValue()', E_USER_ERROR);
//		}
//
//	}

    /**
     * Returns a CompositeField instance used as a default
     * for form scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
     *
     * @param string $title Optional. Localized title of the generated instance
     * @return FormField
     */
    public function scaffoldFormField($title = NULL, $params = NULL)
    {
        return NamedLinkFormField::create($this->name);
    }

    public function saveInto($dataObject)
    {
        foreach ($this->compositeDatabaseFields() as $field => $spec) {
            // Save into record
            $key = $this->getName() . $field;
            $dataObject->setField($key, $this->getField($field));
//            var_dump("Key: $key - " . $this->getField($field));
        }
    }

//	/**
//	 * Used in constructing the database schema.
//	 * Add any custom properties defined in {@link $composite_db}.
//	 * Should make one or more calls to {@link DB::requireField()}.
//	 */
//	public function requireField(){
//		$fields = $this->compositeDatabaseFields();
//		if($fields) foreach($fields as $name => $type){
//			DB::requireField($this->tableName, $this->name.$name, $type);
//		}
//	}

//	/**
//	 * Add the custom internal values to an INSERT or UPDATE
//	 * request passed through the ORM with {@link DataObject->write()}.
//	 * Fields are added in $manipulation['fields']. Please ensure
//	 * these fields are escaped for database insertion, as no
//	 * further processing happens before running the query.
//	 * Use {@link DBField->prepValueForDB()}.
//	 * Ensure to write NULL or empty values as well to allow
//	 * unsetting a previously set field. Use {@link DBField->nullValue()}
//	 * for the appropriate type.
//	 *
//	 * @param array $manipulation
//	 */
//	public function writeToManipulation(&$manipulation) {
//		if($this->getPageID()) {
//			$manipulation['fields'][$this->name.'PageID'] = $this->prepValueForDB((int)$this->getPageID());
//		} else {
//			$manipulation['fields'][$this->name.'PageID'] =
//					DBField::create_field('Int', $this->getPageID())->nullValue();
//		}
//
//		if($this->getPageAnchor()) {
//			$manipulation['fields'][$this->name.'PageAnchor'] = $this->prepValueForDB($this->getPageAnchor());
//		} else {
//			$manipulation['fields'][$this->name.'PageAnchor'] =
//					DBField::create_field('Varchar', $this->getPageAnchor())->nullValue();
//		}
//
//		if($this->getCustomURL()) {
//			$manipulation['fields'][$this->name.'CustomURL'] = $this->prepValueForDB($this->getCustomURL());
//		} else {
//			$manipulation['fields'][$this->name.'CustomURL'] =
//					DBField::create_field('Varchar', $this->getCustomURL())->nullValue();
//		}
//
//		if($this->getShortcode()) {
//			$manipulation['fields'][$this->name.'Shortcode'] = $this->prepValueForDB($this->getShortcode());
//		} else {
//			$manipulation['fields'][$this->name.'Shortcode'] =
//					DBField::create_field('Varchar', $this->getShortcode())->nullValue();
//		}
//
//		if($this->getFileID()) {
//			$manipulation['fields'][$this->name.'FileID'] = $this->prepValueForDB((int)$this->getFileID());
//		} else {
//			$manipulation['fields'][$this->name.'FileID'] =
//					DBField::create_field('Int', $this->getFileID())->nullValue();
//		}
//
//		if($this->getTitle()) {
//			$manipulation['fields'][$this->name.'Title'] = $this->prepValueForDB($this->getTitle());
//		} else {
//			$manipulation['fields'][$this->name.'Title'] =
//					DBField::create_field('Varchar', $this->getTitle())->nullValue();
//		}
//
//		if($this->getLinkType()) {
//			$manipulation['fields'][$this->name.'LinkType'] = $this->prepValueForDB($this->getLinkType());
//		} else {
//			$manipulation['fields'][$this->name.'LinkType'] =
//					DBField::create_field(DBEnum::class, $this->getLinkType())->nullValue();
//		}
//	}
//
//	/**
//	 * Add all columns which are defined through {@link requireField()}
//	 * and {@link $composite_db}, or any additional SQL that is required
//	 * to get to these columns. Will mostly just write to the {@link SQLQuery->select}
//	 * array.
//	 *
//	 * @param SQLQuery $query
//	 */
//	public function addToQuery(&$query) {
//
//		parent::addToQuery($query);
//	}
//
//	/**
//	 * Return array in the format of {@link $composite_db}.
//	 * Used by {@link DataObject->hasOwnDatabaseField()}.
//	 * @return array
//	 */
//	public function compositeDatabaseFields(){
//
//		return static::$composite_db;
//	}
//
//	/**
//	 * Determines if the field has been changed since its initialization.
//	 * Most likely relies on an internal flag thats changed when calling
//	 * {@link setValue()} or any other custom setters on the object.
//	 *
//	 * @return boolean
//	 */
//	public function isChanged(){
//		return $this->isChanged;
//	}

    /**
     * Determines if any of the properties in this field have a value,
     * meaning at least one of them is not NULL.
     *
     * @return boolean
     */
    public function exists()
    {
        return ($this->page_id > 0 || $this->file_id > 0 || $this->custom_url !== null
            || $this->shortcode !== null && $this->title !== null);
    }

//	public function getPageID() {
//		return $this->page_id;
//	}
//
//	public function setPageID($page_id, $markChanged = true) {
//		$this->isChanged = $markChanged;
//		$this->page_id = (int) $page_id;
//	}
//
//	public function getPageAnchor() {
//		return $this->page_anchor;
//	}
//
//	public function setPageAnchor($anchor, $markChanged = true) {
//		$this->isChanged = $markChanged;
//		$this->page_anchor = $anchor;
//	}
//
//	public function getFileID() {
//		return $this->file_id;
//	}
//
//	public function setFileID($file_id, $markChanged = true) {
//		$this->isChanged = $markChanged;
//		$this->file_id = (int) $file_id;
//	}
//
//	public function getCustomURL() {
//		return $this->custom_url;
//	}
//
//	public function setCustomURL($url, $markChanged = true) {
//		$this->isChanged = $markChanged;
//		$this->custom_url = $url;
//	}
//
//	public function getShortcode() {
//		return $this->shortcode;
//	}
//
//	public function setShortcode($code, $markChanged = true) {
//		$this->isChanged = $markChanged;
//		$this->shortcode = $code;
//	}
//
//	public function getTitle() {
//		return $this->title;
//	}
//
//	public function setTitle($title, $markChanged = true) {
//		$this->isChanged = $markChanged;
//		$this->title = $title;
//	}

    public function getLinkmode()
    {
        // legacy Linkmodes
        $linkmode = $this->getField('Linkmode');
        if ($linkmode == 'external') return 'URL';
        if ($linkmode == 'internal') return 'Page';

        return $linkmode;
    }

//    public function setLinkType($ltype, $markChanged = true)
//    {
//        $this->isChanged = $markChanged;
//        $this->LinkType = $ltype;
//    }

    public function Page()
    {
        $pageID = $this->getField('PageID');
        if ($pageID && $page = DataObject::get_by_id('Page', $pageID)) {
            return $page;
        }
        return null;
    }

    public function File()
    {
        $fileID = $this->getField('FileID');
        Debug::show($fileID);
        if ($fileID && $file = DataObject::get_by_id(File::class, $fileID)) {
            return $file;
        }
        return null;
    }

    public function ShortcodeOutput()
    {
        if ($this->getField('Linkmode') == 'Shortcode' && $sc = $this->getField('Shortcode')) {
            return ShortcodeParser::get_active()->parse($sc);
        }
        return null;
    }

    public function getEmail()
    {
        if ($this->getField('Linkmode') == 'Email' && filter_var($this->getField('CustomURL'), FILTER_VALIDATE_EMAIL)) {
            return "mailto:" . $this->getField('CustomURL');
        }
        return null;
    }

	public function getURL() {
		switch($this->getField('Linkmode')){

			case "external": // legacy
			case "URL" :
				$url = $this->getField('CustomURL');
				// add default http if no URL_SCHEME present (NO, relative urls should be possible)
//				if( parse_url($url, PHP_URL_SCHEME) === null ){
//					$url = 'http://' . $url;
//				}
				return Convert::raw2htmlatt($url);

			case "Shortcode":
				// Should probably be handled differently from template (<% if IsShortcode ...)
				return '';

			case "internal": // legacy
			case "Page" :
				$url = '';
				if($page = $this->Page()) $url = $page->AbsoluteLink();
				if($anchor = $this->getField('PageAnchor')) $url .= "#$anchor";
				return Convert::raw2htmlatt($url);

			case 'Email' :
				return Convert::raw2htmlatt($this->getEmail());

			default : // File
				if($file = $this->File()) return $file->AbsoluteLink();

		}

	}

//	public function __toString() {
//		return (string) $this->getURL();
//	}
//
//	public function Absolute() {
//		$relative = $this->getURL();
//		return (Director::is_site_url($relative) && Director::is_relative_url($relative))
//			? Controller::join_links(Director::protocolAndHost(), $relative)
//			: $relative;
//	}
//
//	public function forTemplate() {
//		return new ArrayList( array(
//			'Page' => $this->Page(),
//			'Anchor' => $this->getPageAnchor(),
//			'File' => $this->File(),
//			'Email' => $this->getEmail(),
//			'URL' => $this->getURL(),
//			'Title' => $this->Title,
//			'LinkType' => $this->LinkType,
//			'Absolute' => $this->Absolute(),
//			'ShortcodeOutput' => $this->ShortcodeOutput(),
//		));
//	}

//	public function forTemplate() {
//		$items = array();
//		if ($this->value) {
//			foreach ($this->value as $key => $item) {
//				$v = new Varchar('Value');
//				$v->setValue($item);
//
//				$obj = new ArrayData(array(
//					'Value' => $v,
//					'Key'	=> $key,
//					'Title' => $item
//				));
//				$items[] = $obj;
//			}
//		}
//
//		return new ArrayList($items);
//	}


}
