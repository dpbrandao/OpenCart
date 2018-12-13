<?php
class ControllerExtensionShippingCorreios extends Controller {
	private $error = array(); 
	
	public function index() {   
		$this->load->language('extension/shipping/correios');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_correios', $this->request->post);		
			
			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_select_all'] = $this->language->get('text_select_all');
		$data['text_unselect_all'] = $this->language->get('text_unselect_all');		
		$data['text_sedex'] = $this->language->get('text_sedex');
		$data['text_sedex_cobrar'] = $this->language->get('text_sedex_cobrar');
		$data['text_pac'] = $this->language->get('text_pac');
		$data['text_sedex_10'] = $this->language->get('text_sedex_10');
		$data['text_esedex'] = $this->language->get('text_esedex');
		$data['text_sedex_12'] = $this->language->get('text_sedex_12');
		$data['text_pac_grandes_formatos'] = $this->language->get('text_pac_grandes_formatos');
		$data['text_pac_cobrar'] = $this->language->get('text_pac_cobrar');
		$data['text_total'] = $this->language->get('text_total');
		
		$data['text_sedex_cobrar_contrato'] = $this->language->get('text_sedex_cobrar_contrato');
		$data['text_sedex_hoje'] = $this->language->get('text_sedex_hoje');
		$data['text_sedex_contrato_40096'] = $this->language->get('text_sedex_contrato_40096');
		$data['text_sedex_contrato_40436'] = $this->language->get('text_sedex_contrato_40436');
		$data['text_sedex_contrato_40444'] = $this->language->get('text_sedex_contrato_40444');
		$data['text_sedex_contrato_40568'] = $this->language->get('text_sedex_contrato_40568');
		$data['text_sedex_contrato_40606'] = $this->language->get('text_sedex_contrato_40606');
		$data['text_pac_contrato'] = $this->language->get('text_pac_contrato');
		$data['text_esedex_prioritario'] = $this->language->get('text_esedex_prioritario');
		$data['text_esedex_express'] = $this->language->get('text_esedex_express');
		$data['text_esedex_grupo1'] = $this->language->get('text_esedex_grupo1');
		$data['text_esedex_grupo2'] = $this->language->get('text_esedex_grupo2');
		$data['text_esedex_grupo3'] = $this->language->get('text_esedex_grupo3');

		$data['entry_cost'] = $this->language->get('entry_cost');
		$data['entry_tax_class'] = $this->language->get('entry_tax_class');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_servicos'] = $this->language->get('entry_servicos');
		$data['entry_prazo_adicional'] = $this->language->get('entry_prazo_adicional');
		$data['entry_contrato'] = $this->language->get('entry_contrato');
		$data['entry_contrato_codigo'] = $this->language->get('entry_contrato_codigo');
		$data['entry_contrato_senha'] = $this->language->get('entry_contrato_senha');
		
		$data['help_mao_propria'] = $this->language->get('help_mao_propria');
		$data['help_aviso_recebimento'] = $this->language->get('help_aviso_recebimento');
		$data['help_declarar_valor'] = $this->language->get('help_declarar_valor');
		$data['help_adicional'] = $this->language->get('help_adicional');
		$data['help_servicos'] = $this->language->get('help_servicos');
		$data['help_prazo_adicional'] = $this->language->get('help_prazo_adicional');
		$data['help_contrato'] = $this->language->get('help_contrato');
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		
		$data['tab_general'] = $this->language->get('tab_general');
		
		$data['entry_postcode']= $this->language->get('entry_postcode');
		$data['entry_mao_propria']= $this->language->get('entry_mao_propria');
		$data['entry_aviso_recebimento']= $this->language->get('entry_aviso_recebimento');
		$data['entry_declarar_valor']= $this->language->get('entry_declarar_valor');
		$data['entry_adicional']= $this->language->get('entry_adicional');
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		if (isset($this->error['postcode'])) {
			$data['error_postcode'] = $this->error['postcode'];
		} else {
			$data['error_postcode'] = '';
		}

		$data['breadcrumbs'] = array();
   		
   		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
   		);
		
   		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'), //pode ser aqui
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
   		);
		
   		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/correios', 'user_token=' . $this->session->data['user_token'], true)
   		);
		
   		$data['action'] = $this->url->link('extension/shipping/correios', 'user_token=' . $this->session->data['user_token'], true);
   		
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);
		
		
		if (isset($this->request->post['shipping_correios_status'])) {
			$data['shipping_correios_status'] = $this->request->post['shipping_correios_status'];
		} else {
			$data['shipping_correios_status'] = $this->config->get('shipping_correios_status');
		}
		
		if (isset($this->request->post['shipping_correios_tax_class_id'])) {
			$data['shipping_correios_tax_class_id'] = $this->request->post['shipping_correios_tax_class_id'];
		} else {
			$data['shipping_correios_tax_class_id'] = $this->config->get('shipping_correios_tax_class_id');
		}
		if (isset($this->request->post['shipping_correios_geo_zone_id'])) {
			$data['shipping_correios_geo_zone_id'] = $this->request->post['shipping_correios_geo_zone_id'];
		} else {
			$data['shipping_correios_geo_zone_id'] = $this->config->get('shipping_correios_geo_zone_id');
		}
		if (isset($this->request->post['shipping_correios_postcode'])) {
			$data['shipping_correios_postcode'] = $this->request->post['shipping_correios_postcode'];
		} else {
			$data['shipping_correios_postcode'] = $this->config->get('shipping_correios_postcode');
		}
		
		if (isset($this->request->post['shipping_correios_mao_propria'])) {
			$data['shipping_correios_mao_propria'] = $this->request->post['shipping_correios_mao_propria'];
		} else {
			$data['shipping_correios_mao_propria'] = $this->config->get('shipping_correios_mao_propria');
		}
		if (isset($this->request->post['shipping_correios_aviso_recebimento'])) {
			$data['shipping_correios_aviso_recebimento'] = $this->request->post['shipping_correios_aviso_recebimento'];
		} else {
			$data['shipping_correios_aviso_recebimento'] = $this->config->get('shipping_correios_aviso_recebimento');
		}
		if (isset($this->request->post['shipping_correios_declarar_valor'])) {
			$data['shipping_correios_declarar_valor'] = $this->request->post['shipping_correios_declarar_valor'];
		} else {
			$data['shipping_correios_declarar_valor'] = $this->config->get('shipping_correios_declarar_valor');
		}
		if (isset($this->request->post['shipping_correios_adicional'])) {
			$data['shipping_correios_adicional'] = $this->request->post['shipping_correios_adicional'];
		} else {
			$data['shipping_correios_adicional'] = $this->config->get('shipping_correios_adicional');
		}
		if (isset($this->request->post['shipping_correios_prazo_adicional'])) {
			$data['shipping_correios_prazo_adicional'] = $this->request->post['shipping_correios_prazo_adicional'];
		} else {
			$data['shipping_correios_prazo_adicional'] = $this->config->get('shipping_correios_prazo_adicional');
		}
		if (isset($this->request->post['shipping_correios_contrato_codigo'])) {
			$data['shipping_correios_contrato_codigo'] = $this->request->post['shipping_correios_contrato_codigo'];
		} else {
			$data['shipping_correios_contrato_codigo'] = $this->config->get('shipping_correios_contrato_codigo');
		}
		if (isset($this->request->post['shipping_correios_contrato_senha'])) {
			$data['shipping_correios_contrato_senha'] = $this->request->post['shipping_correios_contrato_senha'];
		} else {
			$data['shipping_correios_contrato_senha'] = $this->config->get('shipping_correios_contrato_senha');
		}						

		if (isset($this->request->post['shipping_correios_41106'])) {
			$data['shipping_correios_41106'] = $this->request->post['shipping_correios_41106'];
		} else {
			$data['shipping_correios_41106'] = $this->config->get('shipping_correios_41106');
		}
		
		if (isset($this->request->post['shipping_correios_total_41106'])) {
			$data['shipping_correios_total_41106'] = $this->request->post['shipping_correios_total_41106'];
		} else {
			$data['shipping_correios_total_41106'] = $this->config->get('shipping_correios_total_41106');
		}		
		
		if (isset($this->request->post['shipping_correios_40010'])) {
			$data['shipping_correios_40010'] = $this->request->post['shipping_correios_40010'];
		} else {
			$data['shipping_correios_40010'] = $this->config->get('shipping_correios_40010');
		}

		if (isset($this->request->post['shipping_correios_total_40010'])) {
			$data['shipping_correios_total_40010'] = $this->request->post['shipping_correios_total_40010'];
		} else {
			$data['shipping_correios_total_40010'] = $this->config->get('shipping_correios_total_40010');
		}
		
		if (isset($this->request->post['shipping_correios_40045'])) {
			$data['shipping_correios_40045'] = $this->request->post['shipping_correios_40045'];
		} else {
			$data['shipping_correios_40045'] = $this->config->get('shipping_correios_40045');
		}
		
		if (isset($this->request->post['shipping_correios_total_40045'])) {
			$data['shipping_correios_total_40045'] = $this->request->post['shipping_correios_total_40045'];
		} else {
			$data['shipping_correios_total_40045'] = $this->config->get('shipping_correios_total_40045');
		}		
		
		if (isset($this->request->post['shipping_correios_40215'])) {
			$data['shipping_correios_40215'] = $this->request->post['shipping_correios_40215'];
		} else {
			$data['shipping_correios_40215'] = $this->config->get('shipping_correios_40215');
		}
		
		if (isset($this->request->post['shipping_correios_total_40215'])) {
			$data['shipping_correios_total_40215'] = $this->request->post['shipping_correios_total_40215'];
		} else {
			$data['shipping_correios_total_40215'] = $this->config->get('shipping_correios_total_40215');
		}		

		if (isset($this->request->post['shipping_correios_81019'])) {
			$data['shipping_correios_81019'] = $this->request->post['shipping_correios_81019'];
		} else {
			$data['shipping_correios_81019'] = $this->config->get('shipping_correios_81019');
		}
		
		if (isset($this->request->post['shipping_correios_total_81019'])) {
			$data['shipping_correios_total_81019'] = $this->request->post['shipping_correios_total_81019'];
		} else {
			$data['shipping_correios_total_81019'] = $this->config->get('shipping_correios_total_81019');
		}		
		
		// sedex a cobrar com contrato
		if (isset($this->request->post['shipping_correios_40126'])) {
			$data['shipping_correios_40126'] = $this->request->post['shipping_correios_40126'];
		} else {
			$data['shipping_correios_40126'] = $this->config->get('shipping_correios_40126');
		}

		if (isset($this->request->post['shipping_correios_total_40126'])) {
			$data['shipping_correios_total_40126'] = $this->request->post['shipping_correios_total_40126'];
		} else {
			$data['shipping_correios_total_40126'] = $this->config->get('shipping_correios_total_40126');
		}				
		
		// sedex hoje
		if (isset($this->request->post['shipping_correios_40290'])) {
			$data['shipping_correios_40290'] = $this->request->post['shipping_correios_40290'];
		} else {
			$data['shipping_correios_40290'] = $this->config->get('shipping_correios_40290');
		}
		
		if (isset($this->request->post['shipping_correios_total_40290'])) {
			$data['shipping_correios_total_40290'] = $this->request->post['shipping_correios_total_40290'];
		} else {
			$data['shipping_correios_total_40290'] = $this->config->get('shipping_correios_total_40290');
		}		
		
		// sedex com contrato
		if (isset($this->request->post['shipping_correios_40096'])) {
			$data['shipping_correios_40096'] = $this->request->post['shipping_correios_40096'];
		} else {
			$data['shipping_correios_40096'] = $this->config->get('shipping_correios_40096');
		}
		
		if (isset($this->request->post['shipping_correios_total_40096'])) {
			$data['shipping_correios_total_40096'] = $this->request->post['shipping_correios_total_40096'];
		} else {
			$data['shipping_correios_total_40096'] = $this->config->get('shipping_correios_total_40096');
		}		

		// sedex com contrato
		if (isset($this->request->post['shipping_correios_40436'])) {
			$data['shipping_correios_40436'] = $this->request->post['shipping_correios_40436'];
		} else {
			$data['shipping_correios_40436'] = $this->config->get('shipping_correios_40436');
		}

		if (isset($this->request->post['shipping_correios_total_40436'])) {
			$data['shipping_correios_total_40436'] = $this->request->post['shipping_correios_total_40436'];
		} else {
			$data['shipping_correios_total_40436'] = $this->config->get('shipping_correios_total_40436');
		}		
		
		// sedex com contrato
		if (isset($this->request->post['shipping_correios_40444'])) {
			$data['shipping_correios_40444'] = $this->request->post['shipping_correios_40444'];
		} else {
			$data['shipping_correios_40444'] = $this->config->get('shipping_correios_40444');
		}
		
		if (isset($this->request->post['shipping_correios_total_40444'])) {
			$data['shipping_correios_total_40444'] = $this->request->post['shipping_correios_total_40444'];
		} else {
			$data['shipping_correios_total_40444'] = $this->config->get('shipping_correios_total_40444');
		}		
		
		// sedex com contrato
		if (isset($this->request->post['shipping_correios_40568'])) {
			$data['shipping_correios_40568'] = $this->request->post['shipping_correios_40568'];
		} else {
			$data['shipping_correios_40568'] = $this->config->get('shipping_correios_40568');
		}
		
		if (isset($this->request->post['shipping_correios_total_40568'])) {
			$data['shipping_correios_total_40568'] = $this->request->post['shipping_correios_total_40568'];
		} else {
			$data['shipping_correios_total_40568'] = $this->config->get('shipping_correios_total_40568');
		}		
		
		// sedex com contrato
		if (isset($this->request->post['shipping_correios_40606'])) {
			$data['shipping_correios_40606'] = $this->request->post['shipping_correios_40606'];
		} else {
			$data['shipping_correios_40606'] = $this->config->get('shipping_correios_40606');
		}
		
		if (isset($this->request->post['shipping_correios_total_40606'])) {
			$data['shipping_correios_total_40606'] = $this->request->post['shipping_correios_total_40606'];
		} else {
			$data['shipping_correios_total_40606'] = $this->config->get('shipping_correios_total_40606');
		}		
		
		// pac com contrato
		if (isset($this->request->post['shipping_correios_41068'])) {
			$data['shipping_correios_41068'] = $this->request->post['shipping_correios_41068'];
		} else {
			$data['shipping_correios_41068'] = $this->config->get('shipping_correios_41068');
		}
		
		if (isset($this->request->post['shipping_correios_total_41068'])) {
			$data['shipping_correios_total_41068'] = $this->request->post['shipping_correios_total_41068'];
		} else {
			$data['shipping_correios_total_41068'] = $this->config->get('shipping_correios_total_41068');
		}		
		
		// e-sedex prioritário
		if (isset($this->request->post['shipping_correios_81027'])) {
			$data['shipping_correios_81027'] = $this->request->post['shipping_correios_81027'];
		} else {
			$data['shipping_correios_81027'] = $this->config->get('shipping_correios_81027');
		}
		
		if (isset($this->request->post['shipping_correios_total_81027'])) {
			$data['shipping_correios_total_81027'] = $this->request->post['shipping_correios_total_81027'];
		} else {
			$data['shipping_correios_total_81027'] = $this->config->get('shipping_correios_total_81027');
		}		
		
		// e-sedex express
		if (isset($this->request->post['shipping_correios_81035'])) {
			$data['shipping_correios_81035'] = $this->request->post['shipping_correios_81035'];
		} else {
			$data['shipping_correios_81035'] = $this->config->get('shipping_correios_81035');
		}
		
		if (isset($this->request->post['shipping_correios_total_81035'])) {
			$data['shipping_correios_total_81035'] = $this->request->post['shipping_correios_total_81035'];
		} else {
			$data['shipping_correios_total_81035'] = $this->config->get('shipping_correios_total_81035');
		}		
		
		// e-sedex grupo 1
		if (isset($this->request->post['shipping_correios_81868'])) {
			$data['shipping_correios_81868'] = $this->request->post['shipping_correios_81868'];
		} else {
			$data['shipping_correios_81868'] = $this->config->get('shipping_correios_81868');
		}
		
		if (isset($this->request->post['shipping_correios_total_81868'])) {
			$data['shipping_correios_total_81868'] = $this->request->post['shipping_correios_total_81868'];
		} else {
			$data['shipping_correios_total_81868'] = $this->config->get('shipping_correios_total_81868');
		}		
		
		// e-sedex grupo 2
		if (isset($this->request->post['shipping_correios_81833'])) {
			$data['shipping_correios_81833'] = $this->request->post['shipping_correios_81833'];
		} else {
			$data['shipping_correios_81833'] = $this->config->get('shipping_correios_81833');
		}
		
		if (isset($this->request->post['shipping_correios_total_81833'])) {
			$data['shipping_correios_total_81833'] = $this->request->post['shipping_correios_total_81833'];
		} else {
			$data['shipping_correios_total_81833'] = $this->config->get('shipping_correios_total_81833');
		}		
		
		// e-sedex grupo 3
		if (isset($this->request->post['shipping_correios_81850'])) {
			$data['shipping_correios_81850'] = $this->request->post['shipping_correios_81850'];
		} else {
			$data['shipping_correios_81850'] = $this->config->get('shipping_correios_81850');
		}
		
		if (isset($this->request->post['shipping_correios_total_81850'])) {
			$data['shipping_correios_total_81850'] = $this->request->post['shipping_correios_total_81850'];
		} else {
			$data['shipping_correios_total_81850'] = $this->config->get('shipping_correios_total_81850');
		}		
		
		// PAC Grandes Formatos
		if (isset($this->request->post['shipping_correios_41300'])) {
			$data['shipping_correios_41300'] = $this->request->post['shipping_correios_41300'];
		} else {
			$data['shipping_correios_41300'] = $this->config->get('shipping_correios_41300');
		}
		
		if (isset($this->request->post['shipping_correios_total_41300'])) {
			$data['shipping_correios_total_41300'] = $this->request->post['shipping_correios_total_41300'];
		} else {
			$data['shipping_correios_total_41300'] = $this->config->get('shipping_correios_total_41300');
		}		

		// PAC a Cobrar
		if (isset($this->request->post['shipping_correios_41262'])) {
			$data['shipping_correios_41262'] = $this->request->post['shipping_correios_41262'];
		} else {
			$data['shipping_correios_41262'] = $this->config->get('shipping_correios_41262');
		}
		
		if (isset($this->request->post['shipping_correios_total_41262'])) {
			$data['shipping_correios_total_41262'] = $this->request->post['shipping_correios_total_41262'];
		} else {
			$data['shipping_correios_total_41262'] = $this->config->get('shipping_correios_total_41262');
		}		

		// Sedex 12
		if (isset($this->request->post['shipping_correios_40169'])) {
			$data['shipping_correios_40169'] = $this->request->post['shipping_correios_40169'];
		} else {
			$data['shipping_correios_40169'] = $this->config->get('shipping_correios_40169');
		}
		
		if (isset($this->request->post['shipping_correios_total_40169'])) {
			$data['shipping_correios_total_40169'] = $this->request->post['shipping_correios_total_40169'];
		} else {
			$data['shipping_correios_total_40169'] = $this->config->get('shipping_correios_total_40169');
		}				
		
		if (isset($this->request->post['shipping_correios_sort_order'])) {
			$data['shipping_correios_sort_order'] = $this->request->post['shipping_correios_sort_order'];
		} else {
			$data['shipping_correios_sort_order'] = $this->config->get('shipping_correios_sort_order');
		}
		$this->load->model('localisation/tax_class');
		
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		
		$this->load->model('localisation/geo_zone');
		
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/shipping/correios', $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/correios')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}


		return !$this->error;
	}
}
?>
