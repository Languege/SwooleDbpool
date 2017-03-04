<?php
/**
*以下代码是我基于韩天峰老师的样例修改而来的
*/
$serv = new swoole_server("127.0.0.1", 9508);
$serv->set(array(
    'worker_num' => 100,
    'task_worker_num' => 10, //MySQL连接的数量
));

function my_onReceive($serv, $fd, $from_id, $sql)
{
    //taskwait就是投递一条任务，这里直接传递SQL语句了
    //然后阻塞等待SQL完成
    $result = $serv->taskwait($sql);
    if ($result !== false) {
        list($status, $db_res) = explode(':', $result, 2);
        if ($status == 'OK') {
            //数据库操作成功了，执行业务逻辑代码，这里就自动释放掉MySQL连接的占用
            $serv->send($fd, var_export(unserialize($db_res), true) . "\n");
        } else {
            $serv->send($fd, $db_res);
        }
        return;
    } else {
        $serv->send($fd, "Error. Task timeout\n");
    }
}

function my_onTask($serv, $task_id, $from_id, $sql)
{
    static $link = null;
    if ($link == null) {
        $link = mysqli_connect("127.0.0.1", "root", "123456", "edu_univer");
        if (!$link) {
            $link = null;
            $serv->finish("ER:" . mysqli_error($link));
            return;
        }
    }
    $result = $link->query($sql);
    if (!$result) {
        $serv->finish("ER:" . mysqli_error($link));
        return;
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $serv->finish("OK:" . serialize($data));
}

function my_onFinish($serv, $data)
{
    echo "AsyncTask Finish:Connect.PID=" . posix_getpid() . PHP_EOL;
}

$serv->on('Receive', 'my_onReceive');
$serv->on('Task', 'my_onTask');
$serv->on('Finish', 'my_onFinish');
$serv->start();