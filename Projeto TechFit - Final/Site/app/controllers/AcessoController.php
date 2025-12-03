<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/Acesso.php';

class AcessoController extends Controller {
    public function index() {
        $this->requireLogin();
        $acessoModel = new Acesso();
        $isAdmin = $_SESSION['usuario']['perfil'] === 'admin';
        
        $acessos = $isAdmin ? $acessoModel->todos() : $acessoModel->doUsuario($_SESSION['usuario']['id']);

        $this->view('acessos/index', [
            'acessos' => $acessos,
            'titulo' => $isAdmin ? 'Todos os Acessos' : 'Meu Histórico de Acessos',
            'isAdmin' => $isAdmin
        ]);
    }

    public function adminIndex() {
        $this->requireLogin('admin');
        $acessoModel = new Acesso();
        $acessos = $acessoModel->todos();

        $this->view('acessos/admin', [
            'acessos' => $acessos,
            'titulo'  => 'Acessos - Administração'
        ]);
    }

    public function gerarQRCode() {
        $this->requireLogin();
        
        require_once __DIR__ . '/../models/Usuario.php';
        require_once __DIR__ . '/../../config/database.php';
        
        $usuarioModel = new Usuario();
        $pdo = Database::getInstance();
        
        $usuarioId = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT) ?: $_SESSION['usuario']['id'];
        $isAdmin = $_SESSION['usuario']['perfil'] === 'admin';
        
        if ($isAdmin && isset($_GET['usuario_id'])) {
            $usuario = $usuarioModel->find($usuarioId);
            $titulo = 'QR Code - ' . ($usuario['nome'] ?? 'Aluno');
        } else {
            // Aluno só pode ver o próprio QR Code
            if ($usuarioId != $_SESSION['usuario']['id']) {
                $this->redirect('/acessos/qrcode');
            }
            $usuario = $usuarioModel->find($usuarioId);
            $titulo = 'Meu QR Code';
        }
        
        // Verificar se já existe QR Code
        $sql = "SELECT codigo_qr FROM usuarios_qrcode WHERE usuario_id = :usuario_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['usuario_id' => $usuarioId]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            $codigoQR = $existente['codigo_qr'];
        } else {
            // Gerar código QR único
            $codigoQR = bin2hex(random_bytes(16));
            
            // Salvar no banco
            $sql = "INSERT INTO usuarios_qrcode (usuario_id, codigo_qr) 
                    VALUES (:usuario_id, :codigo_qr)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'usuario_id' => $usuarioId,
                'codigo_qr' => $codigoQR
            ]);
        }
        
        // Se for admin, buscar lista de alunos para seleção
        $alunos = [];
        if ($_SESSION['usuario']['perfil'] === 'admin') {
            $todosUsuarios = $usuarioModel->all();
            $alunos = array_filter($todosUsuarios, function($u) {
                return $u['perfil'] === 'aluno';
            });
        }
        
        $this->view('acessos/qrcode', [
            'codigoQR' => $codigoQR,
            'usuario' => $usuario,
            'titulo' => $titulo,
            'alunos' => $alunos,
            'usuarioId' => $usuarioId,
            'isAdmin' => $_SESSION['usuario']['perfil'] === 'admin'
        ]);
    }

    public function registrarEntrada() {
        require_once __DIR__ . '/../../config/database.php';
        
        $codigo = $_POST['codigo'] ?? null;
        $tipo = $_POST['tipo'] ?? 'qrcode';
        
        if (!$codigo) {
            http_response_code(400);
            echo json_encode(['erro' => 'Código não fornecido']);
            return;
        }
        
        $pdo = Database::getInstance();
        $acessoModel = new Acesso();
        
        // Buscar usuário pelo QR Code
        $sql = "SELECT u.id FROM usuarios u
                JOIN usuarios_qrcode uq ON u.id = uq.usuario_id
                WHERE uq.codigo_qr = :codigo AND uq.ativo = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['codigo' => $codigo]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            http_response_code(404);
            echo json_encode(['erro' => 'QR Code inválido']);
            return;
        }
        
        $acessoId = $acessoModel->registrarAcesso($usuario['id'], $tipo, $codigo);
        
        // Atualizar check-ins do mês
        $sql = "UPDATE usuarios SET checkins_mes = checkins_mes + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $usuario['id']]);
        
        echo json_encode(['sucesso' => true, 'acesso_id' => $acessoId]);
    }

    public function registrarQRCode() {
        $this->requireLogin('admin');
        header('Content-Type: application/json');
        
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);
        
        if (!$dados || !isset($dados['nome']) || !isset($dados['email']) || !isset($dados['senha'])) {
            http_response_code(400);
            echo json_encode(['erro' => 'Dados do QR Code inválidos']);
            return;
        }
        
        require_once __DIR__ . '/../../config/database.php';
        require_once __DIR__ . '/../models/Usuario.php';
        
        $pdo = Database::getInstance();
        $usuarioModel = new Usuario();
        
        $usuario = $usuarioModel->buscarPorEmail($dados['email']);
        
        if (!$usuario) {
            http_response_code(404);
            echo json_encode(['erro' => 'Usuário não encontrado']);
            return;
        }
        
        if ($usuario['nome'] !== $dados['nome']) {
            http_response_code(400);
            echo json_encode(['erro' => 'Dados do QR Code não correspondem ao usuário']);
            return;
        }
        
        if ($dados['senha'] !== $usuario['senha']) {
            http_response_code(400);
            echo json_encode(['erro' => 'Senha do QR Code inválida']);
            return;
        }
        
        $acessoModel = new Acesso();
        $acessoId = $acessoModel->registrarAcesso($usuario['id'], 'qrcode', 'QR_CODE_' . $usuario['id']);
        
        if ($acessoId) {
            $sql = "UPDATE usuarios SET checkins_mes = checkins_mes + 1 WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $usuario['id']]);
            
            echo json_encode([
                'sucesso' => true,
                'acesso_id' => $acessoId,
                'usuario' => $usuario['nome']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao registrar acesso']);
        }
    }

    public function carteirinha() {
        $this->requireLogin();
        
        require_once __DIR__ . '/../models/Usuario.php';
        require_once __DIR__ . '/../../config/database.php';
        
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->find($_SESSION['usuario']['id']);
        
        if (!$usuario) {
            $this->redirect('/');
        }
        
        $pdo = Database::getInstance();
        
        $sql = "SELECT senha FROM usuarios WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $usuario['id']]);
        $senhaData = $stmt->fetch();
        
        $qrData = json_encode([
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'senha' => $senhaData['senha'] ?? ''
        ]);
        
        $this->view('acessos/carteirinha', [
            'usuario' => $usuario,
            'qrData' => $qrData,
            'titulo' => 'Minha Carteirinha'
        ]);
    }

    public function relatorioUtilizacao() {
        $this->requireLogin('admin');
        $acessoModel = new Acesso();
        
        $dataInicio = $_GET['data_inicio'] ?? null;
        $dataFim = $_GET['data_fim'] ?? null;
        
        $relatorio = $acessoModel->relatorioUtilizacao($dataInicio, $dataFim);
        
        $this->view('acessos/relatorio', [
            'relatorio' => $relatorio,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'titulo' => 'Relatório de Utilização'
        ]);
    }
}
