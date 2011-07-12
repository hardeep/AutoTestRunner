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
 * @class AndroidTestRunner
 * @brief Provides a class for running automated tests for Android applications
 *
 * @author Hardeep Shoker
 *
 * @example Usage :
 *
 * $androidTest = new AndroidTestRunner();
 * $androidTest->setLauncher("/Users/hardeep/nitobi/sdk/android-sdk-mac_x86/tools/emulator");
 * $androidTest->setAvdProfile("default");
 * $androidTest->setAction("android.intent.action.MAIN");
 * $androidTest->setActivity("com.phonegap.getting.started/.PhoneGap_GettingStarted");
 * $androidTest->run("/Users/hardeep/Downloads/PhoneGap_GettingStarted-debug.apk");
 */

include "TestRunner.class.php";

class AndroidTestRunner extends TestRunner {

    /*
     * @breif initialize some default variables
     */
    public function __construct() {
        parent::__construct();

        $this->launcher = "emulator";
        $this->avd = "default";
        $this->pathToAdb = "adb";
        $this->timeToLive = 240;
        $this->emulatorPort = 5554;
    }

    /*
     * @breif Set up some variables before running the Android test
     */
    public function startUp() {

        parent::startUp();

        $this->console(CONSOLE_LINE);

        $this->additionalArguments = array("-cpu-delay", "0",
            "-no-boot-anim",
            "-avd", $this->avd,
            "-port", $this->emulatorPort
        );

        return OKAY;
    }

    /*
     * @breif Verify if the package manager is ready, then install and run the package
     */
    public function serverStartUp() {

        parent::serverStartUp();

        if ($this->packageManagerReady(true, 2, 240) > 0) {
            return FAILURE;
        }

        if ($this->installPackage() > 0) {
            return FAILURE;
        }

        if ($this->runPackage() > 0) {
            return FAILURE;
        }

        RETURN OKAY;
    }

    /*
     * @breif Tasks to perform after the test has completed
     */
    public function tearDown() {

        return parent::tearDown();
    }

    /*
     * @brief Check if the package manager on the emulator is ready
     * @param blocking If set to false will not wait for package manager to become ready
     * @param waitTime How long to wait before checking if the package manager is ready
     * @param timeOut Maximum time to wait for the package manager to become ready
     */
    public function packageManagerReady($blocking = true, $waitTime=2, $timeOut = 30) {

        $this->console(CONSOLE_LINE);
        $this->console("Waiting for package manager");
        // command to check if the package manager is ready

        $command = $this->pathToAdb . " -s emulator-" . $this->emulatorPort . " shell pm path android 2>&1 /dev/null";

        if ($blocking === true) {
            $startTime = time();
            do {
                $timeNow = time();
                $result = array();

                exec($command, $result, $status);
                // timeout occured?
                if ($timeOut != null && $timeNow - $startTime >= $timeOut) {
                    $this->console("Timeout has occurred waiting for package manager.");
                    return FAILURE;
                }

                if ($waitTime > 0)
                    sleep($waitTime);

                echo ".";
            }
            while ($status != 0);

            return OKAY;
        }
        else {
            exec($command, $result, $status);
            return $status;
        }

        return FAILURE;
    }

    /*
     * brief Install a package on the device
     */
    public function installPackage() {

        $this->console(CONSOLE_LINE);
        $this->console("Installing package...");
        $command = $this->pathToAdb . " -s emulator-" . $this->emulatorPort . " install -r " . $this->app;
        exec($command, $result, $status);
        return $status;
    }

    /*
     * brief Run a package on the emulator
     * @param blocking Wait for the device to become ready, the package manager becomes ready
     * before the device is ready to run an application
     * @param timeOut How long to wait for the device to become ready
     */
    public function runPackage($blocking = true, $timeOut = 30) {

        $this->console(CONSOLE_LINE);
        $this->console("Running package: " . $this->app);
        $timeStarted = time();
        do {
            $timeNow = time();
            $command = $this->pathToAdb . " -s emulator-" . $this->emulatorPort . " shell am start -a " . $this->action . " -n " . $this->activity;
            exec($command, $result, $status);
            echo $command;
            //var_dump($result);

            if (preg_match('/^starting: intent/', strtolower($result[0])) > 0) {
                return OKAY;
            }
            if ($timeOut != null && $timeNow - $timeStarted >= $timeOut) {
                $this->console("Time out has occured running package");
                return FAILURE;
            }
        } while ($blocking);

        return true;
    }

    /*
     * @brief Run the test
     * @param app The location to the apk file which will be run in the test
     */
    public function run($app) {

        $this->app = $app;
        if (empty($app) || !is_file($app))
            return FAILURE;
        parent::run();
    }

    /*
     * @brief Set what avd to launch the emulator under
     * @param avd The avd name example: "default"
     */
    public function setAvdProfile($avd) {

        $this->avd = $avd;
    }

    /*
     * @brief Set what action to perform
     * @param action The action that will be performed
     */
    public function setAction($action) {

        $this->action = $action;
    }

    /*
     * @brief Set what activity to perform
     * @param Activity The activity to perform
     */
    public function setActivity($activity) {
        $this->activity = $activity;
    }

    /*
     * @brief Return the avd profile
     */
    public function getAvdProfile() {
        return $this->avd;
    }

    /*
     * @breif Set the Adb path
     */
    public function setAdbPath($pathToAdb) {
        $this->pathToAdb = $pathToAdb;
    }

    /*
     * @breif Return the adb path
     */
    public function getAdbPath() {
        return $this->pathToAdb;
    }

    /* member variables */

    protected $pathToAdb; // Store the path to the adb bin
    protected $avd; // what avd will be used in the test
    protected $emulatorPort; // what port the emulator will run on
    protected $app; // what application will be run
    protected $action; // what action to perform
    protected $activity; // what activity to launch

}

