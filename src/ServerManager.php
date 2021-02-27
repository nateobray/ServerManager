<?php
namespace obray;

class ServerManager
{
    private $instance;
    private $signalWatchers = [];
    private $childrenWatchers = [];
    private $childWatcher;

    private $maxConnections = 10;
    private $minConnections = 1;

    private $children = [];

    public function __construct(string $instance)
    {
        $this->instance = $instance;
        $this->signalWatcher[] = new \EvSignal(15, [$this, 'onStop']);
        $this->signalWatcher[] = new \EvSignal(2, [$this, 'onStop']);
        $this->signalWatcher[] = new \EvSignal(9, [$this, 'onStop']);

        $this->childWatcher = new \EvTimer(1.0, 1.0, [$this, 'watchChildren']);

        print_r("Starting loop\n");
        \EV::run();
    }

    public function watchChildren()
    {
        print_r("Watching children\n");
        if(count($this->children) >= $this->maxConnections) return;
        print_r("Forking new child\n");
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            print_r("Started child " . $pid . "\n");
            // we are the parent
            $this->children[$pid] = new \EvChild($pid, true, [$this, 'watchChild']);
        } else {
            \EV::stop();
            print_r("In Child\n");
            sleep(10);
            print_r("Shutting down child\n");
            exit(0);
            // we are the child
        }
    }

    public function onStop()
    {
        print_r("Shutting down... done!\n");
        exit();
    }

    public function watchChild($w, $revents)
    {
        print_r("child watcher\n");
        $w->stop();
        printf("Process %d exited with status %d\n", $w->rpid, $w->rstatus);   
    }
    
}