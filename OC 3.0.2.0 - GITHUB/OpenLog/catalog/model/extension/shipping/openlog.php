<?php
class ModelExtensionShippingOpenlog extends Model {
  function getQuote($address) {
    $this->load->language('extension/shipping/openlog');
 
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('openlog_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
 
    if (!$this->config->get('openlog_geo_zone_id')) {
      $status = true;
    } elseif ($query->num_rows) {
      $status = true;
    } else {
      $status = false;
    }
 
    $method_data = array();

    if ($status) {
      $valorTotal = $this->cart->getTotal();
      $peso = $this->cart->getWeight();
      $cep_destino = preg_replace ("/[^0-9]/", '', $address['postcode']);
      $email = $this->config->get('config_email'); 
      $produtos = json_encode($this->cart->getProducts()); 
      $api = json_decode($this->getValorFreteAPI($valorTotal, $peso, $email , $cep_destino , $produtos));
      if($api == false){
        return;
      }else{
        $quote_data = array();
        if ($api->{'prazo'} > 1){
          $quote_data['openlog'] = array(
            'code'     => 'openlog.openlog',
            'title'    => $api->{'descricao_servico'},
            'cost'     => $api->{'frete'},
            'text'     => 'Entrega em até '.$api->{'prazo'}.' dias úteis - '.$this->currency->format($api->{'frete'}, $this->session->data['currency'])
          );
        }
        else if($api->{'prazo'} == 1){
          $quote_data['openlog'] = array(
            'code'     => 'openlog.openlog',
            'title'    => $api->{'descricao_servico'},
            'cost'     => $api->{'frete'},
            'text'     => 'Entrega em até '.$api->{'prazo'}.' dia útil - '.$this->currency->format($api->{'frete'}, $this->session->data['currency'])
          );
        }
        
   
        $method_data = array(
          'code'     => 'openlog',
          'title'    => $api->{'transportadora'},
          'quote'    => $quote_data,
          'sort_order' => $this->config->get('openlog_sort_order'),
          'error'    => false
        );
      }
   
      return $method_data;
      }
      
      
  }

  public function getValorFreteAPI($valor, $peso, $email, $cep, $produtos){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://openlog.com.br/financeiro/api/Fretes/CalcularFrete",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 1,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => 'valor='.$valor.'&peso='.$peso.'&email='.$email.'&cep='.$cep.'&produtos='.$produtos.'&formato_retorno=EN'
    ));
    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if($httpcode == 200){
        return $response;
    }else{
      return false;
    }
  }

}