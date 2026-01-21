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
        // NUNCA usar apis.gerencianet.com.br - usar cobrancas.api.efipay.com.br
        // OAuth de Cobranças usa /v1/authorize (não /oauth/token)
        if ($this->sandbox) {
            $this->oauthUrlCharges = 'https://cobrancas-h.api.efipay.com.br/v1/authorize';
            $this->baseUrlCharges = 'https://cobrancas-h.api.efipay.com.br';
        } else {
            $this->oauthUrlCharges = 'https://cobrancas.api.efipay.com.br/v1/authorize';
            $this->baseUrlCharges = 'https://cobrancas.api.efipay.com.br';
        }
        
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
            $this->efiLog('ERROR', 'createCharge: Token não é uma string', [
                'token_type' => gettype($token)
            ]);
            return [
                'ok' => false,
                'message' => 'Erro interno: Token de autenticação inválido'
            ];
        }
        
        $token = trim($token);
        if (empty($token)) {
            $this->efiLog('ERROR', 'createCharge: Token está vazio após trim', []);
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
                ]
            ];
            
            // NOTA: A API de Cobranças EFI não aceita metadata no formato padrão
            // Se precisar rastrear enrollment_id, usar no campo de observações do boleto ou em outro lugar

            // ÁRVORE DE DECISÃO CORRETA (conforme regra de negócio):
            // 
            // 1. payment_method = 'pix' → Pix (único, sempre installments = 1)
            // 2. payment_method = 'cartao' + installments > 1 → Cartão parcelado
            // 3. payment_method = 'cartao' + installments = 1 → Cartão à vista
            // 4. payment_method = 'boleto' + installments = 1 → Boleto à vista
            // 5. payment_method = 'boleto' + installments > 1 → Carnê (N boletos via /v1/carnet)
            //
            // IMPORTANTE: Boleto + parcelas deve usar Carnê, NÃO cartão!
            
            $isCreditCard = ($paymentMethod === 'cartao' || $paymentMethod === 'credit_card') && $installments > 1;
            $isCreditCardSingle = ($paymentMethod === 'cartao' || $paymentMethod === 'credit_card') && $installments === 1;
            $isBoletoSingle = ($paymentMethod === 'boleto') && $installments === 1;
            $isCarnet = ($paymentMethod === 'boleto') && $installments > 1;
            
            if ($isCreditCard || $isCreditCardSingle) {
                // Cartão de crédito (parcelado): customer vai no root do payload
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
            } elseif ($isBoletoSingle) {
                // Boleto à vista (payment_method = 'boleto' + installments = 1)
                // IMPORTANTE: customer NÃO deve estar no root do payload para boleto
                // customer deve estar APENAS dentro de payment.banking_billet.customer
                // banking_billet deve ser um OBJETO, não array vazio
                $bankingBillet = [];
                
                // Adicionar dados do pagador se disponível
                if (!empty($student['cpf'])) {
                    $cpf = preg_replace('/[^0-9]/', '', $student['cpf']);
                    if (strlen($cpf) === 11) {
                        $bankingBillet['customer'] = [
                            'name' => $student['full_name'] ?? $student['name'] ?? 'Cliente',
                            'cpf' => $cpf,
                            'email' => $student['email'] ?? null,
                            'phone_number' => !empty($student['phone']) ? preg_replace('/[^0-9]/', '', $student['phone']) : null
                        ];
                        
                        // Adicionar endereço se disponível
                        if (!empty($student['cep'])) {
                            $cep = preg_replace('/[^0-9]/', '', $student['cep']);
                            if (strlen($cep) === 8) {
                                $bankingBillet['customer']['address'] = [
                                    'street' => $student['street'] ?? 'Não informado',
                                    'number' => $student['number'] ?? 'S/N',
                                    'neighborhood' => $student['neighborhood'] ?? '',
                                    'zipcode' => $cep,
                                    'city' => $student['city'] ?? '',
                                    'state' => $student['state_uf'] ?? ''
                                ];
                            }
                        }
                    }
                }
                
                // Data de vencimento (padrão: 3 dias)
                $bankingBillet['expire_at'] = date('Y-m-d', strtotime('+3 days'));
                
                // Mensagem opcional
                $bankingBillet['message'] = 'Pagamento referente a matrícula';
                
                $payload['payment'] = ['banking_billet' => $bankingBillet];
            } elseif ($isCarnet) {
                // Carnê (boleto parcelado): boleto + installments > 1
                // Usa endpoint /v1/carnet para criar múltiplos boletos
                // Retornar diretamente após criar o Carnê (não seguir fluxo normal)
                return $this->createCarnet($enrollment, $student, $outstandingAmount, $installments);
            } else {
                // Método de pagamento inválido ou não suportado
                $this->efiLog('ERROR', 'createCharge: Método de pagamento não suportado', [
                    'payment_method' => $paymentMethod,
                    'installments' => $installments,
                    'enrollment_id' => $enrollment['id']
                ]);
                $this->updateEnrollmentStatus($enrollment['id'], 'error', 'error', null);
                return [
                    'ok' => false,
                    'message' => 'Método de pagamento não suportado. Verifique payment_method e installments.'
                ];
            }
        }

        // Criar cobrança na API Efí
        // Se for PIX, usar API Pix (/v2/cob), senão usar API de Cobranças (/v1/charges)
        // NOTA: Carnê já foi tratado acima e retornou diretamente
        if ($isPix) {
            // API Pix: converter payload para formato Pix e usar endpoint /v2/cob
            // A API Pix tem estrutura diferente da API de Cobranças
            // Validar chave PIX (obrigatória para API Pix)
            $pixKey = $_ENV['EFI_PIX_KEY'] ?? null;
            if (empty($pixKey)) {
                $this->efiLog('ERROR', 'createCharge Pix: EFI_PIX_KEY não configurada', [
                    'enrollment_id' => $enrollment['id']
                ]);
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
            
            // makeRequest agora sempre retorna array com http_code
            $httpCode = $response['http_code'] ?? 0;
            $responseData = $response['response'] ?? $response;
            
            // API Pix retorna dados diretamente (não dentro de 'data')
            if ($httpCode >= 400 || !$responseData || isset($responseData['error']) || isset($responseData['mensagem'])) {
                $errorMessage = $responseData['mensagem'] ?? $responseData['error_description'] ?? $responseData['message'] ?? $responseData['error'] ?? 'Erro desconhecido ao criar cobrança Pix';
                
                $this->efiLog('ERROR', 'createCharge Pix falhou', [
                    'enrollment_id' => $enrollment['id'],
                    'http_code' => $httpCode,
                    'error' => substr($errorMessage, 0, 180),
                    'response_snippet' => substr(json_encode($responseData, JSON_UNESCAPED_UNICODE), 0, 180)
                ]);
                
                $this->updateEnrollmentStatus($enrollment['id'], 'error', 'error', null);
                
                return [
                    'ok' => false,
                    'message' => $errorMessage
                ];
            }
            
            // Processar resposta da API Pix
            $chargeId = $responseData['txid'] ?? null;
            $status = 'waiting'; // Pix geralmente inicia como 'waiting'
            $paymentUrl = $responseData['pixCopiaECola'] ?? $responseData['qrCode'] ?? null;
            
        } else {
            // API de Cobranças: usar endpoint one-step (cria e define pagamento em uma única chamada)
            // makeRequest já adiciona /v1/ automaticamente para Cobranças
            // Endpoint correto: POST /v1/charge/one-step
            $response = $this->makeRequest('POST', '/charge/one-step', $payload, $token, false);
            
            // makeRequest agora sempre retorna array com http_code
            $httpCode = $response['http_code'] ?? 0;
            $responseData = $response['response'] ?? $response;
            
            // Log detalhado para debug
            $this->efiLog('DEBUG', 'createCharge Cobranças response', [
                'enrollment_id' => $enrollment['id'],
                'http_code' => $httpCode,
                'has_data' => isset($responseData['data']),
                'response_keys' => is_array($responseData) ? array_keys($responseData) : []
            ]);
            
            if ($httpCode >= 400 || !$responseData) {
                // Capturar mensagem de erro mais detalhada
                $errorMessage = $responseData['error_description'] ?? $responseData['message'] ?? $responseData['error'] ?? 'Erro desconhecido ao criar cobrança';
                
                // Garantir que errorMessage seja string
                if (is_array($errorMessage) || is_object($errorMessage)) {
                    $errorMessage = json_encode($errorMessage, JSON_UNESCAPED_UNICODE);
                }
                $errorMessage = (string)$errorMessage;
                
                // Se houver detalhes adicionais, incluir
                if (isset($responseData['error_detail'])) {
                    $errorDetail = is_array($responseData['error_detail']) || is_object($responseData['error_detail'])
                        ? json_encode($responseData['error_detail'], JSON_UNESCAPED_UNICODE)
                        : (string)$responseData['error_detail'];
                    $errorMessage .= ' - ' . $errorDetail;
                }
                
                // Log detalhado para debug
                $this->efiLog('ERROR', 'createCharge Cobranças falhou', [
                    'enrollment_id' => $enrollment['id'],
                    'http_code' => $httpCode,
                    'error' => substr($errorMessage, 0, 180),
                    'response_snippet' => substr(json_encode($responseData, JSON_UNESCAPED_UNICODE), 0, 180)
                ]);
                
                // Atualizar status de erro no banco
                $this->updateEnrollmentStatus($enrollment['id'], 'error', 'error', null);
                
                return [
                    'ok' => false,
                    'message' => $errorMessage
                ];
            }
            
            // Processar resposta da API de Cobranças
            // A resposta pode vir diretamente ou dentro de 'data'
            $chargeData = $responseData['data'] ?? $responseData;
            $chargeId = $chargeData['charge_id'] ?? $chargeData['id'] ?? null;
            $status = $chargeData['status'] ?? 'unknown';
            $paymentUrl = null;
            
            // Extrair URL de pagamento se disponível
            if (isset($chargeData['payment'])) {
                // Boleto
                if (isset($chargeData['payment']['banking_billet']['link'])) {
                    $paymentUrl = $chargeData['payment']['banking_billet']['link'];
                } elseif (isset($chargeData['payment']['banking_billet']['barcode'])) {
                    // Se não tiver link, pode ter código de barras
                    $paymentUrl = $chargeData['payment']['banking_billet']['barcode'];
                }
                // Pix (se houver)
                if (isset($chargeData['payment']['pix']['qr_code'])) {
                    $paymentUrl = $chargeData['payment']['pix']['qr_code'];
                }
            }
            
            // Log de sucesso
            $this->efiLog('INFO', 'createCharge Cobranças sucesso', [
                'enrollment_id' => $enrollment['id'],
                'charge_id' => $chargeId,
                'status' => $status,
                'has_payment_url' => !empty($paymentUrl)
            ]);
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

        // Determinar tipo de pagamento para o retorno
        $paymentType = 'boleto'; // padrão
        if ($isPix) {
            $paymentType = 'pix';
        } elseif ($isCreditCard || $isCreditCardSingle) {
            $paymentType = 'cartao';
        } elseif ($isBoletoSingle) {
            $paymentType = 'boleto';
        }

        return [
            'ok' => true,
            'type' => $paymentType,
            'charge_id' => $chargeId,
            'status' => $status,
            'payment_url' => $paymentUrl
        ];
    }

    /**
     * Cria um Carnê (múltiplos boletos) na API EFI
     * 
     * @param array $enrollment Dados da matrícula
     * @param array $student Dados do aluno
     * @param float $totalAmount Valor total a ser parcelado
     * @param int $installments Número de parcelas
     * @return array Resultado da criação do Carnê
     */
    public function createCarnet($enrollment, $student, $totalAmount, $installments)
    {
        // Validar configuração
        if (!$this->clientId || !$this->clientSecret) {
            return [
                'ok' => false,
                'message' => 'Configuração do gateway incompleta. Verifique EFI_CLIENT_ID e EFI_CLIENT_SECRET no arquivo .env'
            ];
        }

        // Obter token de autenticação (Carnê usa API de Cobranças, não PIX)
        $token = $this->getAccessToken(false);
        if (!$token) {
            return [
                'ok' => false,
                'message' => 'Falha ao autenticar no gateway. Verifique se as credenciais estão corretas.'
            ];
        }

        // Calcular valor por parcela
        $parcelValue = $totalAmount / $installments;
        $parcelValueInCents = intval($parcelValue * 100);
        
        // Obter data da primeira parcela
        $firstDueDate = $enrollment['first_due_date'] ?? null;
        if (!$firstDueDate || $firstDueDate === '0000-00-00') {
            // Se não tiver data configurada, usar 30 dias a partir de hoje
            $firstDueDate = date('Y-m-d', strtotime('+30 days'));
        }

        // Preparar payload do Carnê conforme schema oficial da API Efí
        // Schema: POST /v1/carnet
        // - items[] (obrigatório)
        // - customer{} (opcional mas recomendado)
        // - expire_at (obrigatório no nível raiz) - formato YYYY-MM-DD
        // - repeats (obrigatório) - INT (número de parcelas), não array!
        // - message (opcional)
        // - configurations{} (opcional)
        
        // Validar que a data está no futuro
        $expireDate = date('Y-m-d', strtotime($firstDueDate));
        if (strtotime($expireDate) < time()) {
            $this->efiLog('WARNING', 'createCarnet: Data de vencimento no passado, ajustando', [
                'enrollment_id' => $enrollment['id'],
                'data_original' => $expireDate
            ]);
            // Se a data estiver no passado, usar pelo menos 3 dias a partir de hoje
            $expireDate = date('Y-m-d', strtotime('+3 days'));
        }

        // Montar payload no formato correto do Carnê
        $payload = [
            'items' => [
                [
                    'name' => ($enrollment['service_name'] ?? 'Matrícula') . ' - Parcela 1/' . $installments,
                    'value' => $parcelValueInCents,
                    'amount' => 1
                ]
            ],
            'expire_at' => $expireDate, // ✅ OBRIGATÓRIO no nível raiz (formato YYYY-MM-DD)
            'repeats' => $installments, // ✅ OBRIGATÓRIO - INT (número de parcelas), não array!
            'message' => 'Pagamento referente a matrícula'
        ];

        // Adicionar dados do cliente
        if (!empty($student['cpf'])) {
            $cpf = preg_replace('/[^0-9]/', '', $student['cpf']);
            if (strlen($cpf) === 11) {
                $payload['customer'] = [
                    'name' => $student['full_name'] ?? $student['name'] ?? 'Cliente',
                    'cpf' => $cpf,
                    'email' => $student['email'] ?? null,
                    'phone_number' => !empty($student['phone']) ? preg_replace('/[^0-9]/', '', $student['phone']) : null
                ];

                // Adicionar endereço se disponível
                if (!empty($student['cep'])) {
                    $cep = preg_replace('/[^0-9]/', '', $student['cep']);
                    if (strlen($cep) === 8) {
                        $payload['customer']['address'] = [
                            'street' => $student['street'] ?? 'Não informado',
                            'number' => $student['number'] ?? 'S/N',
                            'neighborhood' => $student['neighborhood'] ?? '',
                            'zipcode' => $cep,
                            'city' => $student['city'] ?? '',
                            'state' => $student['state_uf'] ?? ''
                        ];
                    }
                }
            }
        }
        
        // Remover campos nulos/vazios do customer para evitar problemas na API
        if (isset($payload['customer'])) {
            $payload['customer'] = array_filter($payload['customer'], function($value) {
                return $value !== null && $value !== '';
            });
            
            // Se address existe mas está vazio, remover
            if (isset($payload['customer']['address'])) {
                $address = array_filter($payload['customer']['address'], function($value) {
                    return $value !== null && $value !== '';
                });
                if (empty($address)) {
                    unset($payload['customer']['address']);
                } else {
                    $payload['customer']['address'] = $address;
                }
            }
            
            // Se customer ficou vazio, remover
            if (empty($payload['customer'])) {
                unset($payload['customer']);
            }
        }
        
        // Log do payload para debug (sem dados sensíveis)
        $logPayload = $payload;
        // Remover dados sensíveis do log
        if (isset($logPayload['customer']['cpf'])) {
            $logPayload['customer']['cpf'] = '***';
        }
        if (isset($logPayload['customer']['email'])) {
            $logPayload['customer']['email'] = '***';
        }
        if (isset($logPayload['customer']['phone_number'])) {
            $logPayload['customer']['phone_number'] = '***';
        }
        
        // Log detalhado incluindo endpoint e host
        $this->efiLog('DEBUG', 'createCarnet: Payload no schema correto do Carnê', [
            'enrollment_id' => $enrollment['id'],
            'endpoint' => '/v1/carnet',
            'host' => $this->baseUrlCharges,
            'installments' => $installments,
            'expire_at' => $expireDate,
            'repeats' => $installments,
            'has_customer' => !empty($payload['customer']),
            'has_address' => !empty($payload['customer']['address'] ?? null),
            'payload_structure' => json_encode($logPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        ]);

        // Fazer requisição para criar Carnê - endpoint correto: /v1/carnet
        $response = $this->makeRequest('POST', '/v1/carnet', $payload, $token, false);

        $httpCode = $response['http_code'] ?? 0;
        $responseData = $response['response'] ?? null;

        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorMessage = 'Erro ao criar Carnê';
            $errorDetails = [];
            
            if (is_array($responseData)) {
                if (isset($responseData['error_description'])) {
                    $errorDesc = $responseData['error_description'];
                    if (is_array($errorDesc)) {
                        $errorMessage = json_encode($errorDesc, JSON_UNESCAPED_UNICODE);
                        $errorDetails = $errorDesc;
                    } else {
                        $errorMessage = (string)$errorDesc;
                    }
                } elseif (isset($responseData['message'])) {
                    $errorMessage = $responseData['message'];
                } elseif (isset($responseData['error'])) {
                    $errorMessage = $responseData['error'];
                }
                
                // Extrair detalhes específicos de validação
                if (isset($responseData['errors']) && is_array($responseData['errors'])) {
                    $errorDetails = $responseData['errors'];
                }
            } else {
                $errorMessage = (string)$responseData;
            }

            // Log detalhado incluindo payload (sem dados sensíveis)
            $logPayload = $payload;
            // Remover dados sensíveis do log
            if (isset($logPayload['customer']['cpf'])) {
                $logPayload['customer']['cpf'] = '***';
            }
            if (isset($logPayload['customer']['email'])) {
                $logPayload['customer']['email'] = '***';
            }
            if (isset($logPayload['customer']['phone_number'])) {
                $logPayload['customer']['phone_number'] = '***';
            }

            $this->efiLog('ERROR', 'createCarnet: Falha ao criar Carnê', [
                'enrollment_id' => $enrollment['id'],
                'http_code' => $httpCode,
                'endpoint' => '/v1/carnet',
                'host' => $this->baseUrlCharges,
                'error' => substr($errorMessage, 0, 500),
                'error_details' => $errorDetails,
                'payload_summary' => [
                    'installments' => $installments,
                    'repeats' => $installments,
                    'expire_at' => $expireDate,
                    'first_due_date' => $firstDueDate
                ],
                'response_snippet' => is_array($responseData) ? json_encode($responseData, JSON_UNESCAPED_UNICODE) : substr((string)$responseData, 0, 500)
            ]);

            $this->updateEnrollmentStatus($enrollment['id'], 'error', 'error', null);
            return [
                'ok' => false,
                'message' => 'Erro ao criar Carnê: ' . $errorMessage
            ];
        }

        // Processar resposta do Carnê
        $carnetData = $responseData['data'] ?? $responseData;
        $carnetId = $carnetData['carnet_id'] ?? null;
        $charges = $carnetData['charges'] ?? [];

        // Extrair charge_ids das parcelas
        $chargeIds = [];
        $paymentUrls = [];
        foreach ($charges as $charge) {
            $chargeId = $charge['charge_id'] ?? null;
            if ($chargeId) {
                $chargeIds[] = $chargeId;
                
                // Extrair URL de pagamento se disponível
                if (isset($charge['payment']['banking_billet']['link'])) {
                    $paymentUrls[] = $charge['payment']['banking_billet']['link'];
                }
            }
        }

        // Log de sucesso
        $this->efiLog('INFO', 'createCarnet: Carnê criado com sucesso', [
            'enrollment_id' => $enrollment['id'],
            'carnet_id' => $carnetId,
            'installments' => $installments,
            'charge_ids_count' => count($chargeIds)
        ]);

        // Atualizar matrícula com dados do Carnê
        // NOTA: Para Carnê, salvamos o carnet_id e lista de charge_ids
        // Como a estrutura atual do banco só tem gateway_charge_id (singular),
        // vamos salvar o carnet_id lá e guardar os charge_ids em gateway_payment_url como JSON
        // (solução temporária - ideal seria ter campos dedicados para Carnê)
        $chargeIdsJson = json_encode($chargeIds, JSON_UNESCAPED_UNICODE);
        $firstPaymentUrl = !empty($paymentUrls) ? $paymentUrls[0] : null;

        $this->updateEnrollmentStatus(
            $enrollment['id'],
            'generated',
            'waiting', // Status inicial do Carnê
            $carnetId, // Usar carnet_id como identificador principal
            null,
            $firstPaymentUrl // URL do primeiro boleto
        );

        // Atualizar campo adicional para armazenar charge_ids (via UPDATE direto)
        // Idealmente, deveria ter um campo gateway_charge_ids (JSON) ou tabela filha
        $stmt = $this->db->prepare("
            UPDATE enrollments 
            SET gateway_payment_url = ? 
            WHERE id = ?
        ");
        // Salvar JSON com charge_ids e payment_urls
        $additionalData = json_encode([
            'carnet_id' => $carnetId,
            'charge_ids' => $chargeIds,
            'payment_urls' => $paymentUrls,
            'type' => 'carne'
        ], JSON_UNESCAPED_UNICODE);
        $stmt->execute([$additionalData, $enrollment['id']]);

        return [
            'ok' => true,
            'type' => 'carne',
            'carnet_id' => $carnetId,
            'charge_ids' => $chargeIds,
            'installments' => $installments,
            'payment_urls' => $paymentUrls,
            'status' => 'waiting'
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
            $this->efiLog('WARN', 'processWebhook: Matrícula não encontrada', [
                'charge_id' => $chargeId
            ]);
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
            $this->efiLog('ERROR', 'getChargeStatus: Token não obtido', [
                'charge_id' => $chargeId,
                'isPix' => $isPix
            ]);
            return null;
        }

        // API Pix usa /v2/cob/{txid}, API de Cobranças usa /v1/charge/{charge_id} (singular, não plural)
        // makeRequest já adiciona /v1/ automaticamente para Cobranças
        $endpoint = $isPix ? "/v2/cob/{$chargeId}" : "/charge/{$chargeId}";
        $response = $this->makeRequest('GET', $endpoint, null, $token, $isPix);
        
        // makeRequest agora sempre retorna array com http_code
        $httpCode = $response['http_code'] ?? 0;
        $responseData = $response['response'] ?? $response;
        $rawResponse = $response['raw_response'] ?? '';
        $curlError = $response['curl_error'] ?? null;
        
        // Log detalhado para debug
        $this->efiLog('DEBUG', 'getChargeStatus response', [
            'charge_id' => $chargeId,
            'isPix' => $isPix,
            'http_code' => $httpCode,
            'has_response_data' => !empty($responseData),
            'has_data_key' => isset($responseData['data']),
            'response_keys' => is_array($responseData) ? array_keys($responseData) : [],
            'curl_error' => $curlError
        ]);
        
        if ($curlError) {
            $this->efiLog('ERROR', 'getChargeStatus: Erro cURL', [
                'charge_id' => $chargeId,
                'isPix' => $isPix,
                'curl_error' => $curlError
            ]);
            return null;
        }
        
        if ($httpCode >= 400 || !$responseData) {
            $errorMessage = 'Erro desconhecido';
            if (is_array($responseData)) {
                $errorMessage = $responseData['error_description'] ?? $responseData['message'] ?? $responseData['error'] ?? 'Erro desconhecido';
                if (is_array($errorMessage) || is_object($errorMessage)) {
                    $errorMessage = json_encode($errorMessage, JSON_UNESCAPED_UNICODE);
                }
            }
            
            $this->efiLog('ERROR', 'getChargeStatus: Falha na consulta', [
                'charge_id' => $chargeId,
                'isPix' => $isPix,
                'http_code' => $httpCode,
                'error' => substr((string)$errorMessage, 0, 180),
                'response_snippet' => substr($rawResponse, 0, 200)
            ]);
            return null;
        }
        
        // API Pix retorna dados diretamente, API de Cobranças retorna dentro de 'data'
        if ($isPix) {
            // API Pix: verificar se há erro
            if (isset($responseData['error']) || isset($responseData['mensagem'])) {
                $this->efiLog('ERROR', 'getChargeStatus: API Pix retornou erro', [
                    'charge_id' => $chargeId,
                    'error' => $responseData['error'] ?? $responseData['mensagem'] ?? 'Erro desconhecido'
                ]);
                return null;
            }
            // Retornar dados diretamente
            return $responseData;
        } else {
            // API de Cobranças: dados vêm dentro de 'data'
            if (!isset($responseData['data'])) {
                // Verificar se a resposta está em formato diferente
                // Algumas APIs podem retornar dados diretamente se houver apenas um item
                if (is_array($responseData) && isset($responseData['charge_id'])) {
                    // Dados estão diretamente na resposta (não em 'data')
                    $this->efiLog('INFO', 'getChargeStatus: Dados retornados diretamente (sem data)', [
                        'charge_id' => $chargeId
                    ]);
                    return $responseData;
                }
                
                $this->efiLog('ERROR', 'getChargeStatus: Resposta sem campo data', [
                    'charge_id' => $chargeId,
                    'response_keys' => is_array($responseData) ? array_keys($responseData) : [],
                    'response_snippet' => substr(json_encode($responseData, JSON_UNESCAPED_UNICODE), 0, 300)
                ]);
                return null;
            }
            return $responseData['data'];
        }
    }

    /**
     * Verifica se debug está habilitado
     * 
     * @return bool True se EFI_DEBUG está habilitado
     */
    private function efiDebugEnabled(): bool
    {
        $raw = $_ENV['EFI_DEBUG'] ?? getenv('EFI_DEBUG') ?? 'false';
        return in_array(strtolower(trim((string)$raw)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Helper para log padronizado da integração EFI
     * Grava diretamente no arquivo storage/logs/php_errors.log
     * 
     * @param string $level Nível do log: DEBUG, INFO, WARN, ERROR
     * @param string $message Mensagem do log
     * @param array $context Contexto adicional (host, url, endpoint, isPix, http_code, etc.)
     * @return void
     */
    private function efiLog(string $level, string $message, array $context = []): void
    {
        // DEBUG só grava se debug estiver habilitado
        if ($level === 'DEBUG' && !$this->efiDebugEnabled()) {
            return;
        }
        
        // INFO, WARN, ERROR sempre gravam
        $level = strtoupper($level);
        if (!in_array($level, ['DEBUG', 'INFO', 'WARN', 'ERROR'], true)) {
            $level = 'INFO';
        }
        
        // Sanitizar contexto: nunca incluir tokens completos, client_secret, pix_key
        $safeContext = [];
        foreach ($context as $key => $value) {
            // Mascarar dados sensíveis
            if (in_array($key, ['token', 'client_secret', 'pix_key', 'access_token', 'authorization', 'auth_header', 'header'])) {
                if (is_string($value) && strlen($value) > 0) {
                    if ($key === 'token' || $key === 'access_token') {
                        $safeContext['token_len'] = strlen($value);
                        $safeContext['token_prefix'] = substr($value, 0, 10);
                    } elseif ($key === 'authorization' || $key === 'auth_header' || $key === 'header') {
                        $safeContext[$key . '_len'] = strlen($value);
                    } else {
                        // client_secret, pix_key: não logar nada
                        continue;
                    }
                }
            } else {
                // Outros valores podem ser logados (mas limitar tamanho de strings)
                if (is_string($value) && strlen($value) > 200) {
                    $safeContext[$key] = substr($value, 0, 200) . '...';
                } else {
                    $safeContext[$key] = $value;
                }
            }
        }
        
        // Montar linha de log
        $timestamp = date('Y-m-d H:i:s');
        $timezone = date_default_timezone_get();
        $contextJson = !empty($safeContext) ? ' ' . json_encode($safeContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $line = "[{$timestamp} {$timezone}] EFI-{$level}: {$message}{$contextJson}";
        
        // Gravar diretamente no arquivo
        $logFile = __DIR__ . '/../../storage/logs/php_errors.log';
        @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
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
            $this->efiLog('ERROR', 'getAccessToken: Credenciais não configuradas', [
                'forPix' => $forPix,
                'has_client_id' => !empty($this->clientId),
                'has_client_secret' => !empty($this->clientSecret)
            ]);
            return null;
        }

        // OAuth Pix e OAuth Cobranças usam formatos diferentes
        // GARANTIR SEGREGAÇÃO ABSOLUTA: nunca misturar OAuth Pix com Cobranças
        if ($forPix) {
            // OAuth Pix: usa /oauth/token com form-urlencoded
            // SEMPRE usar oauthUrlPix (nunca oauthUrlCharges)
            $url = $this->oauthUrlPix . '/oauth/token';
            $payload = ['grant_type' => 'client_credentials'];
            $contentType = 'application/x-www-form-urlencoded';
            $postData = http_build_query($payload);
        } else {
            // OAuth Cobranças: usa /v1/authorize com JSON
            // SEMPRE usar oauthUrlCharges (nunca oauthUrlPix)
            // oauthUrlCharges já inclui /v1/authorize (não adicionar /oauth/token)
            $url = $this->oauthUrlCharges;
            $payload = ['grant_type' => 'client_credentials'];
            $contentType = 'application/json';
            $postData = json_encode($payload);
        }
        
        // Log já feito acima com efiLog()

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . $contentType,
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
            $this->efiLog('WARN', 'getAccessToken: Produção sem certificado configurado', [
                'forPix' => $forPix,
                'sandbox' => $this->sandbox
            ]);
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
            
            // Log já feito acima com efiLog()
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
            
            $this->efiLog('ERROR', 'getAccessToken failed', [
                'forPix' => $forPix,
                'http_code' => $httpCode,
                'curl_error' => $curlError ?: null,
                'error' => substr($errorMessage, 0, 180),
                'response_snippet' => substr($response, 0, 180)
            ]);
            return null;
        }

        if (!$response) {
            $this->efiLog('ERROR', 'getAccessToken: Resposta vazia da API', [
                'forPix' => $forPix,
                'host' => parse_url($url, PHP_URL_HOST),
                'url' => $url
            ]);
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['access_token'])) {
            $this->efiLog('ERROR', 'getAccessToken: access_token não encontrado na resposta', [
                'forPix' => $forPix,
                'host' => parse_url($url, PHP_URL_HOST),
                'url' => $url,
                'response_snippet' => substr($response, 0, 180)
            ]);
            return null;
        }

        $accessToken = $data['access_token'];
        
        // Validar que o token é uma string válida
        if (!is_string($accessToken) || empty(trim($accessToken))) {
            $this->efiLog('ERROR', 'getAccessToken: access_token não é uma string válida', [
                'forPix' => $forPix,
                'host' => parse_url($url, PHP_URL_HOST),
                'url' => $url,
                'token_type' => gettype($accessToken)
            ]);
            return null;
        }
        
        $accessToken = trim($accessToken);
        
        // Log após sucesso
        $this->efiLog('INFO', 'getAccessToken result', [
            'forPix' => $forPix,
            'http_code' => $httpCode,
            'curl_error' => null,
            'token' => $accessToken, // será sanitizado pelo efiLog
            'token_len' => strlen($accessToken),
            'token_prefix' => substr($accessToken, 0, 10)
        ]);
        
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
        
        // Para Cobranças, garantir que endpoint começa com /v1/
        // baseUrlCharges NÃO inclui /v1 (foi removido)
        if (!$isPix && strpos($endpoint, '/v1/') !== 0 && strpos($endpoint, '/v1') !== 0) {
            // Se endpoint não começa com /v1, adicionar
            if (strpos($endpoint, '/') === 0) {
                $endpoint = '/v1' . $endpoint;
            } else {
                $endpoint = '/v1/' . $endpoint;
            }
        }
        
        $url = $baseUrl . $endpoint;
        
        // GUARDRAIL: Bloquear URLs antigas (apis.gerencianet.com.br)
        // Nenhuma requisição deve usar apis.gerencianet.com.br
        if (strpos($url, 'apis.gerencianet.com.br') !== false || strpos($url, 'api.gerencianet.com.br') !== false) {
            $this->efiLog('ERROR', 'makeRequest: URL antiga detectada e bloqueada', [
                'isPix' => $isPix,
                'url' => $url,
                'endpoint' => $endpoint
            ]);
            return [
                'http_code' => 400,
                'response' => [
                    'error' => 'URL incorreta',
                    'error_description' => 'Não use apis.gerencianet.com.br. Use cobrancas.api.efipay.com.br para Cobranças ou pix.api.efipay.com.br para Pix.'
                ],
                'raw_response' => 'URL bloqueada',
                'curl_error' => null
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
                $this->efiLog('ERROR', 'makeRequest: Token inválido ou muito curto', [
                    'isPix' => $isPix,
                    'host' => parse_url($url, PHP_URL_HOST),
                    'url' => $url,
                    'token_len' => strlen($token)
                ]);
                return [
                    'http_code' => 401,
                    'response' => ['error' => 'Token de autenticação inválido', 'error_description' => 'Token muito curto ou vazio'],
                    'raw_response' => 'Token inválido',
                    'curl_error' => null
                ];
            }
            
            // IMPORTANTE: Garantir que não há espaços extras ou caracteres especiais
            // A API da EFI em produção é muito sensível ao formato do header
            $token = trim($token);
            
            // Verificar se há caracteres problemáticos no token
            if (preg_match('/[^\x20-\x7E]/', $token)) {
                $this->efiLog('WARN', 'makeRequest: Token contém caracteres não-ASCII', [
                    'isPix' => $isPix,
                    'host' => parse_url($url, PHP_URL_HOST),
                    'url' => $url,
                    'token' => $token // será sanitizado pelo efiLog
                ]);
                // Remover caracteres não-ASCII do token
                $token = preg_replace('/[^\x20-\x7E]/', '', $token);
                $token = trim($token);
            }
            
            // Montar header Authorization - DEVE ser exatamente "Authorization: Bearer {token}"
            // Sem espaços extras, sem quebras de linha, sem caracteres especiais
            $authHeader = 'Authorization: Bearer ' . $token;
            
            // Verificar se o header está correto
            if (strlen($authHeader) !== strlen('Authorization: Bearer ') + strlen($token)) {
                $this->efiLog('ERROR', 'makeRequest: Header Authorization tem tamanho incorreto', [
                    'isPix' => $isPix,
                    'host' => parse_url($url, PHP_URL_HOST),
                    'url' => $url,
                    'expected_len' => strlen('Authorization: Bearer ') + strlen($token),
                    'actual_len' => strlen($authHeader)
                ]);
            }
            
            // Authorization DEVE ser o primeiro header
            $headers[] = $authHeader;
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
            $this->efiLog('WARN', 'makeRequest: Produção sem certificado configurado', [
                'isPix' => $isPix,
                'host' => parse_url($url, PHP_URL_HOST),
                'url' => $url
            ]);
        }

        // Log dos headers (apenas tamanho, nunca conteúdo completo)
        if ($token) {
            $this->efiLog('DEBUG', 'makeRequest headers', [
                'isPix' => $isPix,
                'auth_header_len' => strlen($authHeader ?? ''),
                'token' => $token, // será sanitizado pelo efiLog
                'token_len' => strlen($token),
                'token_prefix' => substr($token, 0, 10)
            ]);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        // SEMPRE retornar array com http_code, response e raw_response
        $result = [
            'http_code' => $httpCode,
            'response' => null,
            'raw_response' => $response,
            'curl_error' => $curlError ?: null
        ];

        if ($curlError) {
            $this->efiLog('ERROR', 'makeRequest: Erro cURL', [
                'isPix' => $isPix,
                'host' => parse_url($url, PHP_URL_HOST),
                'url' => $url,
                'curl_error' => $curlError
            ]);
            $result['response'] = ['error' => 'cURL Error', 'error_description' => $curlError];
            return $result;
        }

        $data = json_decode($response, true);
        $result['response'] = $data !== null ? $data : ['raw' => $response];

        // Logs já foram feitos acima no bloco "Log após requisição"
        
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
            
            // Log já feito acima com efiLog()
        }

        return $result;
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
