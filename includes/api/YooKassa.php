<?php
class YooKassa {
    private $shopId;
    private $secretKey;
    
    public function __construct($shopId, $secretKey) {
        $this->shopId = $shopId;
        $this->secretKey = $secretKey;
    }
    
    public function createPayment($orderId, $amount, $description) {
        $url = 'https://api.yookassa.ru/v3/payments';
        
        $data = [
            'amount' => [
                'value' => $amount,
                'currency' => 'RUB'
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => SITE_URL . 'payment/success'
            ],
            'description' => $description,
            'metadata' => [
                'order_id' => $orderId
            ]
        ];
        
        $response = $this->sendRequest($url, $data);
        return $response;
    }
    
    private function sendRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->shopId . ':' . $this->secretKey);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
?>
