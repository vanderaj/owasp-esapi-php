<?php
    // $Id: unit_tests.php 987 2005-05-14 17:16:30Z lastcraft $
    if (! defined('TEST')) {
        define('TEST', __FILE__);
    }
    require_once(dirname(__FILE__) . '/test_groups.php');
    require_once(dirname(__FILE__) . '/../reporter.php');
    
    if (TEST == __FILE__) {
        $test = &new UnitTests();
        if (SimpleReporter::inCli()) {
            exit ($test->run(new TextReporter()) ? 0 : 1);
        }
        $test->run(new HtmlReporter());
    }
?>