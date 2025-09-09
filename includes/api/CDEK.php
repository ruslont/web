<?php
class CDEK {
    private $login;
    private $password;
    private $token;
    
    public function __construct($login, $password) {
        $this->login = $login;
        $this->password = $password;
        $this->authenticate();
    }
    
    private function authenticate() {
        $url = 'https://api.cdek.ru/v2/oauth/token';
        
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->login,
            'client_secret' => $this->password
        ];
        
        $response = $this->sendRequest($url, $data, false);
        $this->token = $response['access_token'] ?? null;
    }
    
    public function calculateDelivery($from, $to, $weight, $length, $width, $height) {
        $url = 'https://api.cdek.ru/v2/calculator/tariff';
        
        $data = [
            'from_location' => $from,
            'to_location' => $to,
            'packages' => [
                [
                    'weight' => $weight,
                    'length' => $length,
                    'width' => $width,
                    'height' => $height
                ]
            ]
        ];
        
        return $this->sendRequest($url, $data);
    }
    
    public function createOrder($orderData) {
        $url = 'https://api.cdek.ru/v2/orders';
        return $this->sendRequest($url, $orderData);
    }
    
    private function sendRequest($url, $data, $useToken = true) {
        $ch = curl_init($url);
        $headers = ['Content-Type: application/json'];
        
        if ($useToken && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
?>
