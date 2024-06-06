<?php

class CLI_Bpm_Test {
    static function test() {
        // 开发所做的工作 就是通过代码进行流转 具体工作流的定义都交给相关学校的负责人来处理
        require __DIR__ . '/../engine.php';
        $engine = \Gini\BPM\Engine::of('camunda');
        $process = $engine->process('generatedFormsQuickstart');
        // 初始化对应的流程，传递流程的键名
        // 查询接口中所有的count都具有BUG
        var_dump($process);
        die;

        /* $result = $process->start([
            'firstname' => 'jipeng',
            'lastname' => 'huang',
            'netIncome' => '',
        ]); 
        开启一个流程，可以根据具体的表单进行值传递，也可以不传 单独开启流程
        */
        $instanceId = '3a882ce4-f46f-11e8-9db5-0242ac112a0b';

        $processInstance = $engine->processInstance($instanceId);

        /*
        $token = $engine->searchTasks([
            'processInstance' => '3a882ce4-f46f-11e8-9db5-0242ac112a0b',
            'process' => 'generatedFormsQuickstart',
        ])->token; // 批量查询 task

        $tasks = $engine->getTasks($token, 0, 20);*/
        $task = $engine->task('3a8bfd7e-f46f-11e8-9db5-0242ac112a0b'); // 可以获取到 对应的task

        // 当一个节点完成的时候，需要进行complete
        $task->complete([
            'firstname' => 'jipeng',
            'lastname' => 'huang',
            'netIncome' => 100,
        ]);

        var_dump($processInstance->getData()); // 可以获取到完成状态
    }
}
