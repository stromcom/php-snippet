<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Stromcom\Snippet\CspPolicy;
use Stromcom\Snippet\Environment\CustomEnvironment;
use Stromcom\Snippet\Environment\Environment;
use Stromcom\Snippet\Exception\CspException;
use Stromcom\Snippet\SnippetClientFactory;

// A fresh nonce must be generated for every single response.
$nonce = base64_encode(random_bytes(16));

$client = SnippetClientFactory::create(
    clientKey:    'your-client-key',
    clientSecret: 'your-bearer-token',
    nonce:        $nonce,  // rendered as <script nonce="…"> on every generated tag
);


/** 1. Send the policy as a header */
// $client->csp() reuses the client's environment and nonce.
$policy = $client->csp();

// header($policy->getHeaderName() . ': ' . $policy->getHeaderValue());
echo $policy->getHeaderName() . ': ' . $policy->getHeaderValue() . "\n\n";


/** 2. …or render it as a <meta> tag */
echo $policy->getMetaTag() . "\n\n";


/** 3. Merge the directives into an existing policy */
// getDirectives() returns "directive => list of sources", so you can append the
// STROMCOM sources to whatever your application already allows.
$ownPolicy = [
    'default-src' => ["'self'"],
    'script-src'  => ["'self'"],
    'connect-src' => ["'self'"],
];

foreach ($policy->getDirectives() as $directive => $sources) {
    $ownPolicy[$directive] = array_values(array_unique([...$ownPolicy[$directive] ?? [], ...$sources]));
}

foreach ($ownPolicy as $directive => $sources) {
    echo $directive . ' ' . implode(' ', $sources) . ";\n";
}
echo "\n";


/** 4. The snippet carries the nonce */
echo $client->snippet()->getHTML() . "\n\n";


/** 5. A policy without a client */
// Useful when the policy is built somewhere else than the snippet (middleware, edge config…).
echo (new CspPolicy(Environment::STAGING))->getHeaderValue() . "\n\n";


/** 6. A custom environment has to state its origins */
// Only the CDN origin can be read from the loader URL; the API and the application run on
// separate hosts, so they are never guessed from it.
$environment = new CustomEnvironment(
    'https://cdn.example.com/loader.js',
    'https://example.com',       // API — the origin the widget polls
    'https://chat.example.com',  // application — the origin of the iframe
);

echo (new CspPolicy($environment))->getHeaderValue() . "\n\n";

// Without them the policy fails loudly instead of silently blocking the widget at runtime.
try {
    new CspPolicy(new CustomEnvironment('https://cdn.example.com/loader.js'));
} catch (CspException $Exception) {
    echo $Exception->getMessage() . "\n";
}
