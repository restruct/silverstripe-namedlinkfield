<?php

/**
 * LinkFormFieldTest
 *
 * @author Simon Elvery
 * @package silverstripe-link-field
 */
class LinkFormFieldTest extends SapphireTest
{
    
    protected $extraDataObjects = array(
        'LinkFormFieldTest_DataObject'
    );

    public function testSaveInto()
    {
        $o = new LinkFormFieldTest_DataObject();
        
        $db = new LinkField();
        $db->setPageID(1);
        $db->setCustomURL('http://leftrightandcentre.com.au');
        $f = new LinkFormField('Link', 'Link', $db);
        
        $f->saveInto($o);
        $this->assertEquals($o->Link->getPageID(), 1);
        $this->assertEquals($o->Link->getCustomURL(), 'http://leftrightandcentre.com.au');
    }
}


class LinkFormFieldTest_DataObject extends DataObject implements TestOnly
{
    public static $db = array(
        'Link' => 'LinkField'
    );
}
