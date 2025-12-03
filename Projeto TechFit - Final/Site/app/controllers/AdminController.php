<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Modalidade.php';
require_once __DIR__ . '/../models/Turma.php';
require_once __DIR__ . '/../models/Acesso.php';
require_once __DIR__ . '/../models/Agendamento.php';

class AdminController extends Controller {
    public function index() {
        $this->requireLogin('admin');

        $usuarioModel = new Usuario();
        $usuarios = $usuarioModel->all();

        $acessoModel = new Acesso();
        $acessos = $acessoModel->todos();

        $this->view('admin/dashboard', [
            'usuarios' => $usuarios,
            'acessos'  => $acessos,
            'titulo'   => 'Painel Administrativo'
        ]);
    }

    public function modalidades() {
        $this->requireLogin('admin');
        $model = new Modalidade();
        $modalidades = $model->all();

        $this->view('admin/modalidades', [
            'modalidades' => $modalidades,
            'titulo'      => 'Modalidades'
        ]);
    }

    public function salvarModalidade() {
        $this->requireLogin('admin');
        $nome = trim($_POST['nome'] ?? '');
        
        if (empty($nome)) {
            $_SESSION['mensagem_erro'] = 'Nome da modalidade é obrigatório.';
            $this->redirect('/admin/modalidades');
        }

        $model = new Modalidade();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($id) {
            $model->update($id, ['nome' => $nome]);
            $_SESSION['mensagem_sucesso'] = 'Modalidade atualizada com sucesso!';
        } else {
            $model->create(['nome' => $nome]);
            $_SESSION['mensagem_sucesso'] = 'Modalidade criada com sucesso!';
        }
        
        $this->redirect('/admin/modalidades');
    }

    public function excluirModalidade() {
        $this->requireLogin('admin');
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($id) {
            $model = new Modalidade();
            if ($model->delete($id)) {
                $_SESSION['mensagem_sucesso'] = 'Modalidade excluída com sucesso!';
            } else {
                $_SESSION['mensagem_erro'] = 'Erro ao excluir modalidade.';
            }
        }
        
        $this->redirect('/admin/modalidades');
    }

    public function turmas() {
        $this->requireLogin('admin');
        $turmaModel = new Turma();
        $modalidadeModel = new Modalidade();

        $todasTurmas = $turmaModel->all();
        $modalidades = $modalidadeModel->all();
        
        // Adicionar nome da modalidade para cada turma
        $turmas = [];
        foreach ($todasTurmas as $turma) {
            $modalidade = $modalidadeModel->find($turma['modalidade_id']);
            $turma['modalidade_nome'] = $modalidade['nome'] ?? 'N/A';
            $turmas[] = $turma;
        }

        $this->view('admin/turmas', [
            'turmas'      => $turmas,
            'modalidades' => $modalidades,
            'titulo'      => 'Turmas'
        ]);
    }

    public function salvarTurma() {
        $this->requireLogin('admin');
        $turmaModel = new Turma();

        $modalidadeId = filter_input(INPUT_POST, 'modalidade_id', FILTER_VALIDATE_INT);
        $instrutor = trim($_POST['instrutor'] ?? '');
        $data = $_POST['data'] ?? '';
        $inicio = $_POST['inicio'] ?? '';
        $fim = $_POST['fim'] ?? '';
        $vagas = filter_input(INPUT_POST, 'vagas', FILTER_VALIDATE_INT);

        if (!$modalidadeId || empty($instrutor) || empty($data) || empty($inicio) || empty($fim) || !$vagas || $vagas < 1) {
            $_SESSION['mensagem_erro'] = 'Por favor, preencha todos os campos corretamente.';
            $this->redirect('/admin/turmas');
        }

        $dados = [
            'modalidade_id' => $modalidadeId,
            'instrutor' => $instrutor,
            'data' => $data,
            'inicio' => $inicio,
            'fim' => $fim,
            'vagas' => $vagas
        ];

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($id) {
            $turmaAntiga = $turmaModel->find($id);
            if ($turmaModel->update($id, $dados)) {
                if ($turmaAntiga && ($turmaAntiga['data'] != $dados['data'] || 
                    $turmaAntiga['inicio'] != $dados['inicio'] || 
                    $turmaAntiga['fim'] != $dados['fim'])) {
                    $mensagem = "A turma de {$turmaAntiga['data']} das {$turmaAntiga['inicio']} foi alterada para {$dados['data']} das {$dados['inicio']} às {$dados['fim']}.";
                    $turmaModel->notificarAlteracao($id, 'alteracao_horario', $mensagem);
                }
                $_SESSION['mensagem_sucesso'] = 'Turma atualizada com sucesso!';
            }
        } else {
            if ($turmaModel->create($dados)) {
                $_SESSION['mensagem_sucesso'] = 'Turma criada com sucesso!';
            }
        }
        
        $this->redirect('/admin/turmas');
    }

    public function relatorios() {
        $this->requireLogin('admin');
        
        require_once __DIR__ . '/../models/Avaliacao.php';
        require_once __DIR__ . '/../models/Agendamento.php';
        
        $usuarioModel = new Usuario();
        $acessoModel = new Acesso();
        $agendamentoModel = new Agendamento();
        $avaliacaoModel = new Avaliacao();
        
        // Estatísticas gerais
        $totalUsuarios = count($usuarioModel->all());
        $totalAcessos = count($acessoModel->todos());
        $totalAgendamentos = count($agendamentoModel->all());
        $totalAvaliacoes = count($avaliacaoModel->all());
        
        require_once __DIR__ . '/../../config/database.php';
        $pdo = Database::getInstance();
        
        // Relatório de frequência
        $sql = "SELECT 
                    u.id,
                    u.nome,
                    u.checkins_mes,
                    COUNT(a.id) AS total_acessos
                FROM usuarios u
                LEFT JOIN acessos a ON u.id = a.usuario_id
                WHERE u.perfil = 'aluno'
                GROUP BY u.id, u.nome, u.checkins_mes
                ORDER BY u.checkins_mes DESC";
        $frequencia = $pdo->query($sql)->fetchAll();
        
        // Relatório de modalidades mais populares
        $sql = "SELECT 
                    m.nome AS modalidade,
                    COUNT(DISTINCT a.usuario_id) AS alunos,
                    COUNT(a.id) AS agendamentos
                FROM modalidades m
                LEFT JOIN turmas t ON m.id = t.modalidade_id
                LEFT JOIN agendamentos a ON t.id = a.turma_id
                GROUP BY m.id, m.nome
                ORDER BY agendamentos DESC";
        $modalidades = $pdo->query($sql)->fetchAll();
        
        $this->view('admin/relatorios', [
            'totalUsuarios' => $totalUsuarios,
            'totalAcessos' => $totalAcessos,
            'totalAgendamentos' => $totalAgendamentos,
            'totalAvaliacoes' => $totalAvaliacoes,
            'frequencia' => $frequencia,
            'modalidades' => $modalidades,
            'titulo' => 'Relatórios Gerenciais'
        ]);
    }

    public function usuarios() {
        $this->requireLogin('admin');
        $usuarioModel = new Usuario();
        $usuarios = $usuarioModel->all();

        $this->view('admin/usuarios', [
            'usuarios' => $usuarios,
            'titulo' => 'Gerenciar Usuários'
        ]);
    }

    public function salvarUsuario() {
        $this->requireLogin('admin');
        $usuarioModel = new Usuario();
        
        $nome = trim($_POST['nome'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $perfil = $_POST['perfil'] ?? '';
        $modalidade = $_POST['modalidade'] ?? null;
        $senha = $_POST['senha'] ?? '';
        
        if (empty($nome) || empty($email) || !in_array($perfil, ['admin', 'aluno'])) {
            $_SESSION['mensagem_erro'] = 'Por favor, preencha todos os campos obrigatórios.';
            $this->redirect('/admin/usuarios');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['mensagem_erro'] = 'E-mail inválido.';
            $this->redirect('/admin/usuarios');
        }
        
        $dados = [
            'nome' => $nome,
            'email' => $email,
            'perfil' => $perfil,
            'modalidade' => $modalidade
        ];
        
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!empty($senha)) {
            if (strlen($senha) < 6) {
                $_SESSION['mensagem_erro'] = 'A senha deve ter no mínimo 6 caracteres.';
                $this->redirect('/admin/usuarios');
            }
            $dados['senha'] = password_hash($senha, PASSWORD_DEFAULT);
        }
        
        if ($id) {
            if (empty($dados['senha'])) {
                unset($dados['senha']);
            }
            if ($usuarioModel->update($id, $dados)) {
                $_SESSION['mensagem_sucesso'] = 'Usuário atualizado com sucesso!';
            }
        } else {
            if (empty($dados['senha'])) {
                $dados['senha'] = password_hash('senha123', PASSWORD_DEFAULT);
            }
            if ($usuarioModel->create($dados)) {
                $_SESSION['mensagem_sucesso'] = 'Usuário criado com sucesso!';
            }
        }
        
        $this->redirect('/admin/usuarios');
    }

    public function excluirUsuario() {
        $this->requireLogin('admin');
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if ($id && $id != $_SESSION['usuario']['id']) {
            $usuarioModel = new Usuario();
            if ($usuarioModel->delete($id)) {
                $_SESSION['mensagem_sucesso'] = 'Usuário excluído com sucesso!';
            } else {
                $_SESSION['mensagem_erro'] = 'Erro ao excluir usuário.';
            }
        } else {
            $_SESSION['mensagem_erro'] = 'Não é possível excluir seu próprio usuário.';
        }
        
        $this->redirect('/admin/usuarios');
    }

    public function qrcodeDados() {
        $this->requireLogin('admin');
        header('Content-Type: application/json');
        
        $usuarioId = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
        
        if (!$usuarioId) {
            echo json_encode(['erro' => 'ID de usuário inválido']);
            return;
        }
        
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->find($usuarioId);
        
        if (!$usuario) {
            echo json_encode(['erro' => 'Usuário não encontrado']);
            return;
        }
        
        if ($usuario['perfil'] !== 'aluno') {
            echo json_encode(['erro' => 'QR Code disponível apenas para alunos']);
            return;
        }
        
        require_once __DIR__ . '/../../config/database.php';
        $pdo = Database::getInstance();
        
        $sql = "SELECT senha FROM usuarios WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $usuarioId]);
        $senhaData = $stmt->fetch();
        
        echo json_encode([
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'senha' => $senhaData['senha'] ?? ''
        ]);
    }
}
