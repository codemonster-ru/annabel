# Codemonster HTTP Client

Lightweight HTTP client for Annabel applications.

## Usage

```php
use Codemonster\HttpClient\HttpClient;

$response = (new HttpClient())
    ->baseUrl('https://api.example.com')
    ->acceptJson()
    ->get('/users/1');

if ($response->successful()) {
    $user = $response->json();
}
```

The default transport uses PHP streams. A custom transport can be injected for
tests or advanced integrations.
