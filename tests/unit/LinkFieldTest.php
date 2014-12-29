<?php
/**
 * Description of LinkFieldTest
 *
 * @author Simon Elvery
 * @package silverstripe-link-field
 */
class LinkFieldTest extends SapphireTest {
	
	static $fixture_file = 'LinkFieldTest.yml';

	protected $extraDataObjects = array(
		'LinkFieldTest_DataObject',
	);
	
	public function testLinkFieldsReturnedAsObjects() {
		$obj = $this->objFromFixture('LinkFieldTest_DataObject', 'test1');
		$this->assertInstanceOf('LinkField', $obj->Link);
	}

	
	public function testLoadFromFixture() {
		$obj = $this->objFromFixture('LinkFieldTest_DataObject', 'test1');
		$this->assertInstanceOf('LinkField', $obj->Link);
		$this->assertEquals($obj->Link->getPageID(), 0);
		$this->assertEquals($obj->Link->getCustomURL(), "http://leftrightandcentre.com.au");
	}
	
	public function testNotChangedOnConstruction() {
		$obj = $this->objFromFixture('LinkFieldTest_DataObject', 'test1');
		$this->assertEquals($obj->Link->isChanged(), false);
	}
	
	public function testCanOverwriteSettersWithNull() {
		$obj = new LinkFieldTest_DataObject();

		$f1 = new LinkField();
		$f1->setPageID(9);
		$f1->setCustomURL('/test');
		$obj->Link = $f1;
		$obj->write();
		
		$f2 = new LinkField();
		$f2->setPageID(null);
		$f2->setCustomURL(null);
		$obj->Link = $f2;
		$obj->write();

		$linkTest = DataObject::get_by_id('LinkFieldTest_DataObject',$obj->ID);
		$this->assertTrue($linkTest instanceof LinkFieldTest_DataObject);
		$this->assertEquals('', $linkTest->CustomURL);
		$this->assertEquals(null, $linkTest->PageID);
	}
	
	public function testIsChangedPageID() {
		$obj = $this->objFromFixture('LinkFieldTest_DataObject', 'test1');
		$obj->Link->setPageID(1);
		$this->assertEquals($obj->Link->isChanged(), true);
	}
	
	public function testIsChangedCustomURL() {
		$obj = $this->objFromFixture('LinkFieldTest_DataObject', 'test1');
		$obj->Link->setCustomURL('');
		$this->assertEquals($obj->Link->isChanged(), true);
	}
	
	public function testPageURL() {
		
		$page = new Page();
		$page->URLSegment = 'test';
		$page->write();
		
		$obj = new LinkFieldTest_DataObject();
		$obj->Link->setPageID($page->ID);
		$obj->write();
		
		$this->assertEquals($page->Link(), $obj->Link->URL);
	}
	
}

class LinkFieldTest_DataObject extends DataObject implements TestOnly {
	
	public static $db = array(
		'Link' => 'LinkField'
	);
	
}
