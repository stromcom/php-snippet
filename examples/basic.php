<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Stromcom\Snippet\SnippetClientFactory;
use Stromcom\Snippet\Options\ConfOptions;
use Stromcom\Snippet\Options\UserOptions;
use Stromcom\Snippet\Options\ThreadOptions;

// Replace with your actual credentials from the STROMCOM dashboard.
$client = SnippetClientFactory::create(
    clientKey:      'your-client-key',
    clientSecret:   'your-bearer-token',
    codeHashSecret: 'your-app-secret',  // enables automatic HMAC-SHA256 + base-62 hashing
);

// ── 1. Loader ────────────────────────────────────────────────────────────────
// Place this once in <head> or just before </body> on every page.
echo $client->snippet()->getHTML();
echo "\n\n";

// ── 2. User identification ───────────────────────────────────────────────────
// Identify the currently logged-in user.
// The raw 'user-id-42' is HMAC-hashed and base-62 encoded before output,
// so your internal ID is never exposed to the browser.
echo $client->user(new UserOptions(
    code:         'user-id-42',
    name:         'Jane Doe',
    emailAddress: 'jane@example.com',
    avatarURL:    'https://example.com/avatars/jane.png',
))->getHTML();
echo "\n\n";

// ── 3. Thread ────────────────────────────────────────────────────────────────
// Embed a conversation thread into a DOM element.
echo $client->thread('#support-chat', new ThreadOptions(
    code: 'order-12345',
    name: 'Order #12345',
    url:  'https://yourapp.com/orders/12345',
))->getHTML();
echo "\n\n";

// ── 4. Notification center ───────────────────────────────────────────────────
// Embed the notification center icon into a DOM element.
echo $client->home('#notifications')->getHTML();
echo "\n\n";

// ── 5. SDK configuration (optional) ─────────────────────────────────────────
// Customise the widget behaviour. Only non-default values are emitted.
echo $client->conf(new ConfOptions(
    notificationElementPosition: 4,  // 1=top-left  2=top-right  3=bottom-right  4=bottom-left
))->getHTML();
echo "\n";
