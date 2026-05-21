<?php
// File sementara untuk debug — HAPUS setelah selesai
$info = [
    'exec_available'  => function_exists('exec') && is_callable('exec'),
    'shell_available' => function_exists('shell_exec') && is_callable('shell_exec'),
    'repo_path_exists'=> is_dir('/home/sukashaw/repositories/checklistform-ss'),
    'repo_path_alt1'  => is_dir('/home/sukashaw/checklistform-ss'),
    'repo_path_alt2'  => is_dir('/home/sukashaw/public_html/repos/checklistform-ss'),
    'git_which'       => '',
    'home_dir'        => '',
];

if ($info['exec_available']) {
    exec('which git 2>&1', $o); $info['git_which'] = implode('', $o);
    exec('echo $HOME 2>&1', $o2); $info['home_dir'] = implode('', $o2);
    exec('ls /home/sukashaw/repositories/ 2>&1', $o3); $info['repositories_ls'] = implode(', ', $o3);
}

header('Content-Type: application/json');
echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
