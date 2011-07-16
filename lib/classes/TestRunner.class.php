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

// runner constants ==============
define('ENABLE_CONSOLE', true);
define('CONSOLE_LINE', "=====================================================");

// Termination constants =========
define('PROBLEMATIC_SHUTDOWN', 1);
define('UNABLE_TO_CREATE_FILE', 2);
define('UNABLE_TO_CREATE_CHILD', 3);
define('FAILED_TO_CREATE_SOCKET', 4);
define('FAILED_TO_SET_SOCKET_OPT', 5);
define('FAILED_TO_SET_SIG_HANDLER', 6);
define('FAILED_TO_BIND_SOCKET_TO_PORT', 7);
define('FAILED_TO_LISTEN_ON_SOCKET', 8);
define('CLIENT_HANGUP', 9);
define('SOCKET_ERROR', 10);
define('OKAY', 0);
define('FAILURE', 99);

class TestRunner {

    public function __construct() {

        srand(time()); // random is used to select a default port

        $this->openSockets = null;
        $this->childPid = null;

        $this->tempFolder = "tmp";
        $this->timeToLive = 600;
        $this->launcher = null;
        $this->location = null;
        $this->port = rand(9000, 9999);
        $this->host = "localhost";
        $this->additionalArguments = array();
        $this->file = "junit-" . time() . ".xml";
        $this->outputJs = $this->tempFolder . "/server.js";
        $this->timeForChildShutdown = 10;
        $this->report = array();
        $this->maxResults = 1;
        $this->reportedResults = 0;
        $this->serverOnly = false;
    }

    // Mutators

    public function setTimeForChildShutdown($seconds) {
        $this->timeForChildShutdown = $time; // seconds to wait for child to terminate
    }

    public function setTempFolder($folder) {
        $this->tempFolder = $folder; // used to store any temporary information such as the pids
    }

    public function setTimeToLive($time) {
        $this->timeToLive = $time; // maximum time this runner will execute for
    }

    public function setLauncher($launcherPath) {
        $this->launcher = $launcherPath; // stores the path to the launcher
    }

    public function setServerPort($port) {
        $this->port = $port; // the default port this server will use
    }

    public function setHostToRunOn($host) {
        $this->host = $host; // tied to local host
    }

    public function setAdditionalArguments($args) {
        $this->additionalArguments = $args; // arguments the runner will pass to the application
    }

    public function setJUnitOutputFilename($filename) {
        $$this->file = $filename;
    }

    public function setOutputJsFilename($filename) {
        $this->outputjs = $filename;
    }

    public function setMaxReportsToWaitFor($numOfReports) {
        $this->maxResults = $numOfReports;
    }

    public function setServerOnly($bool) {
        $this->serverOnly = $bool;
    }

    // Accessors

    private function signal_handler($signal) {
        $this->console('Caught signal');

        switch ($signal) {
            case(SIGINT):
                $this->console("Caught INTERUPT signal now exiting");
                $this->failedTestTeardown($this->childPid, PROBLEMATIC_SHUTDOWN, $this->openSockets);
                break;
            case(SIGTERM):
                $this->console("Caught SIGTERM signal now exiting");
                $this->failedTestTeardown($this->childPid, PROBLEMATIC_SHUTDOWN, $this->openSockets);
                break;
            default:
                $this->console("Error unknown signal $signal."
                        . " Now exiting");
                $this->failedTestTeardown($this->childPid, PROBLEMATIC_SHUTDOWN, $this->openSockets);
                break;
        }
    }

    protected function startUp() {
        $this->console(CONSOLE_LINE);
        $this->console("Startup initiated...");

        return OKAY;
    }

    protected function tearDown() {
        $this->console(CONSOLE_LINE);
        $this->console("Teardown initiated...");
        return $this->shutdown($this->childPid, OKAY, $this->openSockets);
    }

    protected function failedTestTeardown() {
        $this->console(CONSOLE_LINE);
        $this->console("Failed Test Teardown initiated...");
        return $this->shutdown();
    }

    protected function run() {

        if ($this->startUp() > 0) {
            $this->failedTestTeardown();
            return PROBLEMATIC_SHUTDOWN;
        }

        if ($this->startTest() > 0) {
            $this->failedTestTeardown();
            return PROBLEMATIC_SHUTDOWN;
        }

        return $this->tearDown();
    }

    protected function startTest() {

        if ($this->serverOnly) {
            return $this->startServer();
        }


        $this->childPid = pcntl_fork();

        if ($this->childPid == -1) {

            $this->console("Unable to launch child with launcher");
            return UNABLE_TO_CREATE_CHILD;
        } else if ($this->childPid) {

            return $this->startServer();
        } else {

            $this->console(CONSOLE_LINE);

            if ($this->launcher === null)
                echo "launcher is null";

            $this->console("launching child " . $this->launcher . " " . implode(" ", $this->additionalArguments));

            if (@pcntl_exec($this->launcher, $this->additionalArguments) === false) {
                $this->console("########### Failed to lauch the child application");
            }

            exit(1);
        }
    }

    public function serverStartUp() {
        $this->console(CONSOLE_LINE);
        $this->console("Server Startup initiated...");
        return OKAY;
    }

    public function serverTearDown() {
        $this->console(CONSOLE_LINE);
        $this->console("Server TearDown initiated...");
        return OKAY;
    }

    public function startServer() {

        if ($this->serverStartUp() > 0) {
            $this->console("Server Startup Failed");
            return FAILURE;
        }

        $this->console("Setting sig handlers");

        if (!pcntl_signal(SIGINT, array('self', 'signal_handler'))
                || !pcntl_signal(SIGTERM, array('self', 'signal_handler'))
                || !pcntl_signal(SIGALRM, array('self', 'signal_handler'))
        ) {
            console("Could not add signal handler. Now exiting");
            return FAILED_TO_SET_SIG_HANDLER;
        }

        if (!$this->writeToFile($this->tempFolder . "/parentPid", getmypid())) {
            return UNABLE_TO_CREATE_FILE;
        }

        if (!$this->writeToFile($this->tempFolder . "/childPid", $this->childPid)) {
            $this->console("UNABLE TO CREATE FILE FOR CHILD PID");
            return UNABLE_TO_CREATE_FILE;
        }

        if (!$this->writeToFile($this->outputJs, "var serverInfo = { \"submitToHost\":\"true\", \"port\" : $this->port, \"host\":\"$$this->location\" };"
        )) {
            return UNABLE_TO_CREATE_FILE;
        }

        $this->console("Creating socket for server...");

        if (($socket = socket_create(AF_INET, SOCK_STREAM, 0)) === false) {
            $this->console("Failed to open socket: " .
                    socket_strerror(socket_last_error()));
            return FAILED_TO_CREATE_SOCKET;
        }

        $this->openSockets = array();

        array_push($this->openSockets, $socket);

        $this->console("Setting socket options...");

        if (!socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            $this->console("Failed to set set socket options: " .
                    socket_strerror(socket_last_error()));
            return FAILED_TO_SET_SOCKET_OPT;
        }


        $this->console("Attempting to bind socket...");

        if (!socket_bind($socket, $this->host, $this->port)) {
            $this->console("Could not bind socket");
            return FAILED_TO_BIND_SOCKET_TO_PORT;
        }

        $this->console("Socket bound on $this->host:$this->port");

        $this->console("Attempting to listen on socket..");

        if (!socket_listen($socket)) {
            $this->console("Failed to listen on socket: " .
                    socket_strerror(socket_last_error()));
            return FAILED_TO_LISTEN_ON_SOCKET;
        }

        $this->console("Now listening on socket. Waiting for connection...");

        if (!socket_set_nonblock($socket)) {
            console("Failed to set socket to non block: " .
                    socket_strerror(socket_last_error()));
            return FAILED_TO_SET_SOCKET_OPT;
        }

        $timeStarted = time();

        do {

            $timeNow = time();
            $timeDiff = $timeNow - $timeStarted;

            $client = @socket_accept($socket);

            if ($timeDiff >= $this->timeToLive) {
                $this->console("Client has not responded. Maximum time to live reached.");
                return CLIENT_HANGUP;
            }
        } while ($client === false);


        $this->console("Connection accepted. Handeling on port $client ...");

        array_push($this->openSockets, $client);

        if (!socket_set_nonblock($client)) {
            $this->console("Failed to set socket to non block: " .
                    socket_strerror(socket_last_error()));
            return FAILED_TO_SET_SOCKET_OPT;
        }

        $timeStarted = time();

        $this->console("Waiting for client response.");

        $fds;

        while (($fds = socket_select($r = array($client), $w = NULL, $e = NULL, 0)) !== false) {

            if ($fds === false) {
                $this->console("Failed to perform select on socket." .
                        socket_strerror(socket_last_error()));
                return SOCKET_ERROR;
            } else if ($fds > 0) {

                foreach ($r as $index => $sockRead) {

                    $buf = '';
                    $report = "";

                    do {
                        $buf = socket_read($sockRead, 1024);
                        $report .= $buf;
                    } while ($buf != '');

                    array_push($this->report, $report);

                    $this->reportedResults++;

                    if ($this->reportedResults >= $this->maxResults) {
                        $this->console(CONSOLE_LINE);
                        $this->console($this->report);

                        if ($this->serverTearDown() > 0) {
                            $this->console("Server Teardown Failed.");
                            return FAILURE;
                        }

                        return OKAY;
                    }
                }
            }

            $timeNow = time();

            if ($timeNow - $timeStarted > $this->timeToLive) {
                $this->console("No response from client in"
                        . $$this->timeToLive / 60 . " seconds. Assume failed test");
                return CLIENT_HANGUP;
            }
        }

        $this->console("Problem receiving data from client " . $fds);

        $this->console(socket_strerror(socket_last_error()));

        if ($fds === false) {
            $this->console("Failed to perform select on socket." .
                    socket_strerror(socket_last_error()));
            return SOCKET_ERROR;
        }

        return FAILURE;
    }

    function shutdown($pid = NULL, $status = PROBLEMATIC_SHUTDOWN, $openSockets = NULL) {

        $pid = $this->childPid;
        $openSockets = $this->openSockets;

        $this->console("Shutdown initiated...");
        $this->console(CONSOLE_LINE);

        if ($status == PROBLEMATIC_SHUTDOWN) {
            $this->console("Performing a problamatic termination...");

            $this->console("Printing backtrace ------------------");
            $this->console();
            $trace = debug_backtrace();

            if (is_array($trace) === true) {
                $this->console($trace);
            }

            $this->console();
            $this->console("End of backtrace --------------------");
        }

        if (is_array($openSockets)) {
            $this->console("Closing all sockets...");
            foreach ($openSockets as $index => $value) {
                $this->console("Closing socket $value...");
                socket_close($value);
            }
        }

        if ($pid == NULL) {
            $this->console("No child associated with process");
        } else {
            $this->console("Attempting to kill child $pid process in 10 seconds...");
            sleep(10);
            if (posix_kill($pid, SIGINT) === false) {
                $this->console("Could not kill child process");
            } else {
                $this->console("Killing process terminated");
            }

            if (!$this->__removeFile($this->tempFolder . "/childPid")) {
                $this->console("failed to remove file childPid");
            }

            if (!$this->__removeFile($this->outputJs)) {
                $this->console("failed to remove file " . $this->outputJs);
            }
        }

        if (!$this->__removeFile($this->tempFolder . "/parentPid")) {
            $this->console("failed to remove file parentPid");
        }

        $this->console("Now terminating...");

        return $status;
    }

    protected function __removeFile($file) {
        if (file_exists($file)) {
            $this->console("Removing file $file");
            unlink($file);
            return true;
        }
        return false;
    }

    public function console($info="", $label=null) {
        if (is_array($info)) {
            foreach ($info as $key => $printable) {
                $this->console($printable, $key);
            }
        } else {
            if (ENABLE_CONSOLE) {
                echo "\t";
                if ($label !== null)
                    echo $label . ": ";
                echo $info . "\n";
            }
            return;
        }
    }

    protected function outputResults() {
        $this->outputAsJUnitXML();
    }

    protected function outputAsJUnitXML() {

    }

    function writeToFile($filename, $string) {
        if (($fd = fopen($filename, 'w')) === false) {
            $this->console("Unable to open file for write");
            return false;
        }

        $written = 0;
        $total = strlen($string);

        while ($written < $total) {
            $rem = substr($string, $written);
            $written += fwrite($fd, $string);

            if ($written === false) {
                $this->console("Unable to write file");
                return false;
            }
        }

        fclose($fd);

        return true;
    }

    /*
     *
     * Helper function for printing out the backtrace
     */

    public function __toString() {
        return get_class();
    }

    protected $timeForChildShutdown; // seconds to wait for child to terminate
    protected $openSockets; // Used to determine what sockets are still open
    protected $childPid; // stores the pid of the child
    protected $myPid; // stores this server's pid
    protected $tempFolder; // used to store any temporary information such as the pids
    protected $timeToLive; // maximum time this runner will execute for
    protected $launcher; // stores the path to the launcher
    protected $location; // which url to launch
    protected $port; // the default port this server will use
    protected $host; // tied to local host
    protected $additionalArguments; // arguments the runner will pass to the application
    protected $file; // junit will be output to this file
    protected $outputJs; // the javascript file that the contains what port and host to contact
    protected $report; // keeps track of all the reports that are captured
    protected $reportedResults; // how many reports have came back
    protected $maxResults; // how many reports the server should expect

}



