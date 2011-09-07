<?php
/*
  Copyright (c) 2011 Hardeep Shoker

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in
  all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
  THE SOFTWARE.
 */

/*
 * @class IOSTestRunner
 * @brief Provides a class for running automated tests for IOS applications
 *
 * @author Hardeep Shoker
 *
 * $iosTest = new IOSTestRunner();
 * $iosTest->run("/Users/hardeep/nitobi/phonegap/integration/iphone/tmp/iphone.app/iphone");
 */

class IOSTestRunner extends TestRunner {

    /*
     * @brief Set up some initial variables
     */
    public function __construct() {
        parent::__construct();
        $this->appName = null;
        $this->launcher = "/Developer/Platforms/iPhoneSimulator.platform/Developer/Applications/iPhone Simulator.app/Contents/MacOS/iPhone Simulator";
    }

    /*
     * @breif What application to run
     * @param pathToApp the path to the application that will be ran
     */
    public function run($pathToApp) {

        $additionalArgs = array(
            "-SimulateApplication", $pathToApp
        );

        $this->appName = end(explode("/", $pathToApp));

        $this->additionalArguments = $additionalArgs;

        return parent::run();
    }

    /*
     * brief Remove the application process from memory
     */
    public function tearDown() {

        echo "ps aux | grep " . $this->appName . " | grep -v grep | grep -v " . end(explode("/", __file__)) . " | awk '{print $2}'";

        exec("ps aux | grep " . $this->appName . " | grep -v grep | grep -v " . end(explode("/", __file__)) . " | awk '{print $2}'", $result, $status);

        if ($status > 0) {
            $this->console('Failed to find child');
            return FAILURE;
        }

        if (is_array($result) && count($result) > 0) {
            exec("kill " . $result[0], $result, $status);
            return FAILURE;
        } else {
            $this->console("Failed to find child. Does the process exist?");
            RETURN FAILURE;
        }

        $this->console("Killed iPhone app proccess");

        return parent::tearDown();
    }

    /*
     * breif Tasks to perform when a test has failed
     */
    public function failedTestTeardown() {

        $this->console(CONSOLE_LINE);
        $this->console("Terminating iPhone application...");
        $this->teardown();
        return parent::failedTestTeardown();
    }

    /* member variables */
    private $appName; // The application that will be ran

}
