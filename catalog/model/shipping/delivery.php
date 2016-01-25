<?php
class ModelShippingDelivery extends Model {
  function getQuote($address) {
    $this->load->language('shipping/delivery');

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('delivery_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

    if (!$this->config->get('delivery_geo_zone_id')) {
      $status = true;
    } elseif ($query->num_rows) {
      $status = true;
    } else {
      $status = false;
    }

    $method_data = array();

    if ($status) {
      $quote_data = array();

      // delivery types
      $arrDeliveryMarkup = array(
         'next_day' => 1
        ,'same_day' => 1.5
        ,'expedite' => 2
      );
      
      // build shipping address
      $shipping_addr = '';
      foreach ( array('address_1','address_2','city','zone') as $key )
      if ( strlen($address[$key]) > 0 )
        $shipping_addr .= ( strlen($shipping_addr) > 0 ? ', ' : '' ). $address[$key];
      
      $status = false;
      $apikey = $this->config->get('delivery_api_key');
      $origin_addr = $this->config->get('delivery_origin_addr');
      
      if ($this->config->get('delivery_dist_api_debug'))
        $this->log->write("DELIVERY ORIGIN: ". $origin_addr);

      $request = '';
      if ( $apikey and $origin_addr )
      {
        $apiurl = "https://maps.googleapis.com/maps/api/distancematrix/json?";
        $request = "origins=". urlencode($origin_addr);
        $request .= "&destinations=". urlencode($shipping_addr);
        $request .= "&key=". $apikey;
      }
      
      if ( $apikey and $request )
      {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $apiurl . $request);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);

        // strip reg, trade and ** out, updated 9-11-2013
        $result = str_replace('&amp;lt;sup&amp;gt;&amp;#174;&amp;lt;/sup&amp;gt;', '', $result);
        $result = str_replace('&amp;lt;sup&amp;gt;&amp;#8482;&amp;lt;/sup&amp;gt;', '', $result);
        $result = str_replace('&amp;lt;sup&amp;gt;&amp;#174;&amp;lt;/sup&amp;gt;', '', $result);

        $result = str_replace('**', '', $result);
        $result = str_replace("\r\n", '', $result);
        $result = str_replace('\"', '"', $result);

        $arrResult = json_decode($result, true);

        if ($result) {
          if ($this->config->get('delivery_dist_api_debug')) {
            $this->log->write("DELIVERY DISTANCE API DATA SENT: " . $request);
            $this->log->write("DELIVERY DISTANCE API DATA RECV: " . $result);
          }
        }
        
        if ( $arrResult['status'] == 'OK' and count($arrResult['rows']) > 0 )
        {
          $status = true;
          $dist_dist = $arrResult['rows'][0]['elements'][0]['distance']['text'];
          $dist_meters = $arrResult['rows'][0]['elements'][0]['distance']['value'];
          $dist_miles = $dist_meters * 0.00062137;
          $dur_sec = $arrResult['rows'][0]['elements'][0]['duration']['value'];
          $dur_min = $dur_sec / 60;
          $dur_min += $this->config->get('delivery_staging_min');
          $dur_min += $this->config->get('delivery_dropoff_min');
          $dur_hr = $dur_min / 60;
          $dur_dur = $arrResult['rows'][0]['elements'][0]['duration']['text'];
          $dist_cost = $this->config->get('delivery_mile_cost');
          $dur_cost = $this->config->get('delivery_labor_cost_hr');
          $cost_dist = $dist_cost * $dist_miles;
          $cost_dur = $dur_cost * $dur_hr;
          $this->log->write("DELIVERY DISTANCE COST: ". sprintf('%f * %f = %f',$dist_cost, $dist_miles, $cost_dist) );
          $this->log->write("DELIVERY DURATION COST: ". sprintf('%f * %f = %f',$dur_cost, $dur_hr, $cost_dur) );
          $cost = $cost_dist + $cost_dur;
        }
      }
      
      if ( $status )
      foreach( $arrDeliveryMarkup as $key => $val )
      $quote_data[$key] = array(
        'code'         => 'delivery.'.$key,
        'title'        => $this->language->get('code_'.$key) .' '. $this->language->get('text_description'),
        'cost'         => $cost * $val,
        'tax_class_id' => $this->config->get('delivery_tax_class_id'),
        'text'         => $this->currency->format($this->tax->calculate($cost * $val, $this->config->get('delivery_tax_class_id'), $this->config->get('config_tax')))
      );

      if ( $status )
      $method_data = array(
        'code'       => 'delivery',
        'title'      => $this->language->get('text_title'),
        'quote'      => $quote_data,
        'sort_order' => $this->config->get('delivery_sort_order'),
        'error'      => false
      );
    }

    if ( $this->config->get('delivery_dist_api_debug') and ! $status )
      $this->log->write("DELIVERY STATUS: FALSE");

    return ( $status ? $method_data : array() );
  }
}
