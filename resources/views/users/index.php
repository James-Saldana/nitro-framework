<?php /** @var Nitro\View\ViewEngine $__engine */ ?>
<?php $__engine->extends('layouts.app'); ?>
<?php $__engine->start('content'); ?>
<h1>Users</h1>
<table>
    <thead><tr><th>ID</th><th>Email</th><th>Created</th></tr></thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo \Nitro\View\ViewEngine::escape($user->id); ?></td>
            <td><?php echo \Nitro\View\ViewEngine::escape($user->email); ?></td>
            <td><?php echo \Nitro\View\ViewEngine::escape($user->createdAt ?? ''); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php $__engine->stop(); ?>
