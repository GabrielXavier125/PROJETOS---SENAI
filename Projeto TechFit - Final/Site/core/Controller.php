<?php
class Controller {
    protected function view($view, $data = [], $layout = 'main') {
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . "/../app/views/{$view}.php";
        $layoutFile = __DIR__ . "/../app/views/layouts/{$layout}.php";

        if (!file_exists($viewFile)) {
            http_response_code(404);
            die("View não encontrada.");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    protected function redirect($path) {
        header("Location: {$path}");
        exit;
    }

    protected function isLogged() {
        return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']);
    }

    protected function requireLogin($role = null) {
        if (!$this->isLogged()) {
            $this->redirect('/login');
        }
        if ($role && (!isset($_SESSION['usuario']['perfil']) || $_SESSION['usuario']['perfil'] !== $role)) {
            $this->redirect('/');
        }
    }

    protected function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
