<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo \Nitro\View\ViewEngine::escape($title ?? 'Nitro'); ?></title>
</head>
<body>
    <header>
        <strong>Nitro</strong>
        <nav>
            <a href="/">Home</a>
            <a href="/users">Users</a>
        </nav>
    </header>
    <main>
        <?php echo $__engine->section('content'); ?>
    </main>
</body>
</html>
