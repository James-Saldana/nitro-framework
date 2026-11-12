<?php /** @var Nitro\View\ViewEngine $__engine */ ?>
<?php $__engine->extends('layouts.app'); ?>
<?php $__engine->start('content'); ?>
<h1>Sign in</h1>
<?php if (! empty($error)): ?>
    <p role="alert"><?php echo \Nitro\View\ViewEngine::escape($error); ?></p>
<?php endif; ?>
<form method="post" action="/auth/login">
    <label>Email <input type="email" name="email" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button type="submit">Sign in</button>
</form>
<?php $__engine->stop(); ?>
