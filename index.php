<?php
// Simple real-time health page for Pusher (DEV TEST)

require_once __DIR__ . '/vendor/autoload.php';

// Load .env safely if available
if (class_exists('Dotenv\\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

// Helper for env fallback
function env($key, $default = null) {
    $value = getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : $value;
}

$pusherKey     = env('PUSHER_KEY', '11bcc45e672f6bd4eb1a');
$pusherCluster = env('PUSHER_CLUSTER', 'ap1');
$channel       = env('PUSHER_DEFAULT_CHANNEL', 'my-channel');
$event         = 'my-event';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pusher Test</title>

  <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

  <script>
    Pusher.logToConsole = true;

    const pusher = new Pusher("<?= htmlspecialchars($pusherKey, ENT_QUOTES, 'UTF-8'); ?>", {
      cluster: "<?= htmlspecialchars($pusherCluster, ENT_QUOTES, 'UTF-8'); ?>"
    });

    const channel = pusher.subscribe("<?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8'); ?>");

    channel.bind("<?= htmlspecialchars($event, ENT_QUOTES, 'UTF-8'); ?>", function (data) {
      console.log("Event received:", data);
      alert(JSON.stringify(data, null, 2));
    });
  </script>
</head>

<body>
  <h1>Pusher Test</h1>

  <p>
    Listening on channel:
    <code><?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8'); ?></code>
  </p>

  <p>
    Event name:
    <code><?= htmlspecialchars($event, ENT_QUOTES, 'UTF-8'); ?></code>
  </p>
</body>
</html>