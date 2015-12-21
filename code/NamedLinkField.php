<?php

/**
 * A link field which will store a link in the database.
 * 
 * @author Simon Elvery
 * @package silverstripe-link-field
 */
class NamedLinkField extends DBField implements CompositeDBField
{
    
    /**
     * @var int The PageID for this link.
     */
    protected $page_id;

    /**
     * @var string A custom URL for this link
     */
    protected $custom_url;
    
    /**
     * @var string A title for this link.
     */
    protected $title;

    /**
     * @var string A linkmode for this link
     */
    protected $linkmode;

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
        'CustomURL' => 'Varchar(2000)',
        'Title' => 'Varchar(255)',
        'Linkmode' => "Enum(array('internal','external'))"
    );
    
    public function __construct($name = null)
    {
        parent::__construct($name);
    }
    
    /**
     * Set the value of this field in various formats.
     * Used by {@link DataObject->getField()}, {@link DataObject->setCastedField()}
     * {@link DataObject->dbObject()} and {@link DataObject->write()}.
     * 
     * As this method is used both for initializing the field after construction,
     * and actually changing its values, it needs a {@link $markChanged}
     * parameter. 
     * 
     * @param DBField|array $value
     * @param array $record Map of values loaded from the database
     * @param boolean $markChanged Indicate wether this field should be marked changed. 
     *  Set to FALSE if you are initializing this field after construction, rather
     *  than setting a new value.
     */
    public function setValue($value, $record = null, $markChanged = true)
    {
        if ($value instanceof NamedLinkField && $value->exists()) {
            $this->setPageID($value->getPageID(), $markChanged);
            $this->setCustomURL($value->getCustomURL(), $markChanged);
            $this->setTitle($value->getTitle(), $markChanged);
            $this->setLinkmode($value->getLinkmode(), $markChanged);
        } elseif ($record &&
                    (isset($record[$this->name . 'PageID'])
                        || isset($record[$this->name . 'CustomURL'])
                        || isset($record[$this->name . 'Title'])
                        || isset($record[$this->name . 'Linkmode']))
                ) {
            $this->setPageID(
                (isset($record[$this->name . 'PageID'])) ? $record[$this->name . 'PageID'] : null,
                $markChanged
            );
            $this->setCustomURL(
                (isset($record[$this->name . 'CustomURL'])) ? $record[$this->name . 'CustomURL'] : null,
                $markChanged
            );
            $this->setTitle(
                (isset($record[$this->name . 'Title'])) ? $record[$this->name . 'Title'] : null,
                $markChanged
            );
            $this->setLinkmode(
                (isset($record[$this->name . 'Linkmode'])) ? $record[$this->name . 'Linkmode'] : null,
                $markChanged
            );
        } elseif (is_array($value)) {
            if (array_key_exists('PageID', $value)) {
                $this->setPageID($value['PageID'], $markChanged);
            }
            
            if (array_key_exists('CustomURL', $value)) {
                $this->setCustomURL($value['CustomURL'], $markChanged);
            }
            if (array_key_exists('Title', $value)) {
                $this->setTitle($value['Title'], $markChanged);
            }
            if (array_key_exists('Linkmode', $value)) {
                $this->setLinkmode($value['Linkmode'], $markChanged);
            }
        } else {
            //			user_error('Invalid value in LinkField->setValue()', E_USER_ERROR);
        }
    }
    
    /**
     * Used in constructing the database schema.
     * Add any custom properties defined in {@link $composite_db}.
     * Should make one or more calls to {@link DB::requireField()}.
     */
    public function requireField()
    {
        $fields = $this->compositeDatabaseFields();
        if ($fields) {
            foreach ($fields as $name => $type) {
                DB::requireField($this->tableName, $this->name.$name, $type);
            }
        }
    }
    
    /**
     * Add the custom internal values to an INSERT or UPDATE
     * request passed through the ORM with {@link DataObject->write()}.
     * Fields are added in $manipulation['fields']. Please ensure
     * these fields are escaped for database insertion, as no
     * further processing happens before running the query.
     * Use {@link DBField->prepValueForDB()}.
     * Ensure to write NULL or empty values as well to allow 
     * unsetting a previously set field. Use {@link DBField->nullValue()}
     * for the appropriate type.
     * 
     * @param array $manipulation
     */
    public function writeToManipulation(&$manipulation)
    {
        if ($this->getPageID()) {
            $manipulation['fields'][$this->name.'PageID'] = $this->prepValueForDB((int)$this->getPageID());
        } else {
            $manipulation['fields'][$this->name.'PageID'] =
                    DBField::create_field('Int', $this->getPageID())->nullValue();
        }
        
        if ($this->getCustomURL()) {
            $manipulation['fields'][$this->name.'CustomURL'] = $this->prepValueForDB($this->getCustomURL());
        } else {
            $manipulation['fields'][$this->name.'CustomURL'] =
                    DBField::create_field('Varchar', $this->getCustomURL())->nullValue();
        }
        
        if ($this->getTitle()) {
            $manipulation['fields'][$this->name.'Title'] = $this->prepValueForDB($this->getTitle());
        } else {
            $manipulation['fields'][$this->name.'Title'] =
                    DBField::create_field('Varchar', $this->getTitle())->nullValue();
        }
        
        if ($this->getLinkmode()) {
            $manipulation['fields'][$this->name.'Linkmode'] = $this->prepValueForDB($this->getLinkmode());
        } else {
            $manipulation['fields'][$this->name.'Linkmode'] =
                    DBField::create_field('Enum', $this->getLinkmode())->nullValue();
        }
    }
    
    /**
     * Add all columns which are defined through {@link requireField()}
     * and {@link $composite_db}, or any additional SQL that is required
     * to get to these columns. Will mostly just write to the {@link SQLQuery->select}
     * array.
     * 
     * @param SQLQuery $query
     */
    public function addToQuery(&$query)
    {
        parent::addToQuery($query);
    }
    
    /**
     * Return array in the format of {@link $composite_db}.
     * Used by {@link DataObject->hasOwnDatabaseField()}.
     * @return array
     */
    public function compositeDatabaseFields()
    {
        return static::$composite_db;
    }
    
    /**
     * Determines if the field has been changed since its initialization.
     * Most likely relies on an internal flag thats changed when calling
     * {@link setValue()} or any other custom setters on the object.
     * 
     * @return boolean
     */
    public function isChanged()
    {
        return $this->isChanged;
    }
    
    /**
     * Determines if any of the properties in this field have a value,
     * meaning at least one of them is not NULL.
     * 
     * @return boolean
     */
    public function exists()
    {
        return ($this->page_id > 0 || $this->custom_url !== null || $this->title !== null);
    }
    
    public function getPageID()
    {
        return $this->page_id;
    }
    
    public function setPageID($page_id, $markChanged = true)
    {
        $this->isChanged = $markChanged;
        $this->page_id = (int) $page_id;
    }
    
    public function getCustomURL()
    {
        return $this->custom_url;
    }
    
    public function setCustomURL($url, $markChanged = true)
    {
        $this->isChanged = $markChanged;
        $this->custom_url = $url;
    }
    
    public function getTitle()
    {
        return $this->title;
    }
    
    public function setTitle($title, $markChanged = true)
    {
        $this->isChanged = $markChanged;
        $this->title = $title;
    }
    
    public function getLinkmode()
    {
        return $this->linkmode;
    }
    
    public function setLinkmode($ltype, $markChanged = true)
    {
        $this->isChanged = $markChanged;
        $this->linkmode = $ltype;
    }
    
    /**
     * Returns a CompositeField instance used as a default
     * for form scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
     * 
     * @param string $title Optional. Localized title of the generated instance
     * @return FormField
     */
    public function scaffoldFormField($title = null)
    {
        $field = new NamedLinkFormField($this->name);
        return $field;
    }
    
    public function Page()
    {
        if ($this->getPageID() && $page = DataObject::get_by_id('Page', $this->getPageID())) {
            return $page;
        }
        return null;
    }
    
    public function getURL()
    {
        if ($this->linkmode == "external") {
            $url = $this->getCustomURL();
            // add default http if no URL_SCHEME present
            if (parse_url($url, PHP_URL_SCHEME) === null) {
                $url = 'http://' . $url;
            }
            return Convert::raw2htmlatt($url);
        } elseif ($page = $this->Page()) {
            return $page->AbsoluteLink();
        }
    }
    
    public function __toString()
    {
        return (string) $this->getURL();
    }

    public function forTemplate()
    {
        return new ArrayList(array(
            'Page' => $this->Page(),
            'CustomURL' => 'Varchar(2000)',
            'URL' => $this->getURL(),
            'Title' => $this->Title,
            'Linkmode' => $this->linkmode,
            'Absolute' => $this->Absolute()
        ));
    }
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

    public function Absolute()
    {
        $relative = $this->getURL();
        return (Director::is_site_url($relative) && Director::is_relative_url($relative))
            ? Controller::join_links(Director::protocolAndHost(), $relative)
            : $relative;
    }
}
