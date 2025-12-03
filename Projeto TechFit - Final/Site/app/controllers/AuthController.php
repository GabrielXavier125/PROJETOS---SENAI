<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController extends Controller {
    public function showLoginForm() {
        if ($this->isLogged()) {
            $this->redirect('/');
        }
        $this->view('auth/login', ['titulo' => 'Login - TechFit']);
    }

    public function login() {
        if ($this->isLogged()) {
            $this->redirect('/');
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $senha = $_POST['senha'] ?? '';

        if (empty($email) || empty($senha)) {
            $this->view('auth/login', [
                'titulo' => 'Login - TechFit',
                'erro' => 'Por favor, preencha todos os campos.'
            ]);
            return;
        }

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorEmail($email);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario'] = [
                'id' => (int)$usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'perfil' => $usuario['perfil']
            ];
            session_regenerate_id(true);

            if ($usuario['perfil'] === 'admin') {
                $this->redirect('/dashboard');
            } else {
                $this->redirect('/agendamentos');
            }
        } else {
            sleep(1);
            $this->view('auth/login', [
                'titulo' => 'Login - TechFit',
                'erro' => 'E-mail ou senha incorretos.'
            ]);
        }
    }

    public function logout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        $this->redirect('/');
    }
}
