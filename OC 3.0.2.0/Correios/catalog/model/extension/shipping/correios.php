<?php
class ModelExtensionShippingCorreios extends Model {
	
	// máximo valor declarado, em reais
	private $valor_declarado_max_sedex = 10000;
	private $valor_declarado_max_pac = 3000;
	private $valor_declarado_max = 3000;
	private $valor_declarado_min = 17;
	
	// medida padrão de cada lado da caixa, ou seja, 200cm / 3 = 66,66... (arrendondamos esse valor). onde 200 é o máximo da soma dos lados	
	// cubagem máxima da caixa, ou seja, 66,66^3
	private $cubagem_max = 296207.4163;
	
	// cubagem mínima da caixa levando em conta uma medida de 16cm de cada lado da caixa.
	private $cubagem_min = 4096;	
	
	// medida padrão de cada lado da caixa para grandes formatos, ou seja, 300cm / 3 = 100 onde 300 é o máximo da soma dos lados para grandes formatos
	// cubagem máxima da caixa para grandes formatos, ou seja, 100^3
	private $cubagem_pac_gf_max = 1000000;
	
	// esse limite do Sedex Hoje não tem em nenhum documento dos Correios e por tentativa e erro descobri um número próximo de 52cm de cada lado 
	private $cubagem_sedex_hoje_max = 140608;		
	
	private $peso_max 					= 30;	// padrão, em kg
	private $peso_pac_sedex_max 		= 30; 	// em kg
	private $peso_esedex_max 			= 15; 	// em kg
	private $peso_sedex10_12_hoje_max 	= 10; 	// em kg

	private $peso_min 					= 0.3; 	// em kg
	
	// altura, largura ou comprimento não podem ser maiores que 105cm
	private $lados_max 			= 105; // em cm	
	private $lados_pac_gf_max 	= 150; // em cm	
	
	private $nCdServico = array();
	private $servicos 	= array();
	
	private $url = '';
	
	private $quote_data = array();
	
	private $cep_destino;
	private $cep_origem;
	
	private $contrato_codigo = '';
	private $contrato_senha = '';

	private $mensagem_erro = array();	
	
	private $correios = array(
		'SEDEX'				=> '40010',
		'40010'				=> 'SEDEX',

		'SEDEX Pagamento na Entrega'	=> '40045',
		'40045'							=> 'SEDEX Pagamento na Entrega',

		'SEDEX Pagamento na Entrega - contrato'	=> '40126',
		'40126'									=> 'SEDEX Pagamento na Entrega - contrato',

		'SEDEX 10'			=> '40215',
		'40215'				=> 'SEDEX 10',
		
		'SEDEX 12'			=> '40169',
		'40169'				=> 'SEDEX 12',		

		'SEDEX Hoje'		=> '40290',
		'40290'				=> 'SEDEX Hoje',

		'SEDEX - contrato 1' => '40096',
		'40096'				 => 'SEDEX - contrato 1',

		'SEDEX - contrato 2' => '40436',
		'40436'				 => 'SEDEX - contrato 2',

		'SEDEX - contrato 3' => '40444',
		'40444'				 => 'SEDEX - contrato 3',

		'SEDEX - contrato 4' => '40568',
		'40568'				 => 'SEDEX - contrato 4',

		'SEDEX - contrato 5' => '40606',
		'40606'				 => 'SEDEX - contrato 5',
		
		'PAC'				 => '41106',
		'41106'				 => 'PAC',
		
		'PAC Pagamento na Entrega'	=> '41262',
		'41262'				 		=> 'PAC Pagamento na Entrega',		
		
		'PAC - contrato'	 => '41068',
		'41068'				 => 'PAC - contrato',
		
		'PAC Grandes Formatos - contrato'	=> '41300',
		'41300'				 				=> 'PAC Grandes Formatos - contrato',		
		
		'e-SEDEX'			 => '81019',
		'81019'				 => 'e-SEDEX',		
		
		'e-SEDEX Prioritario'	=> '81027',
		'81027'					=> 'e-SEDEX Prioritario',	
		
		'e-SEDEX Express'	 => '81035',
		'81035'				 => 'e-SEDEX Express',
		
		'e-SEDEX grupo 1'	 => '81868',
		'81868'				 => 'e-SEDEX grupo 1',
		
		'e-SEDEX grupo 2'	 => '81833',
		'81833'				 => 'e-SEDEX grupo 2',
		
		'e-SEDEX grupo 3'	 => '81850',
		'81850'				 => 'e-SEDEX grupo 3'
	);

	private function setPesoMax($peso) {
		$this->peso_max = $peso;
	}
	
	private function setCubagemMax($cubagem) {
		$this->cubagem_max = $cubagem;
	}
	
	private function setServicos($servicos) {
		$this->servicos = $servicos;
	}

	private function resetServicos() {
		$this->servicos = array();
	}

	private function setLadosMax($medida) {
		$this->lados_max = $medida;
	}

	private function setValorDecladadoMax($valor_declarado_max) {
		$this->valor_declarado_max = $valor_declarado_max;
	}

	private function setValorDecladadoMin($valor_declarado_min) {
		$this->valor_declarado_min = $valor_declarado_min;
	}	
	
	// função responsável pelo retorno à loja dos valores finais dos valores dos fretes
	public function getQuote($address) {
		
		$this->load->language('extension/shipping/correios');
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_correios_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
		
		if (!$this->config->get('shipping_correios_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}		
		
		$method_data = array();
		
		$produtos = $this->removeProdutosSemFrete($this->cart->getProducts());

		if ($status && !empty($produtos)) {
			
			// obtém só a parte numérica do CEP
			$this->cep_origem = preg_replace ("/[^0-9]/", '', $this->config->get('shipping_correios_postcode'));
			$this->cep_destino = preg_replace ("/[^0-9]/", '', $address['postcode']);
			
			// classes de serviços agrupados conforme suas propriedades. Cada classe é uma chamada ao WebService dos Correios
			$this->nCdServico['pac'] =  array(
					'peso_max' 			=> $this->peso_pac_sedex_max,
					'lados_max' 		=> $this->lados_max,
					'cubagem_max' 		=> $this->cubagem_max,
					'valor_declarado_max' 	=> $this->valor_declarado_max_pac,
					'valor_declarado_min' 	=> $this->valor_declarado_min
				);
			$this->nCdServico['sedex'] =  array(
					'peso_max' 			=> $this->peso_pac_sedex_max,
					'lados_max' 		=> $this->lados_max,
					'cubagem_max' 		=> $this->cubagem_max,
					'valor_declarado_max' 	=> $this->valor_declarado_max_sedex,
					'valor_declarado_min' 	=> $this->valor_declarado_min
				);				
			$this->nCdServico['sedex10_12_hoje'] = array(
					'peso_max' 			=> $this->peso_sedex10_12_hoje_max,
					'lados_max' 		=> $this->lados_max,
					'cubagem_max' 		=> $this->cubagem_max,
					'valor_declarado_max' 	=> $this->valor_declarado_max_sedex,
					'valor_declarado_min' 	=> $this->valor_declarado_min
				);		
			$this->nCdServico['esedex'] = array(
					'peso_max' 			=> $this->peso_esedex_max,
					'lados_max' 		=> $this->lados_max,
					'cubagem_max' 		=> $this->cubagem_max,
					'valor_declarado_max' 	=> $this->valor_declarado_max_sedex,
					'valor_declarado_min' 	=> $this->valor_declarado_min
				);	
			$this->nCdServico['pac_gf'] = array(
					'peso_max' 			=> $this->peso_pac_sedex_max,
					'lados_max' 		=> $this->lados_pac_gf_max,
					'cubagem_max' 		=> $this->cubagem_pac_gf_max,
					'valor_declarado_max' 	=> $this->valor_declarado_max_pac,
					'valor_declarado_min' 	=> $this->valor_declarado_min
				);
			$this->nCdServico['sedex_hoje'] = array(
					'peso_max' 			=> $this->peso_sedex10_12_hoje_max,
					'lados_max' 		=> $this->lados_max,
					'cubagem_max' 		=> $this->cubagem_sedex_hoje_max,
					'valor_declarado_max' 	=> $this->valor_declarado_max_sedex,
					'valor_declarado_min' 	=> $this->valor_declarado_min
				);				

			$servicos['pac'] = array();
			$servicos['sedex'] = array();				
			$servicos['sedex10_12_hoje'] = array();
			$servicos['esedex']  = array();
			$servicos['pac_gf'] = array();
			$servicos['sedex_hoje'] = array();

			// serviços sem contrato
			if($this->config->get('shipping_correios_' . $this->correios['PAC'])){
				$servicos['pac'][] = $this->correios['PAC'];
			}			
			if($this->config->get('shipping_correios_' . $this->correios['SEDEX'])){
				$servicos['sedex'][] = $this->correios['SEDEX'];
			}
			if($this->config->get('shipping_correios_' . $this->correios['SEDEX Pagamento na Entrega'])){
				$servicos['sedex'][] = $this->correios['SEDEX Pagamento na Entrega'];
			}
			if($this->config->get('shipping_correios_' . $this->correios['PAC Pagamento na Entrega'])){
				$servicos['pac'][] = $this->correios['PAC Pagamento na Entrega'];
			}			
			if($this->config->get('shipping_correios_' . $this->correios['SEDEX 10'])){
				$servicos['sedex10_12_hoje'][]= $this->correios['SEDEX 10'];
			}
			if($this->config->get('shipping_correios_' . $this->correios['SEDEX Hoje'])){
				$servicos['sedex_hoje'][]= $this->correios['SEDEX Hoje'];
			}
			if($this->config->get('shipping_correios_' . $this->correios['SEDEX 12'])){
				$servicos['sedex10_12_hoje'][] = $this->correios['SEDEX 12'];
			}			
			// serviços com contrato			
			if(trim($this->config->get('shipping_correios_contrato_codigo')) != "" && trim($this->config->get('shipping_correios_contrato_senha')) != ""){
				$this->contrato_codigo = $this->config->get('shipping_correios_contrato_codigo');
				$this->contrato_senha = $this->config->get('shipping_correios_contrato_senha');
				
				if($this->config->get('shipping_correios_' . $this->correios['SEDEX Pagamento na Entrega - contrato'])){
					$servicos['sedex'][] = $this->correios['SEDEX Pagamento na Entrega - contrato'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['SEDEX - contrato 1'])){
					$servicos['sedex'][] = $this->correios['SEDEX - contrato 1'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['SEDEX - contrato 2'])){
					$servicos['sedex'][] = $this->correios['SEDEX - contrato 2'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['SEDEX - contrato 3'])){
					$servicos['sedex'][] = $this->correios['SEDEX - contrato 3'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['SEDEX - contrato 4'])){
					$servicos['sedex'][] = $this->correios['SEDEX - contrato 4'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['SEDEX - contrato 5'])){
					$servicos['sedex'][] = $this->correios['SEDEX - contrato 5'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['PAC - contrato'])){
					$servicos['pac'][] = $this->correios['PAC - contrato'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['e-SEDEX'])){
					$servicos['esedex'][] = $this->correios['e-SEDEX'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['e-SEDEX Prioritario'])){
					$servicos['esedex'][] = $this->correios['e-SEDEX Prioritario'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['e-SEDEX Express'])){
					$servicos['esedex'][] = $this->correios['e-SEDEX Express'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['e-SEDEX grupo 1'])){
					$servicos['esedex'][] = $this->correios['e-SEDEX grupo 1'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['e-SEDEX grupo 2'])){
					$servicos['esedex'][] = $this->correios['e-SEDEX grupo 2'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['e-SEDEX grupo 3'])){
					$servicos['esedex'][] =  $this->correios['e-SEDEX grupo 3'];
				}
				if($this->config->get('shipping_correios_' . $this->correios['PAC Grandes Formatos - contrato'])){
					$servicos['pac_gf'][] = $this->correios['PAC Grandes Formatos - contrato'];
				}				
			}
			
			foreach($this->nCdServico as $classe => $info){
				
				$this->setPesoMax($info['peso_max']);
				$this->setLadosMax($info['lados_max']);
				$this->setCubagemMax($info['cubagem_max']);
				$this->setValorDecladadoMax($info['valor_declarado_max']);
				$this->setValorDecladadoMin($info['valor_declarado_min']);
				$this->setServicos($servicos[$classe]);
				
				$caixas = $this->organizarEmCaixas($produtos);
				
				// descomente a linha abaixo para visualizar em arquivos as caixas
				// file_put_contents('filename' . $classe . '.txt', print_r($caixas, true));
				
				foreach ($caixas as $caixa) {
					$this->setQuoteData($caixa);
				}
				
				$this->resetServicos();
			}
			
			// ajustes finais
			if ($this->quote_data) {
				$total_compra = $this->cart->getSubTotal();
				
				$valor_adicional = (is_numeric($this->config->get('shipping_correios_adicional'))) ? $this->config->get('shipping_correios_adicional') : 0 ;

				foreach ($this->quote_data as $codigo => $data) {
					
					// soma o valor adicional ao valor final do frete - não aplicado serviços de pagamento na entrega
					if($codigo != $this->correios['SEDEX Pagamento na Entrega'] || $codigo != $this->correios['SEDEX Pagamento na Entrega - contrato'] || $codigo != $this->correios['PAC Pagamento na Entrega']) {
						
						$new_cost = $this->quote_data[$codigo]['cost'] + ($this->quote_data[$codigo]['cost'] * ($valor_adicional/100));
						// novo custo
						$this->quote_data[$codigo]['cost'] = $new_cost;
						// novo texto
						$this->quote_data[$codigo]['text'] = $this->currency->format($this->tax->calculate($new_cost, $this->config->get('shipping_correios_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency']);
					}
					else{
						// zera o valor do frete do serviço de pagamento na entrega para evitar de ser adiconado ao valor do carrinho
						$this->quote_data[$codigo]['cost'] = 0;
					}
					// frete grátis
					if(trim($this->config->get('shipping_correios_total_' . $codigo)) != "" && $total_compra >= $this->config->get('shipping_correios_total_' . $codigo)) {
						// zera o valor
						$this->quote_data[$codigo]['cost'] = 0;
						// novo texto 
						$this->quote_data[$codigo]['text'] = $this->language->get('text_free');
					}					
					
				}				
				$method_data = array(
					'code'       => 'correios',
					'title'      => $this->language->get('text_title'),
					'quote'      => $this->quote_data,
					'sort_order' => $this->config->get('shipping_correios_sort_order'),
					'error'      => false
				);
			}
			else if(!empty($this->mensagem_erro)){
				$method_data = array(
					'code'       => 'correios',
					'title'      => $this->language->get('text_title'),
					'quote'      => $this->quote_data,
					'sort_order' => $this->config->get('shipping_correios_sort_order'),
					'error'      => implode('<br />', $this->mensagem_erro)
				);				
			}			
		}
		return $method_data;
	}
	
	// obtém os dados dos fretes para os produtos da caixa
	private function setQuoteData($caixa){

		// obtém o valor total da caixa
		$total_caixa = $this->getTotalCaixa($caixa['produtos']);
		
		if ($total_caixa < $this->valor_declarado_min) {
			$total_caixa =  $this->valor_declarado_min;
		}
		
		if ($total_caixa > $this->valor_declarado_max) {
			$total_caixa = $this->valor_declarado_max;
		}
		
		// fazendo a chamada ao site dos Correios e obtendo os dados
		$servicos = $this->getServicos($total_caixa, $caixa['peso'], $caixa['cubagem']);
	
		foreach ($servicos as $servico) {

			// o site dos Correios retornou os dados sem erros.
			$valor_frete_sem_adicionais = $servico['Valor'] - $servico['ValorAvisoRecebimento'] - $servico['ValorMaoPropria'] - $servico['ValorValorDeclarado'];
			//if($servico['Erro'] == 0 && $valor_frete_sem_adicionais > 0) {
			if($valor_frete_sem_adicionais > 0) {
	
				// subtrai do valor do frete as opções desabilitadas nas configurações do módulo - 'declarar valor' é obrigatório para Sedex e PAC Pagamento na Entrega
				$cost = ($this->config->get('shipping_correios_declarar_valor') == 'n' && ($servico['Codigo'] != $this->correios['SEDEX Pagamento na Entrega'] || $servico['Codigo'] != $this->correios['SEDEX Pagamento na Entrega - contrato'] || $servico['Codigo'] != $this->correios['PAC Pagamento na Entrega'])) ? ($servico['Valor'] - $servico['ValorValorDeclarado']) : $servico['Valor'];
				$cost = ($this->config->get('shipping_correios_aviso_recebimento') == 'n') ? ($cost - $servico['ValorAvisoRecebimento']) : $cost;
				$cost = ($this->config->get('shipping_correios_mao_propria') == 'n') ? ($cost - $servico['ValorMaoPropria']) : $cost;
	
				// o valor do frete para a caixa atual é somado ao valor total já calculado para outras caixas 
				if (isset($this->quote_data[$servico['Codigo']])) {
					$cost += $this->quote_data[$servico['Codigo']]['cost'];
				}					

				$title = sprintf($this->language->get('text_'.$servico['Codigo']), $servico['PrazoEntrega']);
				$text = $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_correios_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency']);
	
				$this->quote_data[$servico['Codigo']] = array(
					'code'         => 'correios.' . $servico['Codigo'],
					'title'        => $title,
					'cost'         => $cost,
					'tax_class_id' => $this->config->get('shipping_correios_tax_class_id'),
					'text'         => $text
				);
			}
			// grava no log de erros do OpenCart a mensagem de erro retornado pelos Correios
			else{
				$this->mensagem_erro[] = $this->correios[$servico['Codigo']].': ' . $servico['MsgErro'];
				$this->log->write($this->correios[$servico['Codigo']].': ' . $servico['MsgErro']);
			}
		}
	}
	
	// prepara a url de chamada ao site dos Correios
	private function setUrl($peso, $valor, $medida_lados){
		
		$url = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?";
		//$url = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?"; // url alternativa disponibilizada pelos Correios.
		$url .=	"nCdEmpresa=" . $this->contrato_codigo;
		$url .=	"&sDsSenha=" . $this->contrato_senha;
		$url .=	"&sCepOrigem=%s";
		$url .=	"&sCepDestino=%s";
		$url .=	"&nVlPeso=%s";
		$url .=	"&nCdFormato=1";
		$url .=	"&nVlComprimento=%s";
		$url .=	"&nVlLargura=%s";
		$url .=	"&nVlAltura=%s";
		$url .=	"&sCdMaoPropria=s";
		$url .=	"&nVlValorDeclarado=%s";
		$url .=	"&sCdAvisoRecebimento=s";
		$url .=	"&nCdServico=" . implode(',', $this->servicos);
		$url .=	"&nVlDiametro=0";
		$url .=	"&StrRetorno=xml";
		
		$this->url = sprintf($url, $this->cep_origem, $this->cep_destino, $peso, $medida_lados, $medida_lados, $medida_lados, $valor);
	}
	
	// conecta ao sites dos Correios e obtém o arquivo XML com os dados do frete
	private function getXML($url){
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);			
		
		$result = curl_exec($ch);
		
		if(!$result){
			$this->log->write(curl_error($ch));
			$this->log->write($this->language->get('error_conexao'));
			$result = curl_exec($ch);
			
			if($result){
				$this->log->write($this->language->get('text_sucesso'));
			}
			else{
				$this->log->write(curl_error($ch));
				$this->log->write($this->language->get('error_reconexao'));				
			}
		}
		
		curl_close($ch);
		
		$result = str_replace('&amp;lt;sup&amp;gt;&amp;amp;reg;&amp;lt;/sup&amp;gt;', '', $result);
		$result = str_replace('&amp;lt;sup&amp;gt;&amp;amp;trade;&amp;lt;/sup&amp;gt;', '', $result);
		$result = str_replace('**', '', $result);
		$result = str_replace("\r\n", '', $result);
		$result = str_replace('\"', '"', $result);		
		
		return $result;
	}
	
	// faz a chamada e lê os dados no arquivo XML retornado pelos Correios 
	public function getServicos($total_caixa, $peso, $cubagem){

		$dados = array();
		
		// troca o separador decimal de ponto para vírgula nos dados a serem enviados para os Correios
		$peso = ($peso >= $this->peso_min) ? $peso : $this->peso_min;
		$peso = str_replace('.', ',', $peso);
		
		// total é arredondado pois algumas vezes o WebService dos Correios não aceita centavos
		$valor = round($total_caixa);
		
		// medida dos lados da caixa é a raiz cúbica da cubagem
		$medida_lados = ($cubagem >= $this->cubagem_min) ? $this->raizCubica($cubagem) : $this->raizCubica($this->cubagem_min);
		$medida_lados = str_replace('.', ',', $medida_lados);
		
		// ajusta a url de chamada
		$this->setUrl($peso, $valor, $medida_lados);
		
		// habilite pra ver no log de erros a url com todos os parâmetros enviados para os Correios.
		// $this->log->write($this->url);		

		// faz a chamada e retorna o xml com os dados
		$xml = $this->getXML($this->url);

		// lendo o xml
		if ($xml) {
			$dom = new DOMDocument('1.0', 'ISO-8859-1');
			$dom->loadXml($xml);
			
			$servicos = $dom->getElementsByTagName('cServico');
			
			if ($servicos) {
				
				// obtendo o prazo adicional a ser somado com o dos Correios
				$prazo_adicional = (is_numeric($this->config->get('shipping_correios_prazo_adicional'))) ? $this->config->get('shipping_correios_prazo_adicional') : 0 ;				
				
				foreach ($servicos as $servico) {
					$codigo = $servico->getElementsByTagName('Codigo')->item(0)->nodeValue;
					// Sedex 10, 12 e Sedex Hoje não tem prazo adicional
					$prazo = ($codigo == $this->correios['SEDEX 10'] || $codigo == $this->correios['SEDEX Hoje'] || $codigo == $this->correios['SEDEX 12']) ? 0 : $prazo_adicional;
					
					$dados[$codigo] = array(
						"Codigo" => $codigo,
						"Valor" => str_replace(',', '.', $servico->getElementsByTagName('Valor')->item(0)->nodeValue),
						"PrazoEntrega" => ($servico->getElementsByTagName('PrazoEntrega')->item(0)->nodeValue + $prazo),
						"Erro" => $servico->getElementsByTagName('Erro')->item(0)->nodeValue,
						"MsgErro" => $servico->getElementsByTagName('MsgErro')->item(0)->nodeValue,
						"ValorMaoPropria" => (isset($servico->getElementsByTagName('ValorMaoPropria')->item(0)->nodeValue)) ? str_replace(',', '.', $servico->getElementsByTagName('ValorMaoPropria')->item(0)->nodeValue) : 0,
						"ValorAvisoRecebimento" => (isset($servico->getElementsByTagName('ValorAvisoRecebimento')->item(0)->nodeValue)) ? str_replace(',', '.', $servico->getElementsByTagName('ValorAvisoRecebimento')->item(0)->nodeValue) : 0,
						"ValorValorDeclarado" => (isset($servico->getElementsByTagName('ValorValorDeclarado')->item(0)->nodeValue)) ? str_replace(',', '.', $servico->getElementsByTagName('ValorValorDeclarado')->item(0)->nodeValue) : 0
					);
				}
			}
		}
		return $dados;
	}

	// retorna a dimensão em centímetros
	private function getDimensaoEmCm($unidade_id, $dimensao){
		
		if(is_numeric($dimensao)){
			$length_class_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "length_class mc LEFT JOIN " . DB_PREFIX . "length_class_description mcd ON (mc.length_class_id = mcd.length_class_id) WHERE mcd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND mc.length_class_id =  '" . (int)$unidade_id . "'");
			
			if(isset($length_class_product_query->row['unit'])){
				if($length_class_product_query->row['unit'] == 'mm'){
					return $dimensao / 10;
				}		
			}
		}
		return $dimensao;
	}
	
	// retorna o peso em quilogramas
	private function getPesoEmKg($unidade_id, $peso){
		
		if(is_numeric($peso)) {
			$weight_class_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "weight_class wc LEFT JOIN " . DB_PREFIX . "weight_class_description wcd ON (wc.weight_class_id = wcd.weight_class_id) WHERE wcd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND wc.weight_class_id =  '" . (int)$unidade_id . "'");
			
			if(isset($weight_class_product_query->row['unit'])){
				if($weight_class_product_query->row['unit'] == 'g'){
					return ($peso / 1000);
				}		
			}
		}
		return $peso;
	}	
	
	// pré-validação das dimensões e peso do produto 
  	private function validar($produto){
  		
		$cubagem = (float)$produto['height'] * (float)$produto['width'] * (float)$produto['length'];
		$peso = (float)$produto['weight'];
		
		if(!$peso || $peso > $this->peso_max || !$cubagem || $cubagem > $this->cubagem_max || ($produto['height'] > $this->lados_max) || ($produto['width'] > $this->lados_max) || ($produto['length'] > $this->lados_max)){
			$this->log->write(sprintf($this->language->get('error_limites'), $produto['name']));
			
			return false;
		}
 	
  		return true;
  	}
  	
    // 'empacota' os produtos do carrinho em caixas conforme peso, cubagem e dimensões limites dos Correios
  	private function organizarEmCaixas($produtos) {
  	
  		$caixas = array();
  	
  		foreach ($produtos as $prod) {
  	
  			$prod_copy = $prod;
  	
  			// muda-se a quantidade do produto para incrementá-la em cada caixa
  			$prod_copy['quantity'] = 1;
  			
  			// todas as dimensões da caixa serão em cm e kg
  			$prod_copy['width']	= $this->getDimensaoEmCm($prod_copy['length_class_id'], $prod_copy['width']);
  			$prod_copy['height']= $this->getDimensaoEmCm($prod_copy['length_class_id'], $prod_copy['height']);
  			$prod_copy['length']= $this->getDimensaoEmCm($prod_copy['length_class_id'], $prod_copy['length']);
  			
  			// O peso do produto não é unitário como a dimensão. É multiplicado pela quantidade. Se quisermos o peso unitário, teremos que dividir pela quantidade.
  			$prod_copy['weight']= $this->getPesoEmKg($prod_copy['weight_class_id'], $prod_copy['weight'])/$prod['quantity'];
  			
  			$prod_copy['length_class_id'] = $this->config->get('config_length_class_id');
  			$prod_copy['weight_class_id'] = $this->config->get('config_weight_class_id');
  	
  			$cx_num = 0;
  	
  			for ($i = 1; $i <= $prod['quantity']; $i++) {
  	
  				// valida as dimensões do produto com as dos Correios
  				if ($this->validar($prod_copy)){
  					 
   					// cria-se a caixa caso ela não exista.
					isset($caixas[$cx_num]['peso']) ? true : $caixas[$cx_num]['peso'] = 0;
					isset($caixas[$cx_num]['cubagem']) ? true : $caixas[$cx_num]['cubagem'] = 0;					
  	
  					$peso = $caixas[$cx_num]['peso'] + $prod_copy['weight'];
					$cubagem = $caixas[$cx_num]['cubagem'] + ($prod_copy['width'] * $prod_copy['height'] * $prod_copy['length']);
					
 					$peso_dentro_limite = ($peso <= $this->peso_max);
					$cubagem_dentro_limite = ($cubagem <= $this->cubagem_max);
					
  					// o produto dentro do peso e dimensões estabelecidos pelos Correios
  					if ($peso_dentro_limite && $cubagem_dentro_limite) {
  						
						if (version_compare(VERSION, '2.1') < 0) {
							// já existe o mesmo produto na caixa, assim incrementa-se a sua quantidade
							if (isset($caixas[$cx_num]['produtos'][$prod_copy['key']])) {
								$caixas[$cx_num]['produtos'][$prod_copy['key']]['quantity']++;
							}
							// adiciona o novo produto
							else {
								$caixas[$cx_num]['produtos'][$prod_copy['key']] = $prod_copy;
							}
						} else {
							if (isset($caixas[$cx_num]['produtos'][$prod_copy['cart_id']])) {
								$caixas[$cx_num]['produtos'][$prod_copy['cart_id']]['quantity']++;
							}
							else {
								$caixas[$cx_num]['produtos'][$prod_copy['cart_id']] = $prod_copy;
							}							
						}						
						
						$caixas[$cx_num]['peso'] = $peso;
						$caixas[$cx_num]['cubagem'] = $cubagem;
  					}
  					// tenta adicionar o produto que não coube em uma nova caixa
  					else{
  						$cx_num++;
  						$i--;
  					}
  				}
  				// produto não tem as dimensões/peso válidos então abandona sem calcular o frete. 
  				else {
  					$caixas = array();
  					break 2;  // sai dos dois foreach
  				}
  			}
  		}
  		return $caixas;
  	}
  	// retorna o valor total dos produtos na caixa
  	private function getTotalCaixa($products) {
  		$total = 0;
  	
  		foreach ($products as $product) {
			$total += $this->currency->format($this->tax->calculate($product['total'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'], '', false);
  		}
  		return $total;
  	}

	private function raizCubica($n) {
		return pow($n, 1/3);
	}

  	private function removeProdutosSemFrete($products) {

		foreach ($products as $key => $product) {
  			
			if (!$product['shipping']) {
  				unset($products[$key]);
  			}	
  		}
  		return $products;
  	}	
}
?>
