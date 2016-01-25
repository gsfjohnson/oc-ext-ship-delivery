<?php
class ControllerShippingDelivery extends Controller
{
  private $error = array();

  public function index()
  {
    $this->load->language('shipping/delivery');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('delivery', $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL'));
    }

    $arrKeys = array(
       'heading_title'
      ,'text_edit'
      ,'text_enabled'
      ,'text_disabled'
      ,'text_all_zones'
      ,'text_none'
      ,'entry_mile_cost'
      ,'entry_labor_cost_hr'
      ,'entry_staging_min'
      ,'entry_dropoff_min'
      ,'entry_origin_addr'
      ,'entry_api_key'
      ,'entry_dist_api_debug'
      ,'entry_tax_class'
      ,'entry_geo_zone'
      ,'entry_status'
      ,'entry_sort_order'
      ,'button_save'
      ,'button_cancel'
    );
    foreach ( $arrKeys as $key )
      $data[$key] = $this->language->get($key);
        
    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_shipping'),
      'href' => $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('shipping/delivery', 'token=' . $this->session->data['token'], 'SSL')
    );

    $data['action'] = $this->url->link('shipping/delivery', 'token=' . $this->session->data['token'], 'SSL');

    $data['cancel'] = $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL');

    $arrKeys = array(
       'delivery_labor_cost_hr'
      ,'delivery_mile_cost'
      ,'delivery_staging_min'
      ,'delivery_dropoff_min'
      ,'delivery_api_key'
      ,'delivery_dist_api_debug'
      ,'delivery_origin_addr'
      ,'delivery_geo_zone_id'
      ,'delivery_status'
      ,'delivery_sort_order'
    );
    foreach ( $arrKeys as $key )
    {
      if (isset($this->request->post[$key])) {
        $data[$key] = $this->request->post[$key];
      } else {
        $data[$key] = $this->config->get($key);
      }
    }

    if (isset($this->request->post['delivery_tax_class_id'])) {
      $data['delivery_tax_class_id'] = $this->request->post['delivery_tax_class_id'];
    } else {
      $data['delivery_tax_class_id'] = $this->config->get('delivery_tax_class_id');
    }

    $this->load->model('localisation/tax_class');

    $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

    $this->load->model('localisation/geo_zone');

    $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('shipping/delivery.tpl', $data));
  }

  protected function validate()
  {
    if (!$this->user->hasPermission('modify', 'shipping/delivery')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    return !$this->error;
  }
}
