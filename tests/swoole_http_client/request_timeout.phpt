--TEST--
swoole_http_client: request timeout

--SKIPIF--
<?php require  __DIR__ . '/../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';

$pm = new ProcessManager;

$pm->parentFunc = function ($pid) use ($pm)
{
    $cli = new swoole_http_client('127.0.0.1', $pm->getFreePort());
    $cli->get('/', function ($c) {
        assert($c->statusCode == -2);
        assert($c->body == "");
    });
    swoole\event::wait();
};

$pm->childFunc = function () use ($pm)
{
    $serv = new Swoole\Server('127.0.0.1', $pm->getFreePort(), SWOOLE_BASE);
    $serv->set(array(
        'worker_num' => 1,
        'log_file' => '/dev/null',
    ));
    $serv->on('connect', function ($serv, $fd){
        //echo "Client: Connect.\n";
    });
    $serv->on('receive', function ($serv, $fd, $rid, $data) {
        sleep(1);
        $serv->shutdown();
    });
    $serv->on('close', function ($serv, $fd) {
//        echo "Client: Close.\n";
    });
    $serv->on('WorkerStart', function ($server) use ($pm) {
        $pm->wakeup();
    });
    $serv->start();
};

$pm->childFirst();
$pm->run();
?>
--EXPECT--
