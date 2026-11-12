<?php /** @var Nitro\View\ViewEngine $__engine */ ?>
<?php $__engine->extends('layouts.app'); ?>
<?php $__engine->start('content'); ?>
<h1>Hello, <?php echo \Nitro\View\ViewEngine::escape($name); ?>!</h1>
<p>Welcome to Nitro, a micro-framework for modern PHP.</p>
<?php $__engine->stop(); ?>
