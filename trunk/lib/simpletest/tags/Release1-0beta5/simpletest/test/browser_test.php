<?php
    // $Id: browser_test.php 563 2004-03-17 20:45:13Z lastcraft $
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', '../');
    }
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'user_agent.php');
    require_once(SIMPLE_TEST . 'http.php');
    Mock::generate('SimpleHttpResponse');
    Mock::generate('SimpleHttpHeaders');
    Mock::generate('SimplePage');
    Mock::generate('SimpleForm');
    Mock::generate('SimpleUserAgent');
    Mock::generatePartial(
            'SimpleBrowser',
            'MockParseSimpleBrowser',
            array('_createUserAgent', '_parse'));
    
    class TestOfHistory extends UnitTestCase {
        function TestOfHistory() {
            $this->UnitTestCase();
        }
        function testEmptyHistoryHasFalseContents() {
            $history = &new SimpleBrowserHistory();
            $this->assertIdentical($history->getMethod(), false);
            $this->assertIdentical($history->getUrl(), false);
            $this->assertIdentical($history->getParameters(), false);
        }
        function testCannotMoveInEmptyHistory() {
            $history = &new SimpleBrowserHistory();
            $this->assertFalse($history->back());
            $this->assertFalse($history->forward());
        }
        function testCurrentTargetAccessors() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.here.com/'), array());
            $this->assertIdentical($history->getMethod(), 'GET');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.here.com/'));
            $this->assertIdentical($history->getParameters(), array());
        }
        function testSecondEntryAccessors() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $history->recordEntry('POST', new SimpleUrl('http://www.second.com/'), array('a' => 1));
            $this->assertIdentical($history->getMethod(), 'POST');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.second.com/'));
            $this->assertIdentical($history->getParameters(), array('a' => 1));
        }
        function testGoingBackwards() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $history->recordEntry('POST', new SimpleUrl('http://www.second.com/'), array('a' => 1));
            $this->assertTrue($history->back());
            $this->assertIdentical($history->getMethod(), 'GET');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
            $this->assertIdentical($history->getParameters(), array());
        }
        function testGoingBackwardsOffBeginning() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $this->assertFalse($history->back());
            $this->assertIdentical($history->getMethod(), 'GET');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
            $this->assertIdentical($history->getParameters(), array());
        }
        function testGoingForwardsOffEnd() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $this->assertFalse($history->forward());
            $this->assertIdentical($history->getMethod(), 'GET');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
            $this->assertIdentical($history->getParameters(), array());
        }
        function testGoingBackwardsAndForwards() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $history->recordEntry('POST', new SimpleUrl('http://www.second.com/'), array('a' => 1));
            $this->assertTrue($history->back());
            $this->assertTrue($history->forward());
            $this->assertIdentical($history->getMethod(), 'POST');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.second.com/'));
            $this->assertIdentical($history->getParameters(), array('a' => 1));
        }
        function testNewEntryReplacesNextOne() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $history->recordEntry('POST', new SimpleUrl('http://www.second.com/'), array('a' => 1));
            $history->back();
            $history->recordEntry('GET', new SimpleUrl('http://www.third.com/'), array());
            $this->assertIdentical($history->getMethod(), 'GET');
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.third.com/'));
            $this->assertIdentical($history->getParameters(), array());
        }
        function testNewEntryDropsFutureEntries() {
            $history = &new SimpleBrowserHistory();
            $history->recordEntry('GET', new SimpleUrl('http://www.first.com/'), array());
            $history->recordEntry('GET', new SimpleUrl('http://www.second.com/'), array());
            $history->recordEntry('GET', new SimpleUrl('http://www.third.com/'), array());
            $history->back();
            $history->back();
            $history->recordEntry('GET', new SimpleUrl('http://www.fourth.com/'), array());
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.fourth.com/'));
            $this->assertFalse($history->forward());
            $history->back();
            $this->assertIdentical($history->getUrl(), new SimpleUrl('http://www.first.com/'));
            $this->assertFalse($history->back());
        }
    }
    
    class TestOfParsedPageAccess extends UnitTestCase {
        function TestOfParsedPageAccess() {
            $this->UnitTestCase();
        }
        function &loadPage(&$page) {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            $headers->setReturnValue('getAuthentication', 'Basic');
            $headers->setReturnValue('getRealm', 'Somewhere');
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');
            $response->setReturnReference('getHeaders', $headers);
            
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $response);
            
            $browser = &new MockParseSimpleBrowser($this);
            $browser->setReturnReference('_createUserAgent', $agent);
            $browser->setReturnReference('_parse', $page);
            $browser->expectOnce('_parse', array('stuff'));
            $browser->SimpleBrowser();
            
            $browser->get('http://this.com/page.html');
            $this->assertEqual($browser->getResponseCode(), 200);
            $this->assertEqual($browser->getMimeType(), 'text/html');
            $this->assertEqual($browser->getAuthentication(), 'Basic');
            $this->assertEqual($browser->getRealm(), 'Somewhere');
            return $browser;
        }
        function testParse() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getRaw', 'Raw HTML');
            $page->setReturnValue('getTitle', 'Here');
            
            $browser = &$this->loadPage($page);

            $this->assertEqual($browser->getContent(), 'Raw HTML');
            $this->assertEqual($browser->getTitle(), 'Here');
            $this->assertIdentical($browser->getResponseCode(), 200);
            $this->assertEqual($browser->getMimeType(), 'text/html');
            $browser->tally();
        }
        function testLinkAffirmationWhenPresent() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrls', array('http://www.nowhere.com'));
            $page->expectOnce('getUrls', array('a link label'));
            
            $browser = &$this->loadPage($page);
            $this->assertTrue($browser->isLink('a link label'));
            
            $page->tally();
        }
        function testFormHandling() {
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getField', 'Value');
            $page->expectOnce('getField', array('key'));
            $page->expectOnce('setField', array('key', 'Value'));
            
            $browser = &$this->loadPage($page);
            $this->assertEqual($browser->getField('key'), 'Value');
            
            $browser->setField('key', 'Value');
            $page->tally();
        }
    }
    
    class TestOfBrowserNavigation extends UnitTestCase {
        function TestOfBrowserNavigation() {
            $this->UnitTestCase();
        }
        function &getSuccessfulFetch() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');
            $response->setReturnReference('getHeaders', $headers);
            return $response;
        }
        function &createBrowser(&$agent, &$page) {
            $browser = &new MockParseSimpleBrowser($this);
            $browser->setReturnReference('_createUserAgent', $agent);
            $browser->setReturnReference('_parse', $page);
            $browser->SimpleBrowser();
            return $browser;
        }
        function testClickLinkRequestsPage() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    0,
                    'fetchResponse',
                    array('GET', 'http://this.com/page.html', false));
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('GET', 'new.html', false));
            $agent->expectCallCount('fetchResponse', 2);
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrls', array('new.html'));
            $page->expectOnce('getUrls', array('New'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickLink('New'));
            
            $agent->tally();
            $page->tally();
        }
        function testClickingMissingLinkFails() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrls', array());
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertFalse($browser->clickLink('New'));
        }
        function testClickIndexedLink() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('GET', '1.html', false));
            $agent->expectCallCount('fetchResponse', 2);
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrls', array('0.html', '1.html'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickLink('New', 1));
            
            $agent->tally();
        }
        function testClinkLinkById() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('GET', 'link.html', false));
            $agent->expectCallCount('fetchResponse', 2);
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrlById', 'link.html');
            $page->expectOnce('getUrlById', array(2));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickLinkById(2));
            
            $agent->tally();
            $page->tally();
        }
        function testClickingMissingLinkIdFails() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            
            $page = &new MockSimplePage($this);
            $page->setReturnValue('getUrlById', false);
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertFalse($browser->clickLink(0));
        }
        function testSubmitFormByLabel() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('POST', 'handler.html', array('a' => 'A')));
            $agent->expectCallCount('fetchResponse', 2);
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', 'handler.html');
            $form->setReturnValue('getMethod', 'post');
            $form->setReturnvalue('submitButtonByLabel', array('a' => 'A'));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormBySubmitLabel', $form);
            $page->expectOnce('getFormBySubmitLabel', array('Go'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickSubmit('Go'));
            
            $agent->tally();
            $page->tally();
        }
        function testDefaultSubmitFormByLabel() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('GET', 'http://this.com/page.html', array('a' => 'A')));
            $agent->expectCallCount('fetchResponse', 2);
            $agent->setReturnValue('getCurrentUrl', 'http://this.com/page.html');
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', false);
            $form->setReturnValue('getMethod', 'get');
            $form->setReturnvalue('submitButtonByLabel', array('a' => 'A'));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormBySubmitLabel', $form);
            $page->expectOnce('getFormBySubmitLabel', array('Submit'));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->clickSubmit());
            
            $agent->tally();
            $page->tally();
        }
        function testSubmitFormById() {
            $agent = &new MockSimpleUserAgent($this);
            $agent->setReturnReference('fetchResponse', $this->getSuccessfulFetch());
            $agent->expectArgumentsAt(
                    1,
                    'fetchResponse',
                    array('POST', 'handler.html', array('a' => 'A')));
            $agent->expectCallCount('fetchResponse', 2);
            
            $form = &new MockSimpleForm($this);
            $form->setReturnValue('getAction', 'handler.html');
            $form->setReturnValue('getMethod', 'post');
            $form->setReturnvalue('submit', array('a' => 'A'));
            
            $page = &new MockSimplePage($this);
            $page->setReturnReference('getFormById', $form);
            $page->expectOnce('getFormById', array(33));
            
            $browser = &$this->createBrowser($agent, $page);
            $browser->get('http://this.com/page.html');
            $this->assertTrue($browser->submitFormById(33));
            
            $agent->tally();
            $page->tally();
        }
    }
?>