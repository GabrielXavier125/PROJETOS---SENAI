<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/Turma.php';
require_once __DIR__ . '/../models/Agendamento.php';

class AgendamentoController extends Controller {
    public function index() {
        $this->requireLogin();

        $turmaModel = new Turma();
        $agendamentoModel = new Agendamento();
        require_once __DIR__ . '/../models/Modalidade.php';
        $modalidadeModel = new Modalidade();

        $turmas = $turmaModel->proximasTurmas();
        $modalidades = $modalidadeModel->all() ?: [];
        
        // Se for admin, mostrar todos os agendamentos. Se for aluno, apenas os dele.
        if ($_SESSION['usuario']['perfil'] === 'admin') {
            $meusAgendamentos = $agendamentoModel->all();
        } else {
            $meusAgendamentos = $agendamentoModel->doUsuario($_SESSION['usuario']['id']);
        }
        
        // Adicionar informações de ocupação para cada turma
        foreach ($turmas as &$turma) {
            $contagem = $turmaModel->contagemPorStatus($turma['id']);
            $ocupadas = (int)($contagem['confirmados'] ?? 0);
            $ocupacao = $turma['vagas'] > 0 ? round(($ocupadas / $turma['vagas']) * 100, 1) : 0;
            $turma['ocupadas'] = $ocupadas;
            $turma['ocupacao_percentual'] = $ocupacao;
        }

        $this->view('agendamentos/index', [
            'turmas'           => $turmas,
            'meusAgendamentos' => $meusAgendamentos,
            'modalidades'      => $modalidades,
            'titulo'           => $_SESSION['usuario']['perfil'] === 'admin' ? 'Agendamentos - Todos' : 'Meus Agendamentos',
            'isAdmin'          => $_SESSION['usuario']['perfil'] === 'admin'
        ]);
    }

    public function store() {
        $this->requireLogin();
        
        if ($_SESSION['usuario']['perfil'] !== 'aluno') {
            $_SESSION['mensagem_erro'] = 'Apenas alunos podem fazer agendamentos.';
            $this->redirect('/agendamentos');
        }
        
        $agendamentoModel = new Agendamento();
        $turmaId = filter_input(INPUT_POST, 'turma_id', FILTER_VALIDATE_INT);
        
        if ($turmaId) {
            if ($agendamentoModel->criarComEspera($_SESSION['usuario']['id'], $turmaId)) {
                $_SESSION['mensagem_sucesso'] = 'Agendamento realizado com sucesso!';
            } else {
                $_SESSION['mensagem_erro'] = 'Erro ao realizar agendamento. Turma pode estar lotada.';
            }
        } else {
            $data = $_POST['data'] ?? '';
            $horarioInicio = $_POST['horario_inicio'] ?? '';
            $horarioFim = $_POST['horario_fim'] ?? '';
            $modalidade = trim($_POST['modalidade'] ?? '');
            $observacoes = trim($_POST['observacoes'] ?? '');
            
            if (empty($data) || empty($horarioInicio) || empty($horarioFim) || empty($modalidade)) {
                $_SESSION['mensagem_erro'] = 'Por favor, preencha todos os campos obrigatórios.';
                $this->redirect('/agendamentos');
            }
            
            if (strtotime($data . ' ' . $horarioInicio) < time()) {
                $_SESSION['mensagem_erro'] = 'Não é possível agendar para datas/horários passados.';
                $this->redirect('/agendamentos');
            }
            
            if ($agendamentoModel->criarAgendamentoLivre(
                $_SESSION['usuario']['id'],
                $data,
                $horarioInicio,
                $horarioFim,
                $modalidade,
                $observacoes
            )) {
                $_SESSION['mensagem_sucesso'] = 'Agendamento personalizado realizado com sucesso! Aguarde confirmação do administrador.';
            } else {
                $_SESSION['mensagem_erro'] = 'Erro ao criar agendamento personalizado.';
            }
        }
        
        $this->redirect('/agendamentos');
    }

    public function cancelar() {
        $this->requireLogin();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            $_SESSION['mensagem_erro'] = 'ID de agendamento inválido.';
            $this->redirect('/agendamentos');
        }
        
        $agendamentoModel = new Agendamento();
        $agendamento = $agendamentoModel->find($id);
        
        if (!$agendamento) {
            $_SESSION['mensagem_erro'] = 'Agendamento não encontrado.';
            $this->redirect('/agendamentos');
        }
        
        $isOwner = $agendamento['usuario_id'] == $_SESSION['usuario']['id'];
        $isAdmin = $_SESSION['usuario']['perfil'] === 'admin';
        
        if ($isOwner || $isAdmin) {
            if ($agendamentoModel->delete($id)) {
                $_SESSION['mensagem_sucesso'] = 'Agendamento cancelado com sucesso!';
            } else {
                $_SESSION['mensagem_erro'] = 'Erro ao cancelar agendamento.';
            }
        } else {
            $_SESSION['mensagem_erro'] = 'Você não tem permissão para cancelar este agendamento.';
        }
        
        $this->redirect('/agendamentos');
    }

    public function relatorioOcupacao() {
        $this->requireLogin('admin');
        $turmaModel = new Turma();
        
        $dataInicio = $_GET['data_inicio'] ?? null;
        $dataFim = $_GET['data_fim'] ?? null;
        
        $relatorio = $turmaModel->relatorioOcupacao($dataInicio, $dataFim);
        
        $this->view('agendamentos/relatorio', [
            'relatorio' => $relatorio,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'titulo' => 'Relatório de Ocupação'
        ]);
    }

    public function notificacoes() {
        $this->requireLogin();
        $turmaModel = new Turma();
        $notificacoes = $turmaModel->getNotificacoes($_SESSION['usuario']['id']);
        
        $this->view('agendamentos/notificacoes', [
            'notificacoes' => $notificacoes,
            'titulo' => 'Notificações'
        ]);
    }
}
