<?php

namespace App\Services;

use App\Config\Database;
use App\Config\Env;
use App\Models\Enrollment;
use App\Models\Student;

class EfiPaymentService
{
    private $db;
    private $clientId;
    private $clientSecret;
    private $sandbox;
    private $certPath;
    private $certPassword;
    private $webhookSecret;
    
    // URLs para API de Cobranças (boletos/cartão)
    private $baseUrlCharges;
    private $oauthUrlCharges;
    
    // URLs para API Pix
    private $baseUrlPix;
    private $oauthUrlPix;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        Env::load();
        
        $this->clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
        $this->clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;
        $this->sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';
        $this->certPath = $_ENV['EFI_CERT_PATH'] ?? null;
        $this->certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? null;
        $this->webhookSecret = $_ENV['EFI_WEBHOOK_SECRET'] ?? null;
        
        // URLs para API de Cobranças (boletos/cartão de crédito)
        // OAuth endpoint usa URL diferente (sem /v1)
        $this->oauthUrlCharges = $this->sandbox 
            ? 'https://sandbox.gerencianet.com.br'
            : 'https://apis.gerencianet.com.br';
        
        // API endpoints de cobranças usam /v1
        $this->baseUrlCharges = $this->sandbox 
            ? 'https://sandbox.gerencianet.com.br/v1'
            : 'https://apis.gerencianet.com.br/v1';
        
        // URLs para API Pix (NUNCA usar apis.gerencianet.com.br)
        $this->oauthUrlPix = $this->sandbox 
            ? 'https://pix-h.api.efipay.com.br'
            : 'https://pix.api.efipay.com.br';
        
        // API Pix usa /v2 (sem /v1)
        $this->baseUrlPix = $this->sandbox 
            ? 'https://pix-h.api.efipay.com.br'
            : 'https://pix.api.efipay.com.br';
    }

    /**
     * Cria uma cobrança na Efí a partir de uma matrícula
     * 
     * @param array $enrollment Matrícula com dados completos
     * @return array {ok: bool, charge_id?: string, status?: string, payment_url?: string, message?: string}
     */
    public function createCharge($enrollment)
    {
        // Validar configuração
        if (!$this->clientId || !$this->clientSecret) {
            return [
                'ok' => false,
                'message' => 'Configuração do gateway não encontrada'
            ];
        }

        // Validar saldo devedor
        $outstandingAmount = floatval($enrollment['outstanding_amount'] ?? $enrollment['final_price'] ?? 0);
        if ($outstandingAmount <= 0) {
            return [
                'ok' => false,
                'message' => 'Sem saldo devedor para gerar cobrança'
            ];
        }

        // Verificar se já existe cobrança ativa (idempotência)
        if (!empty($enrollment['gateway_charge_id']) && 
            $enrollment['billing_status'] === 'generated' &&
            !in_array($enrollment['gateway_last_status'] ?? '', ['canceled', 'expired', 'error'])) {
            return [
                'ok' => false,
                'message' => 'Cobrança já existe',
                'charge_id' => $enrollment['gateway_charge_id'],
                'status' => $enrollment['gateway_last_status']
            ];
        }

        // Dados do aluno já devem vir no enrollment (via findWithDetails)
        // Se não vierem, buscar separadamente
        if (empty($enrollment['student_name']) && !empty($enrollment['student_id'])) {
            $studentModel = new Student();
            $student = $studentModel->find($enrollment['student_id']);
            if (!$student) {
                return [
                    'ok' => false,
                    'message' => 'Aluno não encontrado'
                ];
            }
        } else {
            // Usar dados que já vêm no enrollment
            $student = [
                'cpf' => $enrollment['student_cpf'] ?? null,
                'name' => $enrollment['student_name'] ?? null,
                'full_name' => $enrollment['full_name'] ?? null,
                'email' => $enrollment['email'] ?? null,
                'phone' => $enrollment['phone'] ?? $enrollment['phone_primary'] ?? null,
                'street' => $enrollment['street'] ?? null,
                'number' => $enrollment['number'] ?? null,
                'neighborhood' => $enrollment['neighborhood'] ?? null,
                'cep' => $enrollment['cep'] ?? null,
                'city' => $enrollment['city'] ?? null,
                'state_uf' => $enrollment['state_uf'] ?? null
            ];
        }

        // Determinar se é PIX para usar a API correta
        $paymentMethod = $enrollment['payment_method'] ?? 'pix';
        $installments = intval($enrollment['installments'] ?? 1);
        $isPix = ($paymentMethod === 'pix' && $installments === 1);
        
        // Obter token de autenticação (usar OAuth Pix se for PIX)
        $token = $this->getAccessToken($isPix);
        if (!$token) {
            // Verificar se credenciais estão configuradas
            if (empty($this->clientId) || empty($this->clientSecret)) {
                return [
                    'ok' => false,
                    'message' => 'Configuração do gateway incompleta. Verifique EFI_CLIENT_ID e EFI_CLIENT_SECRET no arquivo .env'
                ];
            }
            
            return [
                'ok' => false,
                'message' => 'Falha ao autenticar no gateway. Verifique se as credenciais estão corretas e se o ambiente (sandbox/produção) está configurado adequadamente.'
            ];
        }
        
        // Validar e sanitizar token
        if (!is_string($token)) {
            error_log("EFI Error: Token não é uma string. Tipo: " . gettype($token) . ", Valor: " . print_r($token, true));
            return [
                'ok' => false,
                'message' => 'Erro interno: Token de autenticação inválido'
            ];
        }
        
        $token = trim($token);
        if (empty($token)) {
            error_log("EFI Error: Token está vazio após trim");
            return [
                'ok' => false,
                'message' => 'Erro interno: Token de autenticação vazio'
            ];
        }

        // Montar payload conforme o tipo de API
        // Se for PIX, o payload será montado dentro do bloco if ($isPix) abaixo
        // Se não for PIX, montar payload da API de Cobranças
        $payload = null;
        
        if (!$isPix) {
            // Payload para API de Cobranças (boletos/cartão)
            $amountInCents = intval($outstandingAmount * 100); // Converter para centavos

            $payload = [
                'items' => [
                    [
                        'name' => $enrollment['service_name'] ?? 'Matrícula',
                        'value' => $amountInCents,
                        'amount' => 1
                    ]
                ],
                'metadata' => [
                    'enrollment_id' => $enrollment['id'],
                    'cfc_id' => $enrollment['cfc_id'] ?? 1,
                    'student_id' => $enrollment['student_id']
                ]
            ];

            // Adicionar dados do pagador
            if (!empty($student['cpf'])) {
                $cpf = preg_replace('/[^0-9]/', '', $student['cpf']);
                if (strlen($cpf) === 11) {
                    $payload['customer'] = [
                        'name' => $student['full_name'] ?? $student['name'] ?? 'Cliente',
                        'cpf' => $cpf,
                        'email' => $student['email'] ?? null,
                        'phone_number' => !empty($student['phone']) ? preg_replace('/[^0-9]/', '', $student['phone']) : null
                    ];
                }
            }

            // Configurar parcelamento se aplicável
            if ($installments > 1) {
                $payload['payment'] = [
                    'credit_card' => [
                        'installments' => $installments,
                        'billing_address' => [
                            'street' => $student['street'] ?? 'Não informado',
                            'number' => $student['number'] ?? 'S/N',
                            'neighborhood' => $student['neighborhood'] ?? '',
                            'zipcode' => preg_replace('/[^0-9]/', '', $student['cep'] ?? ''),
                            'city' => $student['city'] ?? '',
                            'state' => $student['state_uf'] ?? ''
                        ]
                    ]
                ];
            } else {
                // Pagamento à vista (Boleto)
                $payload['payment'] = ['banking_billet' => []];
            }
        }

        // Criar cobrança na API Efí
        // Se for PIX, usar API Pix (/v2/cob), senão usar API de Cobranças (/v1/charges)
        if ($isPix) {
            // API Pix: converter payload para formato Pix e usar endpoint /v2/cob
            // A API Pix tem estrutura diferente da API de Cobranças
            // Validar chave PIX (obrigatória para API Pix)
            $pixKey = $_ENV['EFI_PIX_KEY'] ?? null;
            if (empty($pixKey)) {
                error_log("EFI CreateCharge Pix Error: EFI_PIX_KEY não configurada no .env");
                $this->updateEnrollmentStatus($enrollment['id'], 'error', 'error', null);
                return [
                    'ok' => false,
                    'message' => 'Chave PIX não configurada. Configure EFI_PIX_KEY no arquivo .env'
                ];
            }
            
            $pixPayload = [
                'calendario' => [
                    'expiracao' => 3600 // 1 hora em segundos
                ],
                'valor' => [
                    'original' => number_format($outstandingAmount, 2, '.', '')
                ],
                'chave' => $pixKey, // Chave Pix (obrigatória)
                'solicitacaoPagador' => $enrollment['service_name'] ?? 'Matrícula',
                'infoAdicionais' => [
                    [
                        'nome' => 'enrollment_id',
                        'valor' => (string)$enrollment['id']
                    ],
                    [
                        'nome' => 'cfc_id',
                        'valor' => (string)($enrollment['cfc_id'] ?? 1)
                    ],
                    [
                        'nome' => 'student_id',
                        'valor' => (string)$enrollment['student_id']
                    ]
                ]
            ];
            
            // Adicionar dados do pagador se disponível
            if (!empty($student['cpf'])) {
                $cpf = preg_replace('/[^0-9]/', '', $student['cpf']);
                if (strlen($cpf) === 11) {
                    $pixPayload['devedor'] = [
                        'cpf' => $cpf,
                        'nome' => $student['full_name'] ?? $student['name'] ?? 'Cliente'
                    ];
                }
            }
            
            $response = $this->makeRequest('POST', '/v2/cob', $pixPayload, $token, true);
            
            // API Pix retorna dados diretamente (não dentro de 'data')
            if (!$response || isset($response['error']) || isset($response['mensagem'])) {
                $errorMessage = $response['mensagem'] ?? $response['error_description'] ?? $response['message'] ?? $response['error'] ?? 'Erro desconhecido ao criar cobrança Pix';
                
                error_log("EFI CreateCharge Pix Error: " . json_encode($response, JSON_UNESCAPED_UNICODE));
                
                $this->updateEnrollmentStatus($enrollment['id'], 'error', 'error', null);
                
                return [
                    'ok' => false,
                    'message' => $errorMessage
                ];
            }
            
            // Processar resposta da API Pix
            $chargeId = $response['txid'] ?? null;
            $status = 'waiting'; // Pix geralmente inicia como 'waiting'
            $paymentUrl = $response['pixCopiaECola'] ?? $response['qrCode'] ?? null;
            
        } else {
            // API de Cobranças: usar formato original
            $response = $this->makeRequest('POST', '/charges', $payload, $token, false);
            
            if (!$response || !isset($response['data'])) {
                // Capturar mensagem de erro mais detalhada
                $errorMessage = $response['error_description'] ?? $response['message'] ?? $response['error'] ?? 'Erro desconhecido ao criar cobrança';
                
                // Se houver detalhes adicionais, incluir
                if (isset($response['error_detail'])) {
                    $errorMessage .= ' - ' . $response['error_detail'];
                }
                
                // Log detalhado para debug
                error_log("EFI CreateCharge Error: " . json_encode($response, JSON_UNESCAPED_UNICODE));
                
                // Atualizar status de erro no banco
                $this->updateEnrollmentStatus($enrollment['id'], 'error', 'error', null);
                
                return [
                    'ok' => false,
                    'message' => $errorMessage
                ];
            }
            
            // Processar resposta da API de Cobranças
            $chargeData = $response['data'];
            $chargeId = $chargeData['charge_id'] ?? null;
            $status = $chargeData['status'] ?? 'unknown';
            $paymentUrl = null;
            
            // Extrair URL de pagamento se disponível
            if (isset($chargeData['payment'])) {
                if (isset($chargeData['payment']['pix']['qr_code'])) {
                    $paymentUrl = $chargeData['payment']['pix']['qr_code'];
                } elseif (isset($chargeData['payment']['banking_billet']['link'])) {
                    $paymentUrl = $chargeData['payment']['banking_billet']['link'];
                }
            }
        }

        // Atualizar matrícula com dados da cobrança (incluindo payment_url)
        $this->updateEnrollmentStatus(
            $enrollment['id'],
            'generated',
            $status,
            $chargeId,
            null,
            $paymentUrl
        );

        return [
            'ok' => true,
            'charge_id' => $chargeId,
            'status' => $status,
            'payment_url' => $paymentUrl
        ];
    }

    /**
     * Processa webhook da Efí e atualiza status da matrícula
     * 
     * @param array $requestPayload Payload recebido do webhook
     * @return array {ok: bool, processed: bool, message?: string}
     */
    public function parseWebhook($requestPayload)
    {
        // Validar assinatura do webhook (se configurado)
        if ($this->webhookSecret) {
            $signature = $_SERVER['HTTP_X_GN_SIGNATURE'] ?? '';
            if (!$this->validateWebhookSignature($requestPayload, $signature)) {
                return [
                    'ok' => false,
                    'processed' => false,
                    'message' => 'Assinatura inválida'
                ];
            }
        }

        // Normalizar payload
        $chargeId = $requestPayload['identifiers']['charge_id'] ?? $requestPayload['charge_id'] ?? null;
        $status = $requestPayload['current']['status'] ?? $requestPayload['status'] ?? null;
        $occurredAt = $requestPayload['occurred_at'] ?? date('Y-m-d H:i:s');

        if (!$chargeId || !$status) {
            return [
                'ok' => false,
                'processed' => false,
                'message' => 'Payload inválido'
            ];
        }

        // Buscar matrícula por gateway_charge_id
        $enrollmentModel = new Enrollment();
        $stmt = $this->db->prepare("
            SELECT * FROM enrollments 
            WHERE gateway_charge_id = ? AND gateway_provider = 'efi'
            LIMIT 1
        ");
        $stmt->execute([$chargeId]);
        $enrollment = $stmt->fetch();

        if (!$enrollment) {
            // Logar mas não quebrar (idempotência)
            error_log("EFI Webhook: Matrícula não encontrada para charge_id: {$chargeId}");
            return [
                'ok' => true,
                'processed' => false,
                'message' => 'Matrícula não encontrada'
            ];
        }

        // Mapear status do gateway para billing_status interno
        $billingStatus = $this->mapGatewayStatusToBillingStatus($status);

        // Atualizar matrícula
        $this->updateEnrollmentStatus(
            $enrollment['id'],
            $billingStatus,
            $status,
            $chargeId,
            $occurredAt
        );

        return [
            'ok' => true,
            'processed' => true,
            'charge_id' => $chargeId,
            'status' => $status,
            'billing_status' => $billingStatus
        ];
    }

    /**
     * Consulta status de uma cobrança na Efí
     * 
     * @param string $chargeId ID da cobrança
     * @return array|null Dados da cobrança ou null em caso de erro
     */
    /**
     * Consulta status de uma cobrança na Efí
     * 
     * @param string $chargeId ID da cobrança
     * @param bool $isPix Se true, consulta na API Pix, senão na API de Cobranças
     * @return array|null Dados da cobrança ou null em caso de erro
     */
    public function getChargeStatus($chargeId, $isPix = false)
    {
        $token = $this->getAccessToken($isPix);
        if (!$token) {
            return null;
        }

        // API Pix usa /v2/cob/{txid}, API de Cobranças usa /charges/{charge_id}
        $endpoint = $isPix ? "/v2/cob/{$chargeId}" : "/charges/{$chargeId}";
        $response = $this->makeRequest('GET', $endpoint, null, $token, $isPix);
        
        if (!$response) {
            return null;
        }
        
        // API Pix retorna dados diretamente, API de Cobranças retorna dentro de 'data'
        if ($isPix) {
            // API Pix: verificar se há erro
            if (isset($response['error']) || isset($response['mensagem'])) {
                return null;
            }
            // Retornar dados diretamente
            return $response;
        } else {
            // API de Cobranças: dados vêm dentro de 'data'
            if (!isset($response['data'])) {
                return null;
            }
            return $response['data'];
        }
    }

    /**
     * Obtém token de autenticação OAuth da Efí
     * 
     * @param bool $forPix Se true, usa OAuth da API Pix, senão usa OAuth de Cobranças
     * @return string|null Token de acesso ou null em caso de erro
     */
    private function getAccessToken($forPix = false)
    {
        // Validar credenciais antes de fazer requisição
        if (empty($this->clientId) || empty($this->clientSecret)) {
            error_log("EFI Auth Error: Credenciais não configuradas (CLIENT_ID ou CLIENT_SECRET vazios)");
            return null;
        }

        // Usar OAuth Pix se for requisição Pix, senão usar OAuth de Cobranças
        $oauthUrl = $forPix ? $this->oauthUrlPix : $this->oauthUrlCharges;
        $url = $oauthUrl . '/oauth/token';
        
        // Log de debug
        if (($_ENV['EFI_DEBUG'] ?? 'false') === 'true') {
            error_log("[EFI-DEBUG] getAccessToken: forPix={$forPix}, tokenUrl={$url}");
        }
        
        $payload = [
            'grant_type' => 'client_credentials'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        // Se certificado for necessário (geralmente exigido em produção)
        if ($this->certPath && file_exists($this->certPath)) {
            // Configurar certificado cliente para mutual TLS (mTLS)
            curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
            // Para P12, também pode precisar especificar a chave (mesmo arquivo)
            curl_setopt($ch, CURLOPT_SSLKEY, $this->certPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
            // Se tiver senha do certificado, usar
            if ($this->certPassword) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certPassword);
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $this->certPassword);
            } else {
                // Tentar sem senha (certificado pode não ter senha)
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, '');
            }
        } elseif (!$this->sandbox) {
            // Em produção, certificado pode ser obrigatório
            error_log("EFI Auth Warning: Produção sem certificado configurado. A EFI pode exigir certificado cliente em produção.");
        }

        // Captura verbose do cURL para debug (apenas em desenvolvimento ou se habilitado)
        $debugMode = ($_ENV['EFI_DEBUG'] ?? 'false') === 'true' || ($_ENV['APP_ENV'] ?? 'local') === 'local';
        $verboseLog = null;
        if ($debugMode) {
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        
        // Capturar verbose log se habilitado
        if ($debugMode && isset($verbose)) {
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            fclose($verbose);
        }
        
        curl_close($ch);

        // Função helper para debug (não expor segredos completos)
        $tailHex = function($s, $n = 6) {
            if (strlen($s) <= $n) return '***';
            $t = substr($s, -$n);
            return bin2hex($t);
        };

        if ($curlError) {
            $errorDetails = "cURL error: {$curlError} (errno: {$curlErrNo})";
            
            // Debug detalhado se habilitado
            if ($debugMode) {
                $errorDetails .= "\nDEBUG INFO:";
                $errorDetails .= "\n- HTTP_CODE: {$httpCode}";
                $errorDetails .= "\n- CURL_ERRNO: {$curlErrNo}";
                $errorDetails .= "\n- CLIENT_ID_LEN: " . strlen($this->clientId) . " TAIL: " . $tailHex($this->clientId);
                $errorDetails .= "\n- CLIENT_SECRET_LEN: " . strlen($this->clientSecret) . " TAIL: " . $tailHex($this->clientSecret);
                $errorDetails .= "\n- CERT_PATH: " . ($this->certPath ?? 'não configurado');
                $errorDetails .= "\n- CERT_EXISTS: " . ($this->certPath && file_exists($this->certPath) ? 'sim' : 'não');
                if ($verboseLog) {
                    $errorDetails .= "\n- CURL_VERBOSE:\n" . $verboseLog;
                }
                $errorDetails .= "\n- RESPONSE: " . substr($response, 0, 500);
            }
            
            // Mensagens mais específicas para erros comuns
            if (strpos($curlError, 'Connection was reset') !== false || strpos($curlError, 'Recv failure') !== false) {
                $errorDetails .= " | Possíveis causas: 1) Certificado cliente necessário em produção, 2) Firewall bloqueando, 3) Problema de rede";
            } elseif (strpos($curlError, 'SSL') !== false || strpos($curlError, 'certificate') !== false) {
                $errorDetails .= " | Problema com certificado SSL. Verifique EFI_CERT_PATH no .env";
            } elseif (strpos($curlError, 'timeout') !== false) {
                $errorDetails .= " | Timeout na conexão. Verifique conectividade com a internet";
            }
            
            error_log("EFI Auth Error: {$errorDetails}");
            return null;
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error_description'] ?? $errorData['error'] ?? $errorData['message'] ?? 'Erro desconhecido';
            
            // Debug detalhado se habilitado
            $debugInfo = "";
            if (($debugMode = ($_ENV['EFI_DEBUG'] ?? 'false') === 'true' || ($_ENV['APP_ENV'] ?? 'local') === 'local')) {
                $tailHex = function($s, $n = 6) {
                    if (strlen($s) <= $n) return '***';
                    $t = substr($s, -$n);
                    return bin2hex($t);
                };
                $debugInfo = "\nDEBUG INFO:";
                $debugInfo .= "\n- HTTP_CODE: {$httpCode}";
                $debugInfo .= "\n- CLIENT_ID_LEN: " . strlen($this->clientId) . " TAIL: " . $tailHex($this->clientId);
                $debugInfo .= "\n- CLIENT_SECRET_LEN: " . strlen($this->clientSecret) . " TAIL: " . $tailHex($this->clientSecret);
                $debugInfo .= "\n- CERT_PATH: " . ($this->certPath ?? 'não configurado');
                $debugInfo .= "\n- CERT_EXISTS: " . ($this->certPath && file_exists($this->certPath) ? 'sim' : 'não');
                $debugInfo .= "\n- CERT_HAS_PASSWORD: " . (!empty($this->certPassword) ? 'sim' : 'não');
                if ($verboseLog) {
                    $debugInfo .= "\n- CURL_VERBOSE:\n" . substr($verboseLog, 0, 2000);
                }
                $debugInfo .= "\n- RESPONSE_BODY: " . substr($response, 0, 500);
            }
            
            error_log("EFI Auth Error: HTTP {$httpCode} - {$errorMessage}{$debugInfo}");
            return null;
        }

        if (!$response) {
            error_log("EFI Auth Error: Resposta vazia da API");
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['access_token'])) {
            error_log("EFI Auth Error: access_token não encontrado na resposta. Resposta completa: " . substr($response, 0, 500));
            return null;
        }

        $accessToken = $data['access_token'];
        
        // Validar que o token é uma string válida
        if (!is_string($accessToken) || empty(trim($accessToken))) {
            error_log("EFI Auth Error: access_token não é uma string válida. Tipo: " . gettype($accessToken));
            return null;
        }
        
        $accessToken = trim($accessToken);
        
        // Log de debug com informações do token
        if (($_ENV['EFI_DEBUG'] ?? 'false') === 'true') {
            $tokenPrefix = substr($accessToken, 0, 10);
            $isJwt = (substr($accessToken, 0, 3) === 'eyJ');
            $scope = $data['scope'] ?? 'N/A';
            error_log("[EFI-DEBUG] getAccessToken: forPix={$forPix}, http_code={$httpCode}, token_length=" . strlen($accessToken) . ", token_prefix={$tokenPrefix}, is_jwt=" . ($isJwt ? 'true' : 'false') . ", scope={$scope}");
        }
        
        return $accessToken;
    }

    /**
     * Faz requisição HTTP para API Efí
     * 
     * @param string $method Método HTTP (GET, POST, PUT, DELETE)
     * @param string $endpoint Endpoint da API (ex: /charges, /v2/cob)
     * @param array|null $payload Dados para enviar (POST/PUT)
     * @param string|null $token Token de autenticação Bearer
     * @param bool $isPix Se true, usa baseUrlPix, senão usa baseUrlCharges
     * @return array|null Resposta da API ou null em caso de erro
     */
    private function makeRequest($method, $endpoint, $payload = null, $token = null, $isPix = false)
    {
        // Usar base URL Pix se for requisição Pix, senão usar base URL de Cobranças
        $baseUrl = $isPix ? $this->baseUrlPix : $this->baseUrlCharges;
        $url = $baseUrl . $endpoint;
        
        // GUARDRAIL: Se isPix=true e URL contém apis.gerencianet.com.br, BLOQUEAR
        // (Ambas as APIs retornam JWT, então não podemos bloquear JWT em /charges)
        if ($isPix && strpos($url, 'apis.gerencianet.com.br') !== false) {
            error_log("[EFI-DEBUG] BLOCKED: URL de Cobranças sendo usada para Pix. url={$url}");
            return [
                'error' => 'URL incorreta para API Pix',
                'error_description' => 'API Pix deve usar pix.api.efipay.com.br, não apis.gerencianet.com.br'
            ];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // IMPORTANTE: Authorization DEVE ser o primeiro header
        // A API da EFI é muito sensível à ordem e formato dos headers
        $headers = [];
        
        if ($token) {
            // Garantir que token é string e está limpo
            $token = is_string($token) ? trim($token) : (string)$token;
            
            // Validar formato do token (deve ser JWT ou string alfanumérica)
            if (empty($token) || strlen($token) < 10) {
                error_log("EFI makeRequest Error: Token inválido ou muito curto. Tamanho: " . strlen($token));
                return ['error' => 'Token de autenticação inválido', 'error_description' => 'Token muito curto ou vazio'];
            }
            
            // IMPORTANTE: Garantir que não há espaços extras ou caracteres especiais
            // A API da EFI em produção é muito sensível ao formato do header
            $token = trim($token);
            
            // Verificar se há caracteres problemáticos no token
            if (preg_match('/[^\x20-\x7E]/', $token)) {
                error_log("EFI makeRequest Warning: Token contém caracteres não-ASCII");
                // Remover caracteres não-ASCII do token
                $token = preg_replace('/[^\x20-\x7E]/', '', $token);
                $token = trim($token);
            }
            
            // Montar header Authorization - DEVE ser exatamente "Authorization: Bearer {token}"
            // Sem espaços extras, sem quebras de linha, sem caracteres especiais
            $authHeader = 'Authorization: Bearer ' . $token;
            
            // Verificar se o header está correto
            if (strlen($authHeader) !== strlen('Authorization: Bearer ') + strlen($token)) {
                error_log("EFI makeRequest Error: Header Authorization tem tamanho incorreto");
            }
            
            // Authorization DEVE ser o primeiro header
            $headers[] = $authHeader;
            
            // Log para debug (apenas primeiros e últimos caracteres do token por segurança)
            if (($_ENV['EFI_DEBUG'] ?? 'false') === 'true') {
                $tokenPreview = substr($token, 0, 20) . '...' . substr($token, -10);
                error_log("[EFI-DEBUG] makeRequest: URL={$url}, isPix={$isPix}, Token preview={$tokenPreview}, Header length=" . strlen($authHeader));
                error_log("[EFI-DEBUG] makeRequest: Header completo (primeiros 100 chars): " . substr($authHeader, 0, 100));
                // Verificar se há caracteres problemáticos
                $headerBytes = [];
                for ($i = 0; $i < min(50, strlen($authHeader)); $i++) {
                    $headerBytes[] = dechex(ord($authHeader[$i]));
                }
                error_log("[EFI-DEBUG] makeRequest: Header bytes (hex, primeiros 50): " . implode(' ', $headerBytes));
            }
        }
        
        // Content-Type vem depois do Authorization
        $headers[] = 'Content-Type: application/json';

        if ($payload && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        
        // NÃO usar usort - pode corromper o header
        // Headers já estão na ordem correta: Authorization primeiro, depois Content-Type
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Se certificado for necessário (obrigatório em produção)
        if ($this->certPath && file_exists($this->certPath)) {
            // Configurar certificado cliente para mutual TLS (mTLS)
            curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
            // Para P12, também pode precisar especificar a chave (mesmo arquivo)
            curl_setopt($ch, CURLOPT_SSLKEY, $this->certPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
            // Se tiver senha do certificado, usar
            if ($this->certPassword) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certPassword);
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $this->certPassword);
            } else {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, '');
            }
        } elseif (!$this->sandbox) {
            // Em produção, certificado é obrigatório para requisições da API
            error_log("EFI makeRequest Warning: Produção sem certificado configurado. A EFI exige certificado cliente em produção para todas as requisições.");
        }

        // Log detalhado dos headers sendo enviados (apenas em debug)
        if (($_ENV['EFI_DEBUG'] ?? 'false') === 'true') {
            error_log("[EFI-DEBUG] makeRequest: Enviando requisição para {$url}");
            error_log("[EFI-DEBUG] makeRequest: Headers sendo enviados: " . json_encode($headers, JSON_UNESCAPED_UNICODE));
            error_log("[EFI-DEBUG] makeRequest: Método: {$method}, Endpoint: {$endpoint}, isPix: " . ($isPix ? 'true' : 'false'));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("EFI Request Error: {$curlError}");
            return null;
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorDetails = [
                'http_code' => $httpCode,
                'response' => $data,
                'raw_response' => substr($response, 0, 1000), // Primeiros 1000 caracteres
                'url' => $url,
                'isPix' => $isPix,
                'method' => $method,
                'endpoint' => $endpoint
            ];
            
            // Log detalhado em caso de erro 403 (problema de autenticação)
            if ($httpCode === 403) {
                error_log("[EFI-DEBUG] HTTP 403 - Detalhes completos: " . json_encode($errorDetails, JSON_UNESCAPED_UNICODE));
                if (isset($data['message']) && strpos($data['message'], 'Invalid key=value pair') !== false) {
                    error_log("[EFI-DEBUG] ERRO ESPECÍFICO: Invalid key=value pair detectado");
                    error_log("[EFI-DEBUG] Isso geralmente indica que a API está interpretando o header Authorization como AWS SigV4");
                    error_log("[EFI-DEBUG] Verifique se o header está no formato correto: 'Authorization: Bearer {token}'");
                }
            }
            
            error_log("EFI API Error: HTTP {$httpCode} - " . json_encode($errorDetails, JSON_UNESCAPED_UNICODE));
            return $data; // Retornar erro para tratamento
        }

        return $data;
    }

    /**
     * Valida assinatura do webhook
     */
    private function validateWebhookSignature($payload, $signature)
    {
        if (!$this->webhookSecret || !$signature) {
            return false;
        }

        $payloadString = is_array($payload) ? json_encode($payload) : $payload;
        $expectedSignature = hash_hmac('sha256', $payloadString, $this->webhookSecret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Mapeia status do gateway para billing_status interno
     * 
     * @param string $gatewayStatus Status retornado pela EFI
     * @return string billing_status (draft/ready/generated/error)
     */
    private function mapGatewayStatusToBillingStatus($gatewayStatus)
    {
        // Status que indicam sucesso/gerado
        $successStatuses = ['paid', 'settled', 'waiting'];
        // Status que indicam erro
        $errorStatuses = ['unpaid', 'refunded', 'canceled', 'expired'];
        
        if (in_array(strtolower($gatewayStatus), $successStatuses)) {
            return 'generated';
        }
        
        if (in_array(strtolower($gatewayStatus), $errorStatuses)) {
            return 'error';
        }
        
        // Status intermediários
        return 'ready';
    }

    /**
     * Mapeia status do gateway para financial_status interno
     * 
     * @param string $gatewayStatus Status retornado pela EFI
     * @return string|null financial_status (em_dia/pendente/bloqueado) ou null se não deve alterar
     */
    public function mapGatewayStatusToFinancialStatus($gatewayStatus)
    {
        $status = strtolower($gatewayStatus);
        
        // Status que indicam pagamento confirmado
        if (in_array($status, ['paid', 'settled', 'approved'])) {
            return 'em_dia';
        }
        
        // Status que indicam cancelamento/expirado (mantém pendente, permite gerar nova)
        if (in_array($status, ['canceled', 'expired'])) {
            return 'pendente';
        }
        
        // Status aguardando pagamento (mantém pendente)
        if (in_array($status, ['waiting', 'unpaid', 'pending', 'processing', 'new'])) {
            return 'pendente';
        }
        
        // Outros status: não altera financial_status (retorna null)
        return null;
    }

    /**
     * Sincroniza status de uma cobrança consultando a API da EFI
     * 
     * @param array $enrollment Matrícula com gateway_charge_id
     * @return array {ok: bool, charge_id?: string, status?: string, payment_url?: string, financial_status?: string, message?: string}
     */
    public function syncCharge($enrollment)
    {
        // Validar configuração
        if (!$this->clientId || !$this->clientSecret) {
            return [
                'ok' => false,
                'message' => 'Configuração do gateway não encontrada'
            ];
        }

        // Validar que existe cobrança gerada
        $chargeId = $enrollment['gateway_charge_id'] ?? null;
        if (empty($chargeId)) {
            return [
                'ok' => false,
                'message' => 'Nenhuma cobrança gerada para esta matrícula'
            ];
        }

        // Determinar se é PIX baseado no payment_method da matrícula
        $paymentMethod = $enrollment['payment_method'] ?? null;
        $isPix = ($paymentMethod === 'pix');

        // Consultar status na EFI (usar API Pix se for PIX)
        $chargeData = $this->getChargeStatus($chargeId, $isPix);
        if (!$chargeData) {
            return [
                'ok' => false,
                'message' => 'Não foi possível consultar status da cobrança na EFI. Verifique se a cobrança existe ou se há problemas de conexão.'
            ];
        }

        $status = 'unknown';
        $paymentUrl = null;

        // Processar resposta conforme o tipo de API
        if ($isPix) {
            // API Pix: dados vêm diretamente (não dentro de 'data')
            $status = $chargeData['status'] ?? 'ATIVA'; // API Pix usa 'ATIVA', 'CONCLUIDA', etc.
            // Mapear status Pix para formato padrão
            if ($status === 'CONCLUIDA') {
                $status = 'paid';
            } elseif ($status === 'ATIVA') {
                $status = 'waiting';
            }
            
            // Extrair QR Code da API Pix
            $paymentUrl = $chargeData['pixCopiaECola'] ?? $chargeData['qrCode'] ?? null;
            
            // Se não tiver QR Code direto, pode estar em location
            if (empty($paymentUrl) && isset($chargeData['location'])) {
                // TODO: Consultar location se necessário
            }
        } else {
            // API de Cobranças: dados vêm dentro de 'data'
            $status = $chargeData['status'] ?? 'unknown';
            
            // Extrair URL de pagamento se disponível
            if (isset($chargeData['payment'])) {
                if (isset($chargeData['payment']['pix']['qr_code'])) {
                    $paymentUrl = $chargeData['payment']['pix']['qr_code'];
                } elseif (isset($chargeData['payment']['banking_billet']['link'])) {
                    $paymentUrl = $chargeData['payment']['banking_billet']['link'];
                }
            }
        }

        // Mapear status
        $billingStatus = $this->mapGatewayStatusToBillingStatus($status);
        $financialStatus = $this->mapGatewayStatusToFinancialStatus($status);

        // Atualizar matrícula
        $eventAt = isset($chargeData['updated_at']) ? date('Y-m-d H:i:s', strtotime($chargeData['updated_at'])) : date('Y-m-d H:i:s');
        
        // Preparar dados de atualização
        $updateData = [
            'billing_status' => $billingStatus,
            'gateway_last_status' => $status,
            'gateway_last_event_at' => $eventAt,
            'gateway_provider' => 'efi'
        ];

        // Atualizar payment_url se fornecido e ainda não existir
        if ($paymentUrl && empty($enrollment['gateway_payment_url'])) {
            $updateData['gateway_payment_url'] = $paymentUrl;
        }

        // Atualizar financial_status se mapeado
        if ($financialStatus !== null) {
            $updateData['financial_status'] = $financialStatus;
        } else {
            // Se não foi mapeado, recalcular baseado em outstanding_amount
            $updateData['financial_status'] = $this->recalculateFinancialStatus($enrollment);
        }

        $setParts = [];
        $params = [];
        foreach ($updateData as $key => $value) {
            $setParts[] = "`{$key}` = ?";
            $params[] = $value;
        }
        $params[] = $enrollment['id'];

        $sql = "UPDATE enrollments SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // Log (sem dados sensíveis)
        error_log(sprintf(
            "EFI Sync: enrollment_id=%d, charge_id=%s, status=%s, billing_status=%s, financial_status=%s",
            $enrollment['id'],
            $chargeId,
            $status,
            $billingStatus,
            $financialStatus ?? 'não alterado'
        ));

        return [
            'ok' => true,
            'charge_id' => $chargeId,
            'status' => $status,
            'billing_status' => $billingStatus,
            'financial_status' => $financialStatus,
            'payment_url' => $paymentUrl ?: $enrollment['gateway_payment_url'] ?? null
        ];
    }

    /**
     * Recalcula financial_status baseado em outstanding_amount
     * 
     * @param array $enrollment Matrícula com dados
     * @return string financial_status ('em_dia', 'pendente', 'bloqueado')
     */
    private function recalculateFinancialStatus($enrollment)
    {
        // Se já está bloqueado, manter bloqueado
        if (($enrollment['financial_status'] ?? '') === 'bloqueado') {
            return 'bloqueado';
        }
        
        // Calcular saldo devedor
        $outstandingAmount = floatval($enrollment['outstanding_amount'] ?? 0);
        if ($outstandingAmount <= 0) {
            // Se não tem coluna outstanding_amount, calcular
            if (empty($enrollment['outstanding_amount'])) {
                $finalPrice = floatval($enrollment['final_price'] ?? 0);
                $entryAmount = floatval($enrollment['entry_amount'] ?? 0);
                $outstandingAmount = max(0, $finalPrice - $entryAmount);
            }
        }
        
        // Se tem saldo devedor, deve ser 'pendente'
        // Se não tem saldo, deve ser 'em_dia'
        return $outstandingAmount > 0 ? 'pendente' : 'em_dia';
    }

    /**
     * Atualiza status da matrícula no banco
     * 
     * @param int $enrollmentId ID da matrícula
     * @param string $billingStatus Status interno (draft/ready/generated/error)
     * @param string $gatewayStatus Status do gateway (paid/waiting/canceled/etc)
     * @param string|null $chargeId ID da cobrança no gateway
     * @param string|null $eventAt Data/hora do evento (formato Y-m-d H:i:s)
     * @param string|null $paymentUrl URL de pagamento (PIX ou Boleto)
     */
    private function updateEnrollmentStatus($enrollmentId, $billingStatus, $gatewayStatus, $chargeId = null, $eventAt = null, $paymentUrl = null)
    {
        // Buscar matrícula atual para recalcular financial_status
        $stmt = $this->db->prepare("SELECT * FROM enrollments WHERE id = ?");
        $stmt->execute([$enrollmentId]);
        $currentEnrollment = $stmt->fetch();
        
        if (!$currentEnrollment) {
            return;
        }
        
        $updateData = [
            'billing_status' => $billingStatus,
            'gateway_last_status' => $gatewayStatus,
            'gateway_last_event_at' => $eventAt ?: date('Y-m-d H:i:s'),
            'gateway_provider' => 'efi'
        ];

        if ($chargeId) {
            $updateData['gateway_charge_id'] = $chargeId;
        }

        // Salvar payment_url se fornecido (não sobrescreve se já existir e novo for vazio)
        if ($paymentUrl !== null) {
            $updateData['gateway_payment_url'] = $paymentUrl;
        }
        
        // Recalcular financial_status baseado em outstanding_amount
        // (exceto se já está bloqueado ou se foi mapeado pelo gateway)
        $updateData['financial_status'] = $this->recalculateFinancialStatus($currentEnrollment);

        $setParts = [];
        $params = [];
        foreach ($updateData as $key => $value) {
            $setParts[] = "`{$key}` = ?";
            $params[] = $value;
        }
        $params[] = $enrollmentId;

        $sql = "UPDATE enrollments SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
}
