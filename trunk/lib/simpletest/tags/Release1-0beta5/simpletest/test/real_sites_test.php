<?php
    // $Id: real_sites_test.php 558 2004-03-17 01:35:56Z lastcraft $
    
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', '../');
    }
    require_once(SIMPLE_TEST . 'web_tester.php');

    class LiveSitesTestCase extends WebTestCase {
        function LiveSitesTestCase() {
            $this->WebTestCase();
        }
        function testLastCraft() {
            $this->assertTrue($this->get('http://www.lastcraft.com'));
            $this->assertResponse(array(200));
            $this->assertMime(array('text/html'));
            $this->clickLink('About');
            $this->assertTitle('About Last Craft');
        }
        function testSourceforge() {
            $this->assertTrue($this->get('http://sourceforge.net/projects/simpletest/'));
            $this->clickLink('statistics');
            $this->assertWantedPattern('/Statistics for the past 7 days/');
            $this->assertTrue($this->setField('report', 'Monthly'));
            $this->clickSubmit('Change Stats View');
            $this->assertWantedPattern('/Statistics for the past \d+ months/');
        }
    }
?>