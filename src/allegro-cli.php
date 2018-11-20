<?php

require  __DIR__ . '/../vendor/autoload.php';


if(!is_file('client_secrets.json')) {
    echo "Wygeneruj kody aplikacji na https://apps.developer.allegro.pl/
Wybierz opcję: 'Aplikacja będzie działać w środowisku bez dostępu do przeglądarki albo klawiatury (np. aplikacja konsolowa albo na urządzeniu typu telewizor)'

Utwórz plik 'client_secrets.json' w tym katalogu i uzupełnij według wzoru:

{
    \"client_id\": \"CLIENT_ID\",
    \"client_secret\": \"CLIENT_SECRET\"
}

";
    exit(1);


} else {

    $client = new GuzzleHttp\Client();

    $secrets = json_decode(file_get_contents('client_secrets.json'));

if(!is_file('authorization.json')) {

    if(!is_file('device_code.json')) {

        $response = $client->request('POST', 'https://allegro.pl/auth/oauth/device', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($secrets->client_id . ':' . $secrets->client_secret)
            ],
            'form_params' => [
                'client_id' => $secrets->client_id,
            ]
        ]);

        $codes = json_decode($response->getBody()->getContents());

        echo PHP_EOL;
        echo 'Wpisz poniższy adres do przeglądarki i dodaj kod aplikacji', PHP_EOL;
        echo $codes->verification_uri_complete , PHP_EOL, PHP_EOL;
        echo 'Następnie uruchom apliację ponownie', PHP_EOL, PHP_EOL;
            
        file_put_contents('device_code.json', json_encode($codes));

        exit(1);

    } else {
        $codes = json_decode(file_get_contents('device_code.json'));
    }

    $response = $client->request('POST', 'https://allegro.pl/auth/oauth/token', [
        'query' => [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'device_code' => $codes->device_code,
        ],
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($secrets->client_id . ':' . $secrets->client_secret)
        ]
    ]);

    $authorization = json_decode($response->getBody()->getContents());
    $authorization->expires_at = time() + $authorization->expires_in;

    file_put_contents('authorization.json', json_encode($authorization));

} else {
    $authorization = json_decode(file_get_contents('authorization.json'));
}

if ($authorization->expires_at < time()) {

    $response = $client->request('POST', 'https://allegro.pl/auth/oauth/token', [
        'query' => [
            'grant_type' => 'refresh_token',
            'refresh_token' => $authorization->refresh_token,
//          'redirect_uri' => 'http://exemplary.redirect.uri',
        ],
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($secrets->client_id . ':' . $secrets->client_secret)
        ]
    ]);

    $authorization = json_decode($response->getBody()->getContents());
    $authorization->expires_at = time() + $authorization->expires_in;

    file_put_contents('authorization.json', json_encode($authorization));
}

}

echo 'READY', PHP_EOL;