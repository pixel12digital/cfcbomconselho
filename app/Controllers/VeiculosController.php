<?php

namespace App\Controllers;

use App\Models\Vehicle;
use App\Services\AuditService;
use App\Config\Constants;

class VeiculosController extends Controller
{
    private $cfcId;
    private $auditService;

    public function __construct()
    {
        $this->cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        $this->auditService = new AuditService();
    }

    public function index()
    {
        $vehicleModel = new Vehicle();
        $vehicles = $vehicleModel->findByCfc($this->cfcId, false); // Todos, incluindo inativos
        
        $data = [
            'pageTitle' => 'Veículos',
            'vehicles' => $vehicles
        ];
        $this->view('veiculos/index', $data);
    }

    public function novo()
    {
        $data = [
            'pageTitle' => 'Novo Veículo'
        ];
        $this->view('veiculos/form', $data);
    }

    public function criar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('veiculos'));
        }
        
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('veiculos'));
        }
        
        $plate = strtoupper(trim($_POST['plate'] ?? ''));
        $category = trim($_POST['category'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($plate)) {
            $_SESSION['error'] = 'Placa é obrigatória.';
            redirect(base_url('veiculos/novo'));
        }
        
        if (empty($category)) {
            $_SESSION['error'] = 'Categoria é obrigatória.';
            redirect(base_url('veiculos/novo'));
        }
        
        // Verificar se placa já existe
        $vehicleModel = new Vehicle();
        $existing = $vehicleModel->findByPlate($this->cfcId, $plate);
        if ($existing) {
            $_SESSION['error'] = 'Já existe um veículo cadastrado com esta placa.';
            redirect(base_url('veiculos/novo'));
        }
        
        $data = [
            'cfc_id' => $this->cfcId,
            'plate' => $plate,
            'category' => $category,
            'brand' => !empty($_POST['brand']) ? trim($_POST['brand']) : null,
            'model' => !empty($_POST['model']) ? trim($_POST['model']) : null,
            'year' => !empty($_POST['year']) ? (int)$_POST['year'] : null,
            'color' => !empty($_POST['color']) ? trim($_POST['color']) : null,
            'is_active' => $isActive
        ];
        
        $vehicleId = $vehicleModel->create($data);
        
        $this->auditService->logCreate('veiculos', $vehicleId, $data);
        
        $_SESSION['success'] = 'Veículo cadastrado com sucesso!';
        redirect(base_url('veiculos'));
    }

    public function editar($id)
    {
        $vehicleModel = new Vehicle();
        $vehicle = $vehicleModel->find($id);
        
        if (!$vehicle || $vehicle['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Veículo não encontrado.';
            redirect(base_url('veiculos'));
        }
        
        $data = [
            'pageTitle' => 'Editar Veículo',
            'vehicle' => $vehicle
        ];
        $this->view('veiculos/form', $data);
    }

    public function atualizar($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('veiculos'));
        }
        
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('veiculos'));
        }
        
        $vehicleModel = new Vehicle();
        $vehicle = $vehicleModel->find($id);
        
        if (!$vehicle || $vehicle['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Veículo não encontrado.';
            redirect(base_url('veiculos'));
        }
        
        $plate = strtoupper(trim($_POST['plate'] ?? ''));
        $category = trim($_POST['category'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($plate)) {
            $_SESSION['error'] = 'Placa é obrigatória.';
            redirect(base_url('veiculos/' . $id . '/editar'));
        }
        
        if (empty($category)) {
            $_SESSION['error'] = 'Categoria é obrigatória.';
            redirect(base_url('veiculos/' . $id . '/editar'));
        }
        
        // Verificar se placa já existe em outro veículo
        $existing = $vehicleModel->findByPlate($this->cfcId, $plate);
        if ($existing && $existing['id'] != $id) {
            $_SESSION['error'] = 'Já existe um veículo cadastrado com esta placa.';
            redirect(base_url('veiculos/' . $id . '/editar'));
        }
        
        $dataBefore = $vehicle;
        $updateData = [
            'plate' => $plate,
            'category' => $category,
            'brand' => !empty($_POST['brand']) ? trim($_POST['brand']) : null,
            'model' => !empty($_POST['model']) ? trim($_POST['model']) : null,
            'year' => !empty($_POST['year']) ? (int)$_POST['year'] : null,
            'color' => !empty($_POST['color']) ? trim($_POST['color']) : null,
            'is_active' => $isActive
        ];
        
        $vehicleModel->update($id, $updateData);
        
        $this->auditService->logUpdate('veiculos', $id, $dataBefore, array_merge($vehicle, $updateData));
        
        $_SESSION['success'] = 'Veículo atualizado com sucesso!';
        redirect(base_url('veiculos'));
    }
}
